<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $tags = $user->tags()
            ->withCount(['entries', 'tasks'])
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn($t) => [
                'id'            => $t->id,
                'name'          => $t->name,
                'color'         => $t->color,
                'entries_count' => $t->entries_count,
                'tasks_count'   => $t->tasks_count,
            ]);

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:50',
            'color' => 'required|string|size:7',
        ]);

        // Verificar unicidad por usuario
        $exists = $request->user()->tags()->where('name', $data['name'])->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'Ya tenés un tag con ese nombre.']);
        }

        $request->user()->tags()->create($data);

        return back()->with('success', 'Tag creado.');
    }

    public function update(Request $request, Tag $tag)
    {
        abort_unless($tag->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name'  => 'required|string|max:50',
            'color' => 'required|string|size:7',
        ]);

        $tag->update($data);

        return back()->with('success', 'Tag actualizado.');
    }

    public function destroy(Request $request, Tag $tag)
    {
        abort_unless($tag->user_id === $request->user()->id, 403);

        // Desvincular de entradas y tareas automáticamente por la cascade del pivot
        $tag->delete();

        return back()->with('success', 'Tag eliminado.');
    }

    public function create() {}
    public function show(string $id) {}
    public function edit(string $id) {}
}
