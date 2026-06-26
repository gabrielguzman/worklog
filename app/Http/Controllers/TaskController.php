<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Task;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->tasks()
            ->with(['project:id,name,color', 'tags:id,name,color', 'entry:id,title', 'parentTask:id,title'])
            ->withCount([
                'focusSessions',
                'subtasks',
                'subtasks as subtasks_done_count' => fn($q) => $q->where('status', 'done'),
            ]);

        if ($search = $request->get('search')) {
            $query->where(
                fn($q) => $q
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            );
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        } else {
            // Por defecto, ocultar las hechas
            if (!$request->boolean('show_done')) {
                $query->where('status', '!=', 'done');
            }
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($project = $request->get('project_id')) {
            $query->where('project_id', $project);
        }

        if ($tag = $request->get('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('name', $tag));
        }

        if ($request->boolean('overdue')) {
            $query->whereNotNull('due_date')
                ->where('due_date', '<', Carbon::today())
                ->where('status', '!=', 'done');
        }

        $tasks = $query
            ->orderByRaw("
                CASE status
                    WHEN 'in_progress' THEN 1
                    WHEN 'pending' THEN 2
                    WHEN 'done' THEN 3
                    ELSE 4
                END
            ")
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
            ->orderBy('sort_order', 'asc')
            ->paginate(30)
            ->withQueryString()
            ->through(fn($t) => [
                'id'          => $t->id,
                'title'       => $t->title,
                'description' => $t->description,
                'priority'    => $t->priority,
                'status'      => $t->status,
                'due_date'    => $t->due_date ? $t->due_date->format('Y-m-d') : null,
                'due_label'   => $t->due_date ? $t->due_date->locale('es')->isoFormat('D MMM') : null,
                'is_overdue'  => $t->due_date && $t->due_date->isPast() && $t->status !== 'done',
                'focus_sessions_count' => $t->focus_sessions_count,
                'parent_task' => $t->parentTask ? ['id' => $t->parentTask->id, 'title' => $t->parentTask->title] : null,
                'subtasks_count' => $t->subtasks_count,
                'subtasks_done'  => $t->subtasks_done_count,
                'project'     => $t->project ? ['id' => $t->project->id, 'name' => $t->project->name, 'color' => $t->project->color] : null,
                'tags'        => $t->tags->map(fn($tag) => ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color]),
                'entry'       => $t->entry ? ['id' => $t->entry->id, 'title' => $t->entry->title] : null,
            ]);

        // Contadores de resumen
        $summary = [
            'total'       => $user->tasks()->count(),
            'pending'     => $user->tasks()->where('status', 'pending')->count(),
            'in_progress' => $user->tasks()->where('status', 'in_progress')->count(),
            'done_today'  => $user->tasks()->whereDate('completed_at', Carbon::today())->count(),
            'overdue'     => $user->tasks()
                ->whereNotNull('due_date')
                ->where('due_date', '<', Carbon::today())
                ->where('status', '!=', 'done')
                ->count(),
        ];

        return Inertia::render('Tasks/Index', [
            'tasks'    => $tasks,
            'summary'  => $summary,
            'projects' => $user->projects()->where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'tags'     => $user->tags()->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'filters'  => $request->only('search', 'status', 'priority', 'project_id', 'tag', 'show_done', 'overdue'),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        $entry = $request->get('entry')
            ? Entry::where('user_id', $user->id)->find($request->get('entry'))
            : null;

        $templateData = null;

        if ($templateId = $request->get('template')) {
            $template = Template::where('user_id', $user->id)
                ->where('type', 'task')
                ->find($templateId);

            $templateData = $template ? $template->fields : null;
        }

        return Inertia::render('Tasks/Form', [
            'projects'     => $user->projects()->where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'tags'         => $user->tags()->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'entries'      => $user->entries()->orderBy('entry_date', 'desc')->limit(20)->get(['id', 'title', 'entry_date']),
            'templates'    => $user->templates()->where('type', 'task')->where('is_active', true)->orderBy('name', 'asc')->get(),
            'templateData' => $templateData,
            'defaults'     => [
                'entry_id'   => $entry ? $entry->id : null,
                'project_id' => $entry ? $entry->project_id : null,
                'due_date'   => now()->addDays(3)->format('Y-m-d'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'priority'            => 'required|in:low,medium,high,urgent',
            'status'              => 'required|in:pending,in_progress,done',
            'due_date'            => 'nullable|date',
            'project_id'          => 'nullable|exists:projects,id',
            'entry_id'            => 'nullable|exists:entries,id',
            'parent_task_id'      => 'nullable|exists:tasks,id',
            'recurrence_type'     => 'nullable|in:none,daily,weekly,monthly',
            'recurrence_interval' => 'nullable|integer|min:1|max:365',
            'recurrence_ends_at'  => 'nullable|date',
            'tags'                => 'nullable|array',
            'tags.*'              => 'exists:tags,id',
        ]);

        // Bloquear subtareas anidadas
        if (!empty($data['parent_task_id'])) {
            $parent = Task::find($data['parent_task_id']);
            abort_if($parent && $parent->parent_task_id !== null, 422, 'Las subtareas no pueden tener subtareas.');
        }

        $task = $request->user()->tasks()->create([
            ...$data,
            'completed_at'        => $data['status'] === 'done' ? now() : null,
            'recurrence_type'     => $data['recurrence_type'] ?? 'none',
            'recurrence_interval' => $data['recurrence_interval'] ?? 1,
        ]);

        if (!empty($data['tags'])) {
            $task->tags()->sync($data['tags']);
        }

        $redirect = $request->get('entry_id')
            ? redirect()->route('entries.show', $request->get('entry_id'))
            : redirect()->route('tasks.show', $task);

        return $redirect->with('success', 'Tarea creada.');
    }

    public function show(Request $request, Task $task)
    {
        abort_unless($task->user_id === $request->user()->id, 403);

        $task->load([
            'project:id,name,color',
            'tags:id,name,color',
            'entry:id,title,entry_date',
            'attachments',
            'focusSessions' => fn($q) => $q->orderBy('started_at', 'desc')->limit(10),
            'subtasks' => fn($q) => $q->with(['tags:id,name,color']),
            'parentTask:id,title',
        ]);

        $totalFocusMinutes = $task->focusSessions->sum('duration_minutes');

        return Inertia::render('Tasks/Show', [
            'task' => [
                'id'           => $task->id,
                'title'        => $task->title,
                'description'  => $task->description,
                'priority'     => $task->priority,
                'status'       => $task->status,
                'due_date'     => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                'due_label'    => $task->due_date ? $task->due_date->locale('es')->isoFormat('dddd D [de] MMMM') : null,
                'is_overdue'   => $task->due_date && $task->due_date->isPast() && $task->status !== 'done',
                'completed_at' => $task->completed_at ? $task->completed_at->locale('es')->isoFormat('D MMM YYYY, HH:mm') : null,
                'created_at'   => $task->created_at->locale('es')->diffForHumans(),
                'project'      => $task->project,
                'tags'         => $task->tags,
                'entry'        => $task->entry ? [
                    'id'    => $task->entry->id,
                    'title' => $task->entry->title,
                    'date'  => $task->entry->entry_date->locale('es')->isoFormat('D MMM'),
                ] : null,
                'parent_task'  => $task->parentTask ? [
                    'id'    => $task->parentTask->id,
                    'title' => $task->parentTask->title,
                ] : null,
                'subtasks'     => $task->subtasks->map(fn($s) => [
                    'id'           => $s->id,
                    'title'        => $s->title,
                    'status'       => $s->status,
                    'priority'     => $s->priority,
                    'due_date'     => $s->due_date ? $s->due_date->format('Y-m-d') : null,
                    'due_label'    => $s->due_date ? $s->due_date->locale('es')->isoFormat('D MMM') : null,
                    'is_overdue'   => $s->due_date && $s->due_date->isPast() && $s->status !== 'done',
                    'tags'         => $s->tags->map(fn($t) => [
                        'id'    => $t->id,
                        'name'  => $t->name,
                        'color' => $t->color,
                    ]),
                    'completed_at' => $s->completed_at ? $s->completed_at->toISOString() : null,
                ]),
                'subtask_progress'    => $task->subtaskProgress(),
                'recurrence_type'     => $task->recurrence_type,
                'recurrence_interval' => $task->recurrence_interval,
                'recurrence_ends_at'  => $task->recurrence_ends_at ? $task->recurrence_ends_at->format('Y-m-d') : null,
                'attachments'         => $task->attachments->map(fn($a) => [
                    'id'            => $a->id,
                    'original_name' => $a->original_name,
                    'mime_type'     => $a->mime_type,
                    'size_humans'   => $a->size_for_humans,
                    'is_image'      => $a->is_image,
                    'url'           => $a->url,
                ]),
                'focus_sessions'      => $task->focusSessions->map(fn($s) => [
                    'id'               => $s->id,
                    'duration_minutes' => $s->duration_minutes,
                    'started_at'       => $s->started_at->locale('es')->isoFormat('D MMM, HH:mm'),
                    'notes'            => $s->notes,
                    'status'           => $s->status,
                ]),
                'total_focus_minutes' => $totalFocusMinutes,
            ],
        ]);
    }

    public function edit(Request $request, Task $task)
    {
        abort_unless($task->user_id === $request->user()->id, 403);

        $task->load(['tags:id']);
        $user = $request->user();

        return Inertia::render('Tasks/Form', [
            'task'     => [
                'id'                  => $task->id,
                'title'               => $task->title,
                'description'         => $task->description,
                'priority'            => $task->priority,
                'status'              => $task->status,
                'due_date'            => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                'project_id'          => $task->project_id,
                'entry_id'            => $task->entry_id,
                'tags'                => $task->tags->pluck('id'),
                'recurrence_type'     => $task->recurrence_type,
                'recurrence_interval' => $task->recurrence_interval,
                'recurrence_ends_at'  => $task->recurrence_ends_at ? $task->recurrence_ends_at->format('Y-m-d') : null,
            ],
            'projects' => $user->projects()->where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'tags'     => $user->tags()->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'entries'  => $user->entries()->orderBy('entry_date', 'desc')->limit(20)->get(['id', 'title', 'entry_date']),
            'defaults' => [],
        ]);
    }

    public function update(Request $request, Task $task)
    {
        abort_unless($task->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'priority'            => 'required|in:low,medium,high,urgent',
            'status'              => 'required|in:pending,in_progress,done',
            'due_date'            => 'nullable|date',
            'project_id'          => 'nullable|exists:projects,id',
            'entry_id'            => 'nullable|exists:entries,id',
            'recurrence_type'     => 'nullable|in:none,daily,weekly,monthly',
            'recurrence_interval' => 'nullable|integer|min:1|max:365',
            'recurrence_ends_at'  => 'nullable|date',
            'tags'                => 'nullable|array',
            'tags.*'              => 'exists:tags,id',
        ]);

        // Registrar completed_at al marcar done
        if ($data['status'] === 'done' && $task->status !== 'done') {
            $data['completed_at'] = now();
        } elseif ($data['status'] !== 'done') {
            $data['completed_at'] = null;
        }

        $task->update([
            ...$data,
            'recurrence_type'     => $data['recurrence_type'] ?? 'none',
            'recurrence_interval' => $data['recurrence_interval'] ?? 1,
        ]);

        $task->tags()->sync($data['tags'] ?? []);

        return redirect()->route('tasks.show', $task)->with('success', 'Tarea actualizada.');
    }

    public function destroy(Request $request, Task $task)
    {
        abort_unless($task->user_id === $request->user()->id, 403);

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Tarea eliminada.');
    }

    // PATCH /tasks/{task}/toggle — alterna done ↔ pending sin abrir el form
    public function toggle(Request $request, Task $task)
    {
        abort_unless($task->user_id === $request->user()->id, 403);

        if ($task->status === 'done') {
            $task->update(['status' => 'pending', 'completed_at' => null]);
        } else {
            $task->update(['status' => 'done', 'completed_at' => now()]);

            if ($task->isRecurrent()) {
                $task->spawnNextRecurrence();
            }
        }

        return back();
    }

    // POST /tasks/reorder — actualizar orden de múltiples tareas
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'tasks'              => 'required|array',
            'tasks.*.id'         => 'required|integer',
            'tasks.*.sort_order' => 'required|integer',
        ]);

        $user = $request->user();

        foreach ($data['tasks'] as $item) {
            $task = $user->tasks()->find($item['id']);

            if ($task) {
                $task->update(['sort_order' => $item['sort_order']]);
            }
        }

        return response()->json(['ok' => true]);
    }

    // POST /tasks/{task}/subtasks — crear subtarea
    public function storeSubtask(Request $request, Task $task)
    {
        abort_unless($task->user_id === $request->user()->id, 403);
        abort_if($task->parent_task_id !== null, 422, 'No se pueden crear subtareas de subtareas.');

        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'status'   => 'nullable|in:pending,in_progress,done',
        ]);

        $request->user()->tasks()->create([
            'title'          => $data['title'],
            'priority'       => $data['priority'] ?? 'medium',
            'status'         => $data['status'] ?? 'pending',
            'due_date'       => $data['due_date'] ?? null,
            'project_id'     => $task->project_id,
            'parent_task_id' => $task->id,
            'completed_at'   => ($data['status'] ?? 'pending') === 'done' ? now() : null,
        ]);

        return back()->with('success', 'Subtarea creada.');
    }

    // GET /tasks/kanban — vista kanban con 3 columnas
    public function kanban(Request $request)
    {
        $user = $request->user();

        $query = $user->tasks()
            ->with(['project:id,name,color', 'tags:id,name,color'])
            ->withCount([
                'subtasks',
                'subtasks as subtasks_done_count' => fn($q) => $q->where('status', 'done'),
            ])
            ->whereNull('parent_task_id');

        if ($project = $request->get('project_id')) {
            $query->where('project_id', $project);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($tag = $request->get('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('name', $tag));
        }

        $allTasks = $query
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
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn($t) => [
                'id'              => $t->id,
                'title'           => $t->title,
                'priority'        => $t->priority,
                'status'          => $t->status,
                'due_date'        => $t->due_date ? $t->due_date->format('Y-m-d') : null,
                'due_label'       => $t->due_date ? $t->due_date->locale('es')->isoFormat('D MMM') : null,
                'is_overdue'      => $t->due_date && $t->due_date->isPast() && $t->status !== 'done',
                'project'         => $t->project ? ['id' => $t->project->id, 'name' => $t->project->name, 'color' => $t->project->color] : null,
                'tags'            => $t->tags->map(fn($tag) => [
                    'id'    => $tag->id,
                    'name'  => $tag->name,
                    'color' => $tag->color,
                ]),
                'subtasks_count'  => $t->subtasks_count,
                'subtasks_done'   => $t->subtasks_done_count,
                'recurrence_type' => $t->recurrence_type,
            ]);

        $columns = [
            'pending'     => $allTasks->where('status', 'pending')->values(),
            'in_progress' => $allTasks->where('status', 'in_progress')->values(),
            'done'        => $allTasks->where('status', 'done')->values(),
        ];

        return Inertia::render('Tasks/Kanban', [
            'columns'  => $columns,
            'projects' => $user->projects()->where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'tags'     => $user->tags()->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'filters'  => $request->only('project_id', 'priority', 'tag'),
        ]);
    }

    // PATCH /tasks/{task}/status — cambiar status desde Kanban
    public function updateStatus(Request $request, Task $task)
    {
        abort_unless($task->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'status' => 'required|in:pending,in_progress,done',
        ]);

        $wasNotDone = $task->status !== 'done';
        $becomingDone = $data['status'] === 'done';

        $task->update([
            'status'       => $data['status'],
            'completed_at' => $becomingDone && $wasNotDone ? now() : ($data['status'] !== 'done' ? null : $task->completed_at),
        ]);

        if ($becomingDone && $wasNotDone && $task->isRecurrent()) {
            $task->spawnNextRecurrence();
        }

        return response()->json(['ok' => true]);
    }

    // PATCH /tasks/bulk-update — cambiar estado/prioridad/proyecto en lote
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'task_ids'   => 'required|array|min:1',
            'task_ids.*' => 'required|integer',
            'status'     => 'nullable|in:pending,in_progress,done',
            'priority'   => 'nullable|in:low,medium,high,urgent',
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);

        $user = $request->user();
        $updated = 0;

        foreach ($data['task_ids'] as $taskId) {
            $task = $user->tasks()->find($taskId);

            if (!$task) {
                continue;
            }

            $updateData = [];

            if ($data['status'] ?? null) {
                $updateData['status'] = $data['status'];
                $wasNotDone = $task->status !== 'done';
                $becomingDone = $data['status'] === 'done';

                if ($becomingDone && $wasNotDone) {
                    $updateData['completed_at'] = now();
                } elseif ($data['status'] !== 'done') {
                    $updateData['completed_at'] = null;
                }
            }

            if ($data['priority'] ?? null) {
                $updateData['priority'] = $data['priority'];
            }

            if ($data['project_id'] ?? null) {
                $updateData['project_id'] = $data['project_id'];
            }

            if (!empty($updateData)) {
                $task->update($updateData);
                $updated++;

                // Generar próxima recurrencia si fue marcada como done
                if (($data['status'] ?? null) === 'done' && $task->isRecurrent()) {
                    $task->spawnNextRecurrence();
                }
            }
        }

        return response()->json([
            'ok'      => true,
            'updated' => $updated,
            'message' => "$updated tarea(s) actualizada(s).",
        ]);
    }

    // DELETE /tasks/bulk-delete — eliminar múltiples tareas
    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'task_ids'   => 'required|array|min:1',
            'task_ids.*' => 'required|integer',
        ]);

        $user = $request->user();
        $deleted = 0;

        foreach ($data['task_ids'] as $taskId) {
            $task = $user->tasks()->find($taskId);

            if ($task) {
                $task->delete();
                $deleted++;
            }
        }

        return response()->json([
            'ok'      => true,
            'deleted' => $deleted,
            'message' => "$deleted tarea(s) eliminada(s).",
        ]);
    }
}
