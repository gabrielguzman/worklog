<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today();

        // Métricas
        $entriestoday      = $user->entries()->whereDate('entry_date', $today)->count();
        $tasksPending      = $user->tasks()->whereIn('status', ['pending', 'in_progress'])->count();
        $filesThisWeek     = $user->attachments()->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()])->count();
        $tasksCompletedToday = $user->tasks()->whereDate('completed_at', $today)->count();

        // Tareas pendientes (top 8, priorizadas)
        $pendingTasks = $user->tasks()
            ->with(['project:id,name,color', 'tags:id,name,color'])
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
            ->limit(8)
            ->get()
            ->get()
            ->map(fn($t) => [
                'id'       => $t->id,
                'title'    => $t->title,
                'priority' => $t->priority,
                'status'   => $t->status,
                'due_date' => $t->due_date ? $t->due_date->format('d/m') : null,
                'is_overdue' => $t->due_date && $t->due_date->isPast(),
                'project'  => $t->project ? ['name' => $t->project->name, 'color' => $t->project->color] : null,
                'tags'     => $t->tags->map(fn($tag) => ['name' => $tag->name, 'color' => $tag->color]),
            ]);

        // Entradas de hoy
        $todayEntries = $user->entries()
            ->with(['project:id,name,color', 'tags:id,name,color'])
            ->whereDate('entry_date', $today)
            ->orderBy('entry_time', 'asc')
            ->get()
            ->map(fn($e) => [
                'id'       => $e->id,
                'title'    => $e->title,
                'type'     => $e->type,
                'time'     => substr($e->entry_time, 0, 5),
                'is_pinned' => $e->is_pinned,
                'project'  => $e->project ? ['name' => $e->project->name, 'color' => $e->project->color] : null,
                'tags'     => $e->tags->map(fn($tag) => ['name' => $tag->name, 'color' => $tag->color]),
                'attachments_count' => $e->attachments()->count(),
            ]);

        // Archivos recientes
        $recentFiles = $user->attachments()
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(fn($a) => [
                'id'            => $a->id,
                'original_name' => $a->original_name,
                'mime_type'     => $a->mime_type,
                'size_humans'   => $a->size_for_humans,
                'created_at'    => $a->created_at->diffForHumans(),
            ]);

        // Actividad semanal (entradas por día, últimos 7 días)
        $weekActivity = collect(range(6, 0))->map(function ($daysAgo) use ($user) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'label' => $date->locale('es')->isoFormat('ddd'),
                'date'  => $date->format('Y-m-d'),
                'count' => $user->entries()->whereDate('entry_date', $date)->count(),
                'is_today' => $date->isToday(),
            ];
        });

        // Entradas recientes (últimas 5, excluyendo hoy)
        $recentEntries = $user->entries()
            ->with(['project:id,name,color'])
            ->whereDate('entry_date', '<', $today)
            ->orderBy('entry_date', 'desc')
            ->orderBy('entry_time', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($e) => [
                'id'      => $e->id,
                'title'   => $e->title,
                'type'    => $e->type,
                'date'    => $e->entry_date->locale('es')->isoFormat('D MMM'),
                'project' => $e->project ? ['name' => $e->project->name, 'color' => $e->project->color] : null,
            ]);

        // Actividad reciente (últimas 12 acciones)
        $recentActivity = collect()
            ->merge(
                $user->entries()
                    ->select('id', 'title', 'created_at', 'updated_at')
                    ->get()
                    ->map(fn($e) => [
                        'type'      => 'entry',
                        'id'        => $e->id,
                        'title'     => $e->title,
                        'action'    => $e->created_at && $e->created_at->diffInMinutes() < 1 ? 'creada' : 'actualizada',
                        'time'      => $e->updated_at->diffForHumans(),
                        'timestamp' => $e->updated_at,
                    ])
            )
            ->merge(
                $user->tasks()
                    ->select('id', 'title', 'status', 'updated_at')
                    ->get()
                    ->map(fn($t) => [
                        'type'      => 'task',
                        'id'        => $t->id,
                        'title'     => $t->title,
                        'action'    => $t->status === 'done' ? 'completada' : 'actualizada',
                        'time'      => $t->updated_at->diffForHumans(),
                        'timestamp' => $t->updated_at,
                    ])
            )
            ->sort(function ($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            })
            ->take(12)
            ->values();

        // Gráfico: Tareas completadas últimos 30 días
        $last30Days = collect(range(29, 0))->map(function ($daysAgo) use ($user) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'date'  => $date->format('M d'),
                'count' => $user->tasks()
                    ->where('status', 'done')
                    ->whereDate('completed_at', $date)
                    ->count(),
            ];
        });

        // Gráfico: Distribución de tareas por proyecto
        $projectDistribution = $user->tasks()
            ->with('project')
            ->where('status', '!=', 'done')
            ->get()
            ->groupBy('project_id')
            ->map(fn($tasks, $projectId) => [
                'name'  => $tasks->first()->project ? $tasks->first()->project->name : null ?? 'Sin proyecto',
                'count' => $tasks->count(),
            ])
            ->sort(function ($a, $b) {
                return $b['count'] <=> $a['count'];
            })
            ->values()
            ->take(5);

        // Gráfico: Distribución por prioridad
        $priorityDistribution = $user->tasks()
            ->where('status', '!=', 'done')
            ->get()
            ->groupBy('priority')
            ->map(fn($tasks, $priority) => [
                'priority' => $priority,
                'count'    => $tasks->count(),
            ])
            ->values();

        // Gráfico: Tiempo de enfoque últimos 7 días
        $focusLast7Days = collect(range(6, 0))->map(function ($daysAgo) use ($user) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'date'    => $date->format('ddd'),
                'minutes' => $user->focusSessions()
                    ->whereDate('started_at', $date)
                    ->where('status', 'completed')
                    ->sum('duration_minutes'),
            ];
        });

        return Inertia::render('Dashboard', [
            'metrics' => [
                'entries_today'        => $entriestoday,
                'tasks_pending'        => $tasksPending,
                'files_this_week'      => $filesThisWeek,
                'tasks_completed_today' => $tasksCompletedToday,
            ],
            'pendingTasks'  => $pendingTasks,
            'todayEntries'  => $todayEntries,
            'recentEntries' => $recentEntries,
            'recentFiles'   => $recentFiles,
            'weekActivity'  => $weekActivity,
            'recentActivity' => $recentActivity,
            'todayFormatted' => Carbon::today()->locale('es')->isoFormat('dddd D [de] MMMM'),
            'chartData' => [
                'last30Days'            => $last30Days,
                'projectDistribution'   => $projectDistribution,
                'priorityDistribution'  => $priorityDistribution,
                'focusLast7Days'        => $focusLast7Days,
            ],
        ]);
    }
}
