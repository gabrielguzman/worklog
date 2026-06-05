<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $templates = $user->templates()
            ->orderBy('type', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn($t) => [
                'id'        => $t->id,
                'name'      => $t->name,
                'type'      => $t->type,
                'icon'      => $t->icon,
                'is_active' => $t->is_active,
                'fields'    => $t->fields,
            ]);

        return Inertia::render('Templates/Index', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'type'      => 'required|in:entry,task',
            'icon'      => 'required|string|max:30',
            'is_active' => 'boolean',
            'fields'    => 'required|array',
            // Campos de entrada
            'fields.title'    => 'nullable|string|max:255',
            'fields.content'  => 'nullable|string',
            'fields.type'     => 'nullable|string',
            // Campos de tarea
            'fields.priority'    => 'nullable|string',
            'fields.description' => 'nullable|string',
        ]);

        $request->user()->templates()->create($data);

        return back()->with('success', 'Plantilla creada.');
    }

    public function update(Request $request, Template $template)
    {
        abort_unless($template->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'type'      => 'required|in:entry,task',
            'icon'      => 'required|string|max:30',
            'is_active' => 'boolean',
            'fields'    => 'required|array',
        ]);

        $template->update($data);

        return back()->with('success', 'Plantilla actualizada.');
    }

    public function destroy(Request $request, Template $template)
    {
        abort_unless($template->user_id === $request->user()->id, 403);
        $template->delete();

        return back()->with('success', 'Plantilla eliminada.');
    }

    public function create() {}
    public function show(string $id) {}
    public function edit(string $id) {}
}
