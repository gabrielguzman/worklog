<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $projects = $user->projects()
            ->withCount([
                'entries',
                'tasks',
                'tasks as tasks_pending_count' => fn($q) => $q->where('status', '!=', 'done'),
                'tasks as tasks_done_count'    => fn($q) => $q->where('status', 'done'),
            ])
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn($p) => [
                'id'                  => $p->id,
                'name'                => $p->name,
                'color'               => $p->color,
                'description'         => $p->description,
                'is_active'           => $p->is_active,
                'entries_count'       => $p->entries_count,
                'tasks_count'         => $p->tasks_count,
                'tasks_pending_count' => $p->tasks_pending_count,
                'tasks_done_count'    => $p->tasks_done_count,
            ]);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'color'       => 'required|string|size:7',
            'description' => 'nullable|string|max:500',
        ]);

        $request->user()->projects()->create($data);

        return back()->with('success', 'Proyecto creado.');
    }

    public function update(Request $request, Project $project)
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'color'       => 'required|string|size:7',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
        ]);

        $project->update($data);

        return back()->with('success', 'Proyecto actualizado.');
    }

    public function destroy(Request $request, Project $project)
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        // Desvincular entradas y tareas antes de borrar
        $project->entries()->update(['project_id' => null]);
        $project->tasks()->update(['project_id' => null]);
        $project->delete();

        return back()->with('success', 'Proyecto eliminado.');
    }

    public function show(Request $request, Project $project)
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        // Entradas del proyecto
        $entries = $project->entries()
            ->with(['tags:id,name,color'])
            ->orderBy('entry_date', 'desc')
            ->get()
            ->map(fn($e) => [
                'id'       => $e->id,
                'title'    => $e->title,
                'type'     => $e->type,
                'date'     => $e->entry_date->locale('es')->isoFormat('D MMM YYYY'),
                'time'     => substr($e->entry_time, 0, 5),
                'tags'     => $e->tags->map(fn($t) => ['name' => $t->name, 'color' => $t->color]),
            ]);

        // Tareas del proyecto
        $tasks = $project->tasks()
            ->with(['tags:id,name,color'])
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
            ->get()
            ->map(fn($t) => [
                'id'       => $t->id,
                'title'    => $t->title,
                'priority' => $t->priority,
                'status'   => $t->status,
                'due_date' => $t->due_date ? $t->due_date->locale('es')->isoFormat('D MMM') : null,
                'tags'     => $t->tags->map(fn($tag) => ['name' => $tag->name, 'color' => $tag->color]),
            ]);

        // Archivos del proyecto (a través de tareas y entradas)
        $files = $project->entries()
            ->with('attachments')
            ->get()
            ->pluck('attachments')
            ->flatten()
            ->concat(
                $project->tasks()
                    ->with('attachments')
                    ->get()
                    ->pluck('attachments')
                    ->flatten()
            )
            ->unique('id')
            ->map(fn($a) => [
                'id'            => $a->id,
                'original_name' => $a->original_name,
                'mime_type'     => $a->mime_type,
                'size_humans'   => $a->size_for_humans,
                'url'           => $a->url,
                'is_image'      => $a->is_image,
            ]);

        return Inertia::render('Projects/Show', [
            'project' => [
                'id'          => $project->id,
                'name'        => $project->name,
                'color'       => $project->color,
                'description' => $project->description,
                'is_active'   => $project->is_active,
            ],
            'entries' => $entries,
            'tasks'   => $tasks,
            'files'   => $files,
        ]);
    }

    // Métodos no usados en esta implementación
    public function create() {}
    public function edit(string $id) {}
}
