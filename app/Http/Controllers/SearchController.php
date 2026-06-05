<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $user       = $request->user();
        $query      = trim($request->get('q', ''));
        $typeFilter = $request->get('type', 'all');   // all | entries | tasks | files
        $projectId  = $request->get('project_id');
        $tagName    = $request->get('tag');
        $from       = $request->get('from');
        $to         = $request->get('to');

        $results = ['entries' => [], 'tasks' => [], 'files' => []];
        $totals  = ['entries' => 0, 'tasks' => 0, 'files' => 0];

        // ── Sólo buscar si hay término o filtros activos ─────────────────
        $hasQuery = $query !== '' || $projectId || $tagName || $from || $to;

        if ($hasQuery) {

            // ── Entradas ──────────────────────────────────────────────────
            if (in_array($typeFilter, ['all', 'entries'])) {
                $q = $user->entries()
                    ->with(['project:id,name,color', 'tags:id,name,color'])
                    ->when($query, fn($q) => $q->where(fn($sub) => $sub
                        ->where('title',   'like', "%{$query}%")
                        ->orWhere('content', 'like', "%{$query}%")
                    ))
                    ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                    ->when($tagName,   fn($q) => $q->whereHas('tags', fn($t) => $t->where('name', $tagName)))
                    ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
                    ->when($to,   fn($q) => $q->whereDate('entry_date', '<=', $to))
                    ->orderBy('entry_date', 'desc')
                    ->orderBy('entry_time', 'desc');

                $totals['entries'] = $q->count();
                $results['entries'] = $q->limit(10)->get()->map(fn($e) => [
                    'id'          => $e->id,
                    'type'        => 'entry',
                    'title'       => $e->title,
                    'snippet'     => $this->snippet($e->content, $query),
                    'meta'        => $e->entry_date->locale('es')->isoFormat('D MMM YYYY') . ' · ' . substr($e->entry_time, 0, 5),
                    'entry_type'  => $e->type,
                    'url'         => "/entries/{$e->id}",
                    'project'     => $e->project ? ['name' => $e->project->name, 'color' => $e->project->color] : null,
                    'tags'        => $e->tags->map(fn($t) => ['name' => $t->name, 'color' => $t->color]),
                ])->toArray();
            }

            // ── Tareas ────────────────────────────────────────────────────
            if (in_array($typeFilter, ['all', 'tasks'])) {
                $q = $user->tasks()
                    ->with(['project:id,name,color', 'tags:id,name,color'])
                    ->when($query, fn($q) => $q->where(fn($sub) => $sub
                        ->where('title',       'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%")
                    ))
                    ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                    ->when($tagName,   fn($q) => $q->whereHas('tags', fn($t) => $t->where('name', $tagName)))
                    ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
                    ->when($to,   fn($q) => $q->whereDate('created_at', '<=', $to))
                    ->orderByRaw("FIELD(status,'in_progress','pending','done')")
                    ->orderByRaw("FIELD(priority,'urgent','high','medium','low')");

                $totals['tasks'] = $q->count();
                $results['tasks'] = $q->limit(10)->get()->map(fn($t) => [
                    'id'       => $t->id,
                    'type'     => 'task',
                    'title'    => $t->title,
                    'snippet'  => $this->snippet($t->description, $query),
                    'meta'     => ucfirst($t->status) . ' · ' . ucfirst($t->priority),
                    'status'   => $t->status,
                    'priority' => $t->priority,
                    'url'      => "/tasks/{$t->id}",
                    'project'  => $t->project ? ['name' => $t->project->name, 'color' => $t->project->color] : null,
                    'tags'     => $t->tags->map(fn($tag) => ['name' => $tag->name, 'color' => $tag->color]),
                ])->toArray();
            }

            // ── Archivos ──────────────────────────────────────────────────
            if (in_array($typeFilter, ['all', 'files'])) {
                $q = $user->attachments()
                    ->when($query, fn($q) => $q->where(fn($sub) => $sub
                        ->where('original_name', 'like', "%{$query}%")
                        ->orWhere('ocr_text',    'like', "%{$query}%")
                    ))
                    ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
                    ->when($to,   fn($q) => $q->whereDate('created_at', '<=', $to))
                    ->orderBy('created_at', 'desc');

                $totals['files'] = $q->count();
                $results['files'] = $q->limit(10)->get()->map(fn($a) => [
                    'id'            => $a->id,
                    'type'          => 'file',
                    'title'         => $a->original_name,
                    'snippet'       => $this->snippet($a->ocr_text, $query),
                    'meta'          => $a->size_for_humans . ' · ' . $a->created_at->locale('es')->isoFormat('D MMM YYYY'),
                    'mime_type'     => $a->mime_type,
                    'is_image'      => $a->is_image,
                    'url'           => $a->url,
                    'attachable_type' => $a->attachable_type ? class_basename($a->attachable_type) : null,
                    'attachable_id'   => $a->attachable_id,
                ])->toArray();
            }
        }

        $totalAll = $totals['entries'] + $totals['tasks'] + $totals['files'];

        return Inertia::render('Search/Index', [
            'results'     => $results,
            'totals'      => $totals,
            'totalAll'    => $totalAll,
            'query'       => $query,
            'filters'     => $request->only('type', 'project_id', 'tag', 'from', 'to'),
            'projects'    => $user->projects()->where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'tags'        => $user->tags()->orderBy('name', 'asc')->get(['id', 'name', 'color']),
            'hasQuery'    => $hasQuery,
        ]);
    }

    private function snippet(?string $text, string $query): ?string
    {
        if (!$text || !$query) {
            return $text ? Str::limit(strip_tags($text), 140) : null;
        }

        $clean = strip_tags($text);
        $pos   = mb_stripos($clean, $query);

        if ($pos === false) {
            return Str::limit($clean, 140);
        }

        // Extraer ventana de contexto alrededor del match
        $start   = max(0, $pos - 60);
        $excerpt = mb_substr($clean, $start, 200);

        if ($start > 0) $excerpt = '...' . $excerpt;
        if ($start + 200 < mb_strlen($clean)) $excerpt .= '...';

        return $excerpt;
    }
}
