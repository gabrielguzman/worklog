<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class ReportController extends Controller
{
    // Daily report
    public function daily(Request $request)
    {
        $user = $request->user();
        $date = Carbon::parse($request->get('date', today()))->startOfDay();

        // Métricas
        $entriesCount = $user->entries()
            ->whereDate('entry_date', $date)
            ->count();

        $tasksCompleted = $user->tasks()
            ->where(fn($q) => $q->whereDate('completed_at', $date))
            ->count();

        $tasksPending = $user->tasks()
            ->where('status', '!=', 'done')
            ->count();

        $focusMinutesToday = $user->focusSessions()
            ->whereDate('started_at', $date)
            ->where('status', 'completed')
            ->sum('duration_minutes');

        // Entradas del día
        $entries = $user->entries()
            ->with(['project:id,name,color', 'tags:id,name,color'])
            ->whereDate('entry_date', $date)
            ->orderBy('entry_time', 'asc')
            ->get()
            ->map(fn($e) => [
                'id'        => $e->id,
                'title'     => $e->title,
                'type'      => $e->type,
                'time'      => $e->entry_time,
                'project'   => $e->project ? ['name' => $e->project->name, 'color' => $e->project->color] : null,
                'tags'      => $e->tags->map(fn($t) => ['name' => $t->name, 'color' => $t->color]),
            ]);

        // Tareas completadas hoy
        $tasksCompletedList = $user->tasks()
            ->with(['project:id,name,color', 'tags:id,name,color'])
            ->where('status', 'done')
            ->whereDate('completed_at', $date)
            ->get()
            ->map(fn($t) => [
                'id'       => $t->id,
                'title'    => $t->title,
                'priority' => $t->priority,
                'project'  => $t->project ? ['name' => $t->project->name, 'color' => $t->project->color] : null,
                'tags'     => $t->tags->map(fn($tag) => ['name' => $tag->name, 'color' => $tag->color]),
            ]);

        // Sesiones de enfoque
        $focusSessions = $user->focusSessions()
            ->with(['task:id,title,project_id'])
            ->whereDate('started_at', $date)
            ->orderBy('started_at', 'asc')
            ->get()
            ->map(fn($s) => [
                'id'               => $s->id,
                'duration_minutes' => $s->duration_minutes,
                'status'           => $s->status,
                'notes'            => $s->notes,
                'task'             => $s->task ? ['id' => $s->task->id, 'title' => $s->task->title] : null,
            ]);

        // Estadísticas por proyecto
        $projectStats = $user->tasks()
            ->with('project')
            ->where('status', 'done')
            ->whereDate('completed_at', $date)
            ->get()
            ->groupBy('project_id')
            ->map(fn($tasks, $projectId) => [
                'project_id'   => $projectId,
                'project_name' => $tasks->first()->project ? $tasks->first()->project->name : null ?? 'Sin proyecto',
                'count'        => $tasks->count(),
            ])
            ->values();

        return Inertia::render('Reports/Daily', [
            'date'              => $date->format('Y-m-d'),
            'dateFormatted'     => $date->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'),
            'metrics'           => [
                'entries_count'      => $entriesCount,
                'tasks_completed'    => $tasksCompleted,
                'tasks_pending'      => $tasksPending,
                'focus_minutes'      => $focusMinutesToday,
            ],
            'entries'           => $entries,
            'tasksCompleted'    => $tasksCompletedList,
            'focusSessions'     => $focusSessions,
            'projectStats'      => $projectStats,
        ]);
    }

    // Weekly report
    public function weekly(Request $request)
    {
        $user = $request->user();
        $startOfWeek = Carbon::parse($request->get('week', today()))->startOfWeek();
        $endOfWeek = $startOfWeek->clone()->endOfWeek();

        // Datos por día
        $dailyStats = collect(range(0, 6))->map(function ($daysFromStart) use ($startOfWeek, $user) {
            $date = $startOfWeek->clone()->addDays($daysFromStart);

            return [
                'date'              => $date->format('Y-m-d'),
                'label'             => $date->locale('es')->isoFormat('ddd D'),
                'entries_count'     => $user->entries()->whereDate('entry_date', $date)->count(),
                'tasks_completed'   => $user->tasks()->where('status', 'done')->whereDate('completed_at', $date)->count(),
                'focus_minutes'     => $user->focusSessions()->whereDate('started_at', $date)->where('status', 'completed')->sum('duration_minutes'),
            ];
        });

        // Totales semanales
        $totalEntries = $user->entries()
            ->whereBetween('entry_date', [$startOfWeek, $endOfWeek])
            ->count();

        $totalTasksCompleted = $user->tasks()
            ->where('status', 'done')
            ->whereBetween('completed_at', [$startOfWeek, $endOfWeek])
            ->count();

        $totalFocusMinutes = $user->focusSessions()
            ->whereBetween('started_at', [$startOfWeek, $endOfWeek])
            ->where('status', 'completed')
            ->sum('duration_minutes');

        // Tareas más completadas por proyecto
        $projectStats = $user->tasks()
            ->with('project')
            ->where('status', 'done')
            ->whereBetween('completed_at', [$startOfWeek, $endOfWeek])
            ->get()
            ->groupBy('project_id')
            ->map(fn($tasks, $projectId) => [
                'project_id'   => $projectId,
                'project_name' => $tasks->first()->project ? $tasks->first()->project->name : null ?? 'Sin proyecto',
                'count'        => $tasks->count(),
            ])
            ->sort(function ($a, $b) {
                return $b['count'] <=> $a['count'];
            })
            ->values();

        // Tipos de entradas
        $entryTypes = $user->entries()
            ->whereBetween('entry_date', [$startOfWeek, $endOfWeek])
            ->get()
            ->groupBy('type')
            ->map(fn($entries, $type) => [
                'type'  => $type,
                'count' => $entries->count(),
            ])
            ->values();

        // Top tags
        $topTags = $user->tags()
            ->withCount(['tasks as tasks_completed_count' => fn($q) => $q->where('status', 'done')->whereBetween('completed_at', [$startOfWeek, $endOfWeek])])
            ->withCount(['entries as entries_count' => fn($q) => $q->whereBetween('entry_date', [$startOfWeek, $endOfWeek])])
            ->having('tasks_completed_count', '>', 0)
            ->orHaving('entries_count', '>', 0)
            ->orderBy('tasks_completed_count', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($tag) => [
                'name'               => $tag->name,
                'tasks_completed'    => $tag->tasks_completed_count,
                'entries'            => $tag->entries_count,
            ]);

        return Inertia::render('Reports/Weekly', [
            'weekStart'          => $startOfWeek->format('Y-m-d'),
            'weekEnd'            => $endOfWeek->format('Y-m-d'),
            'weekFormatted'      => $startOfWeek->locale('es')->isoFormat('D MMM') . ' - ' . $endOfWeek->locale('es')->isoFormat('D MMM YYYY'),
            'metrics'            => [
                'total_entries'      => $totalEntries,
                'total_tasks_completed' => $totalTasksCompleted,
                'total_focus_minutes' => $totalFocusMinutes,
                'avg_tasks_per_day'  => round($totalTasksCompleted / 7, 1),
                'avg_focus_minutes_per_day' => round($totalFocusMinutes / 7, 0),
            ],
            'dailyStats'         => $dailyStats,
            'projectStats'       => $projectStats,
            'entryTypes'         => $entryTypes,
            'topTags'            => $topTags,
        ]);
    }
}
