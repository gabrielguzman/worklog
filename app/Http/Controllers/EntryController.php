<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class EntryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->entries()
            ->with(['project:id,name,color', 'tags:id,name,color'])
            ->withCount('attachments');

        // Filtros
        if ($search = $request->get('search')) {
            $query->where(fn($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
            );
        }

        if ($project = $request->get('project_id')) {
            $query->where('project_id', $project);
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($tag = $request->get('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('name', $tag));
        }

        if ($from = $request->get('from')) {
            $query->whereDate('entry_date', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->whereDate('entry_date', '<=', $to);
        }

        $entries = $query
            ->orderBy('entry_date', 'desc')
            ->orderBy('entry_time', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($e) => [
                'id'               => $e->id,
                'title'            => $e->title,
                'type'             => $e->type,
                'entry_date'       => $e->entry_date->format('Y-m-d'),
                'entry_date_label' => $e->entry_date->locale('es')->isoFormat('D MMM YYYY'),
                'entry_time'       => substr($e->entry_time, 0, 5),
                'is_pinned'        => $e->is_pinned,
                'content_preview'  => $e->content ? \Str::limit(strip_tags($e->content), 120) : null,
                'attachments_count'=> $e->attachments_count,
                'project'          => $e->project ? ['id' => $e->project->id, 'name' => $e->project->name, 'color' => $e->project->color] : null,
                'tags'             => $e->tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color]),
            ]);

        return Inertia::render('Entries/Index', [
            'entries'  => $entries,
            'projects' => $user->projects()->where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'tags'     => $user->tags()->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'filters'  => $request->only('search', 'project_id', 'type', 'tag', 'from', 'to'),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        $templateData = null;
        if ($templateId = $request->get('template')) {
            $template = Template::where('user_id', $user->id)->where('type', 'entry')->find($templateId);
            $templateData = $template ? $template->fields : null;
        }

        return Inertia::render('Entries/Form', [
            'projects'     => $user->projects()->where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'tags'         => $user->tags()->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'templates'    => $user->templates()->where('type', 'entry')->where('is_active', true)->orderBy('name', 'asc')->get(),
            'templateData' => $templateData,
            'defaults'     => [
                'entry_date' => now()->format('Y-m-d'),
                'entry_time' => now()->format('H:i'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'nullable|string',
            'type'       => 'required|in:general,reunion,deploy,code_review,investigacion,planificacion',
            'entry_date' => 'required|date',
            'entry_time' => 'required',
            'project_id' => 'nullable|exists:projects,id',
            'is_pinned'  => 'boolean',
            'tags'       => 'nullable|array',
            'tags.*'     => 'exists:tags,id',
        ]);

        $entry = $request->user()->entries()->create([
            ...$data,
            'is_pinned' => $data['is_pinned'] ?? false,
        ]);

        if (!empty($data['tags'])) {
            $entry->tags()->sync($data['tags']);
        }

        return redirect()->route('entries.show', $entry)->with('success', 'Entrada creada correctamente.');
    }

    public function show(Request $request, Entry $entry)
    {
        abort_unless($entry->user_id === $request->user()->id, 403);

        $entry->load(['project:id,name,color', 'tags:id,name,color', 'attachments', 'tasks' => fn($q) => $q->with('tags:id,name,color')->orderBy('sort_order', 'asc')]);

        return Inertia::render('Entries/Show', [
            'entry' => [
                'id'               => $entry->id,
                'title'            => $entry->title,
                'content'          => $entry->content,
                'type'             => $entry->type,
                'entry_date'       => $entry->entry_date->format('Y-m-d'),
                'entry_date_label' => $entry->entry_date->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'),
                'entry_time'       => substr($entry->entry_time, 0, 5),
                'is_pinned'        => $entry->is_pinned,
                'project'          => $entry->project,
                'tags'             => $entry->tags,
                'attachments'      => $entry->attachments->map(fn($a) => [
                    'id'            => $a->id,
                    'original_name' => $a->original_name,
                    'mime_type'     => $a->mime_type,
                    'size_humans'   => $a->size_for_humans,
                    'is_image'      => $a->is_image,
                    'url'           => $a->url,
                ]),
                'tasks'            => $entry->tasks->map(fn($t) => [
                    'id'       => $t->id,
                    'title'    => $t->title,
                    'priority' => $t->priority,
                    'status'   => $t->status,
                    'tags'     => $t->tags,
                ]),
                'created_at' => $entry->created_at->locale('es')->diffForHumans(),
            ],
        ]);
    }

    public function edit(Request $request, Entry $entry)
    {
        abort_unless($entry->user_id === $request->user()->id, 403);

        $entry->load(['tags:id,name,color']);
        $user = $request->user();

        return Inertia::render('Entries/Form', [
            'entry'    => [
                'id'         => $entry->id,
                'title'      => $entry->title,
                'content'    => $entry->content,
                'type'       => $entry->type,
                'entry_date' => $entry->entry_date->format('Y-m-d'),
                'entry_time' => substr($entry->entry_time, 0, 5),
                'project_id' => $entry->project_id,
                'is_pinned'  => $entry->is_pinned,
                'tags'       => $entry->tags->pluck('id'),
            ],
            'projects'     => $user->projects()->where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'tags'         => $user->tags()->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'templates'    => [],
            'templateData' => null,
            'defaults'     => [],
        ]);
    }

    public function update(Request $request, Entry $entry)
    {
        abort_unless($entry->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'nullable|string',
            'type'       => 'required|in:general,reunion,deploy,code_review,investigacion,planificacion',
            'entry_date' => 'required|date',
            'entry_time' => 'required',
            'project_id' => 'nullable|exists:projects,id',
            'is_pinned'  => 'boolean',
            'tags'       => 'nullable|array',
            'tags.*'     => 'exists:tags,id',
        ]);

        $entry->update($data);
        $entry->tags()->sync($data['tags'] ?? []);

        return redirect()->route('entries.show', $entry)->with('success', 'Entrada actualizada.');
    }

    public function destroy(Request $request, Entry $entry)
    {
        abort_unless($entry->user_id === $request->user()->id, 403);
        $entry->delete();

        return redirect()->route('entries.index')->with('success', 'Entrada eliminada.');
    }
}
