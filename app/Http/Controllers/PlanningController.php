<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class PlanningController extends Controller
{
    public function week(Request $request)
    {
        $user = $request->user();
        $startOfWeek = Carbon::parse($request->get('week', today()))->startOfWeek();
        $endOfWeek = $startOfWeek->clone()->endOfWeek();

        // Tareas sin asignar (sin due_date)
        $unassignedTasks = $user->tasks()
            ->with(['project:id,name,color', 'tags:id,name,color'])
            ->where('status', '!=', 'done')
            ->whereNull('due_date')
            ->orderByRaw("FIELD(priority,'urgent','high','medium','low')")
            ->get()
            ->map(fn($t) => [
                'id'        => $t->id,
                'title'     => $t->title,
                'priority'  => $t->priority,
                'status'    => $t->status,
                'project'   => $t->project,
                'tags'      => $t->tags,
            ]);

        // Tareas por día de la semana
        $weekDays = collect(range(0, 6))->map(function ($daysFromStart) use ($startOfWeek, $user) {
            $date = $startOfWeek->clone()->addDays($daysFromStart);

            $tasks = $user->tasks()
                ->with(['project:id,name,color', 'tags:id,name,color'])
                ->where('status', '!=', 'done')
                ->whereDate('due_date', $date)
                ->orderByRaw("FIELD(priority,'urgent','high','medium','low')")
                ->get()
                ->map(fn($t) => [
                    'id'        => $t->id,
                    'title'     => $t->title,
                    'priority'  => $t->priority,
                    'status'    => $t->status,
                    'due_date'  => $t->due_date ? $t->due_date->format('Y-m-d') : null,
                    'project'   => $t->project,
                    'tags'      => $t->tags,
                ]);

            return [
                'date'       => $date->format('Y-m-d'),
                'label'      => $date->locale('es')->isoFormat('ddd D'),
                'dateFormat' => $date->locale('es')->isoFormat('dddd D MMMM'),
                'tasks'      => $tasks,
                'taskCount'  => $tasks->count(),
            ];
        });

        // Proyectos y tags para filtros
        $projects = $user->projects()
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'color']);

        $tags = $user->tags()
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'color']);

        return Inertia::render('Planning/Week', [
            'weekStart'       => $startOfWeek->format('Y-m-d'),
            'weekEnd'         => $endOfWeek->format('Y-m-d'),
            'weekFormatted'   => $startOfWeek->locale('es')->isoFormat('D MMM') . ' - ' . $endOfWeek->locale('es')->isoFormat('D MMM YYYY'),
            'weekDays'        => $weekDays,
            'unassignedTasks' => $unassignedTasks,
            'projects'        => $projects,
            'tags'            => $tags,
        ]);
    }

    public function updateDueDate(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'task_id'  => 'required|exists:tasks,id',
            'due_date' => 'required|date',
        ]);

        $task = $user->tasks()->find($data['task_id']);
        abort_unless($task, 403);

        $task->update(['due_date' => $data['due_date']]);

        return response()->json(['ok' => true]);
    }
}
