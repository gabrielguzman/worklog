<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        // Estadísticas
        $stats = [
            'total_tasks'           => $user->tasks()->count(),
            'completed_tasks'       => $user->tasks()->where('status', 'done')->count(),
            'total_entries'         => $user->entries()->count(),
            'total_focus_minutes'   => $user->focusSessions()->where('status', 'completed')->sum('duration_minutes'),
            'focus_sessions'        => $user->focusSessions()->where('status', 'completed')->count(),
            'total_projects'        => $user->projects()->count(),
            'total_tags'            => $user->tags()->count(),
            'streak_days'           => $this->calculateStreak($user),
        ];

        // Productividad últimos 7 días
        $productivityChart = collect(range(6, 0))->map(function ($daysAgo) use ($user) {
            $date = now()->subDays($daysAgo);
            $tasks = $user->tasks()->whereDate('completed_at', $date->toDateString())->where('status', 'done')->count();
            $entries = $user->entries()->whereDate('entry_date', $date->toDateString())->count();
            $minutes = $user->focusSessions()->whereDate('started_at', $date->toDateString())->where('status', 'completed')->sum('duration_minutes');

            return [
                'date'    => $date->format('M d'),
                'tasks'   => $tasks,
                'entries' => $entries,
                'minutes' => $minutes,
            ];
        });

        // Top projects
        $topProjects = $user->tasks()
            ->with('project:id,name,color')
            ->where('status', 'done')
            ->get()
            ->groupBy('project_id')
            ->map(function ($tasks, $projectId) {
                $project = $tasks->first()->project;
                return [
                    'name'  => $project ? $project->name : 'Sin proyecto',
                    'color' => $project ? $project->color : '#999',
                    'count' => $tasks->count(),
                ];
            })
            ->sort(function ($a, $b) {
                return $b['count'] <=> $a['count'];
            })
            ->take(5)
            ->values();

        // Badges/Logros
        $badges = $this->calculateBadges($user, $stats);

        // Actividad reciente
        $recentActivity = $this->getRecentActivity($user);

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail'    => $user instanceof MustVerifyEmail,
            'status'             => session('status'),
            'stats'              => $stats,
            'productivityChart'  => $productivityChart,
            'topProjects'        => $topProjects,
            'badges'             => $badges,
            'recentActivity'     => $recentActivity,
        ]);
    }

    private function calculateStreak($user): int
    {
        $streak = 0;
        $date = now();

        while (true) {
            $count = $user->entries()
                ->whereDate('entry_date', $date->toDateString())
                ->count();

            if ($count === 0) break;

            $streak++;
            $date = $date->subDay();
        }

        return $streak;
    }

    private function calculateBadges($user, $stats): array
    {
        $badges = [];

        // Primeras completadas
        if ($stats['completed_tasks'] >= 1) {
            $badges[] = ['id' => 'first-task', 'name' => 'Primer paso', 'icon' => '🏁', 'description' => 'Completaste tu primera tarea'];
        }

        // 10 tareas
        if ($stats['completed_tasks'] >= 10) {
            $badges[] = ['id' => 'ten-tasks', 'name' => 'En marcha', 'icon' => '⚡', 'description' => '10 tareas completadas'];
        }

        // 50 tareas
        if ($stats['completed_tasks'] >= 50) {
            $badges[] = ['id' => 'fifty-tasks', 'name' => 'Productivo', 'icon' => '🚀', 'description' => '50 tareas completadas'];
        }

        // 100 tareas
        if ($stats['completed_tasks'] >= 100) {
            $badges[] = ['id' => 'hundred-tasks', 'name' => 'Leyenda', 'icon' => '👑', 'description' => '100 tareas completadas'];
        }

        // Focus warrior
        if ($stats['total_focus_minutes'] >= 500) {
            $badges[] = ['id' => 'focus-warrior', 'name' => 'Guerrero del Focus', 'icon' => '🍅', 'description' => '500+ minutos de enfoque'];
        }

        // Streak
        if ($stats['streak_days'] >= 7) {
            $badges[] = ['id' => 'seven-day-streak', 'name' => 'En racha', 'icon' => '🔥', 'description' => '7 días consecutivos activo'];
        }

        return $badges;
    }

    private function getRecentActivity($user): array
    {
        $activities = [];

        // Últimas tareas completadas
        $tasks = $user->tasks()
            ->where('status', 'done')
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($tasks as $task) {
            $activities[] = [
                'type'      => 'task',
                'action'    => 'Completó tarea',
                'title'     => $task->title,
                'timestamp' => $task->completed_at->diffForHumans(),
                'icon'      => '✓',
                'color'     => 'green',
            ];
        }

        // Últimas entradas
        $entries = $user->entries()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($entries as $entry) {
            $activities[] = [
                'type'      => 'entry',
                'action'    => 'Registró entrada',
                'title'     => $entry->title,
                'timestamp' => $entry->created_at->diffForHumans(),
                'icon'      => '📝',
                'color'     => 'blue',
            ];
        }

        // Últimas sesiones de focus
        $sessions = $user->focusSessions()
            ->where('status', 'completed')
            ->orderBy('ended_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($sessions as $session) {
            $activities[] = [
                'type'      => 'focus',
                'action'    => 'Sesión de enfoque',
                'title'     => $session->task ? $session->task->title : 'Sesión general',
                'subtitle'  => $session->duration_minutes . ' min',
                'timestamp' => $session->ended_at->diffForHumans(),
                'icon'      => '🍅',
                'color'     => 'orange',
            ];
        }

        return collect($activities)
            ->sort(function ($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            })
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
