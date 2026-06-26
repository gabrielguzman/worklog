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
            ->orderByRaw("
                CASE priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 5
                END
            ")
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

    // Historial completo de sesiones
    public function history(Request $request)
    {
        $user = $request->user();

        $query = $user->focusSessions()
            ->with(['task:id,title,project_id', 'task.project:id,name,color']);

        // Filtros
        if ($search = $request->get('search')) {
            $query->where(
                fn($q) => $q
                    ->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('task', fn($tq) => $tq->where('title', 'like', "%{$search}%"))
            );
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->get('from')) {
            $query->whereDate('started_at', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->whereDate('started_at', '<=', $to);
        }

        $sessions = $query
            ->orderBy('started_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($s) => [
                'id'               => $s->id,
                'duration_minutes' => $s->duration_minutes,
                'started_at'       => $s->started_at->format('Y-m-d H:i'),
                'started_at_label' => $s->started_at->locale('es')->isoFormat('D MMM YYYY, HH:mm'),
                'ended_at'         => $s->ended_at ? $s->ended_at->format('Y-m-d H:i') : null,
                'notes'            => $s->notes,
                'status'           => $s->status,
                'task'             => $s->task ? [
                    'id'       => $s->task->id,
                    'title'    => $s->task->title,
                    'project'  => $s->task->project ? [
                        'id'    => $s->task->project->id,
                        'name'  => $s->task->project->name,
                        'color' => $s->task->project->color
                    ] : null,
                ] : null,
            ]);

        // Estadísticas
        $stats = [
            'total_sessions'      => $user->focusSessions()->count(),
            'completed_sessions'  => $user->focusSessions()->where('status', 'completed')->count(),
            'total_minutes'       => $user->focusSessions()->where('status', 'completed')->sum('duration_minutes'),
            'avg_duration'        => round($user->focusSessions()->where('status', 'completed')->avg('duration_minutes'), 1),
            'week_sessions'       => $user->focusSessions()->where('started_at', '>=', now()->startOfWeek())->where('status', 'completed')->count(),
            'week_minutes'        => $user->focusSessions()->where('started_at', '>=', now()->startOfWeek())->where('status', 'completed')->sum('duration_minutes'),
        ];

        return Inertia::render('Focus/History', [
            'sessions' => $sessions,
            'stats'    => $stats,
            'filters'  => $request->only('search', 'status', 'from', 'to'),
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
