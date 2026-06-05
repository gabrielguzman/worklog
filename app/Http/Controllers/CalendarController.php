<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Obtener tareas del usuario
        $tasks = $user->tasks()
            ->with(['project', 'focusSessions'])
            ->get();

        // Convertir a eventos para FullCalendar
        $events = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'start' => $task->due_date ?: null,
                'end' => $task->due_date ?: null,
                'backgroundColor' => $this->getPriorityColor($task->priority),
                'borderColor' => $this->getPriorityColor($task->priority),
                'textColor' => '#fff',
                'extendedProps' => [
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'project' => $task->project ? $task->project->name : null,
                    'description' => $task->description,
                    'isDone' => $task->status === 'done',
                    'isOverdue' => $task->is_overdue,
                ],
            ];
        })->filter(fn($event) => $event['start'])->values();

        return Inertia::render('Calendar/Index', [
            'events' => $events,
            'projects' => $user->projects,
        ]);
    }

    public function updateDueDate(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'due_date' => 'required|date',
        ]);

        $task->update($validated);

        return back();
    }

    private function getPriorityColor($priority)
    {
        switch ($priority) {
            case 'urgent': return '#EF4444';
            case 'high': return '#F97316';
            case 'medium': return '#FBBF24';
            case 'low': return '#D1D5DB';
            default: return '#3B82F6';
        }
    }
}
