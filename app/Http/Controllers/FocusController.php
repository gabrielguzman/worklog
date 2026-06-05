<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\FocusSession;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FocusController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $pendingTasks = $user->tasks()
            ->with(['project:id,name,color'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByRaw("FIELD(priority,'urgent','high','medium','low')")
            ->orderBy('due_date', 'asc')
            ->get(['id', 'title', 'priority', 'status', 'project_id'])
            ->map(fn($t) => [
                'id'       => $t->id,
                'title'    => $t->title,
                'priority' => $t->priority,
                'status'   => $t->status,
                'project'  => $t->project ? ['name' => $t->project->name, 'color' => $t->project->color] : null,
            ]);

        $recentSessions = $user->focusSessions()
            ->with(['task:id,title'])
            ->orderBy('started_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($s) => [
                'id'               => $s->id,
                'duration_minutes' => $s->duration_minutes,
                'started_at'       => $s->started_at->locale('es')->isoFormat('D MMM, HH:mm'),
                'notes'            => $s->notes,
                'status'           => $s->status,
                'task'             => $s->task ? ['id' => $s->task->id, 'title' => $s->task->title] : null,
            ]);

        $todayMinutes = $user->focusSessions()
            ->whereDate('started_at', today())
            ->where('status', 'completed')
            ->sum('duration_minutes');

        $weekSessions = $user->focusSessions()
            ->where('started_at', '>=', now()->startOfWeek())
            ->where('status', 'completed')
            ->count();

        return Inertia::render('Focus/Index', [
            'pendingTasks'   => $pendingTasks,
            'recentSessions' => $recentSessions,
            'todayMinutes'   => $todayMinutes,
            'weekSessions'   => $weekSessions,
        ]);
    }

    // Iniciar sesión
    public function start(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'task_id'          => 'nullable|exists:tasks,id',
            'duration_minutes' => 'required|integer|min:1|max:120',
        ]);

        // Validar que la tarea pertenece al usuario autenticado
        if (!empty($data['task_id'])) {
            $task = $user->tasks()->find($data['task_id']);
            abort_unless($task, 403, 'No tienes acceso a esta tarea.');
        }

        $session = $user->focusSessions()->create([
            'task_id'          => $data['task_id'] ?? null,
            'duration_minutes' => $data['duration_minutes'],
            'started_at'       => now(),
            'status'           => 'running',
        ]);

        return response()->json(['id' => $session->id]);
    }

    // Completar sesión con notas (y crear entrada opcional)
    public function complete(Request $request, FocusSession $session)
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'notes'        => 'nullable|string|max:1000',
            'create_entry' => 'boolean',
            'entry_title'  => 'nullable|string|max:255',
        ]);

        $session->update([
            'notes'    => $data['notes'] ?? null,
            'ended_at' => now(),
            'status'   => 'completed',
        ]);

        // Actualizar tarea a in_progress si estaba pendiente
        if ($session->task_id) {
            $task = $request->user()->tasks()->find($session->task_id);
            if ($task && $task->status === 'pending') {
                $task->update(['status' => 'in_progress']);
            }
        }

        // Crear entrada del registro si se solicitó
        if (!empty($data['create_entry']) && !empty($data['entry_title'])) {
            $user  = $request->user();
            $entry = $user->entries()->create([
                'title'      => $data['entry_title'],
                'content'    => $data['notes'] ?? '',
                'type'       => 'general',
                'entry_date' => today(),
                'entry_time' => now()->format('H:i:s'),
                'project_id' => $session->task ? $session->task->project_id : null,
            ]);

            if ($session->task_id) {
                $session->task ? $session->task->update : null(['entry_id' => $entry->id]);
            }
        }

        return response()->json(['ok' => true]);
    }

    // Cancelar sesión
    public function cancel(Request $request, FocusSession $session)
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        $session->update([
            'ended_at' => now(),
            'status'   => 'cancelled',
        ]);

        return response()->json(['ok' => true]);
    }
}
