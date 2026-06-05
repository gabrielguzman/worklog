<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessOcr;
use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FileController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = $user->attachments()->orderBy('created_at', 'desc');

        // Filtro por tipo agrupado
        if ($typeGroup = $request->get('type')) {
            if ($typeGroup === 'images') {
                $query->where('mime_type', 'like', 'image/%');
            } elseif ($typeGroup === 'pdfs') {
                $query->where('mime_type', 'application/pdf');
            } elseif ($typeGroup === 'documents') {
                $query->whereIn('mime_type', [
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/plain', 'text/csv',
                ]);
            }
        }

        // Búsqueda en nombre + texto OCR
        if ($search = $request->get('search')) {
            $query->where(fn($q) => $q
                ->where('original_name', 'like', "%{$search}%")
                ->orWhere('ocr_text', 'like', "%{$search}%")
            );
        }

        // Filtro por entidad vinculada
        if ($entryId = $request->get('entry')) {
            $query->where('attachable_type', Entry::class)->where('attachable_id', $entryId);
        }
        if ($taskId = $request->get('task')) {
            $query->where('attachable_type', Task::class)->where('attachable_id', $taskId);
        }

        $files = $query->paginate(24)->withQueryString()->through(fn($a) => [
            'id'            => $a->id,
            'original_name' => $a->original_name,
            'mime_type'     => $a->mime_type,
            'size'          => $a->size,
            'size_humans'   => $a->size_for_humans,
            'is_image'      => $a->is_image,
            'url'           => $a->url,
            'ocr_text'      => $a->ocr_text ? Str::limit($a->ocr_text, 120) : null,
            'attachable_type'=> $a->attachable_type ? class_basename($a->attachable_type) : null,
            'attachable_id'  => $a->attachable_id,
            'created_at'    => $a->created_at->locale('es')->isoFormat('D MMM YYYY, HH:mm'),
            'created_diff'  => $a->created_at->diffForHumans(),
        ]);

        // Contadores por tipo para las tabs
        $counts = [
            'all'       => $user->attachments()->count(),
            'images'    => $user->attachments()->where('mime_type', 'like', 'image/%')->count(),
            'pdfs'      => $user->attachments()->where('mime_type', 'application/pdf')->count(),
            'documents' => $user->attachments()->whereIn('mime_type', [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'text/plain', 'text/csv',
            ])->count(),
        ];

        // Info del entry/task prefiltrado (para mostrar contexto en el header)
        $context = null;
        if ($entryId) {
            $entry = Entry::where('user_id', $user->id)->find($entryId);
            $context = $entry ? ['type' => 'entry', 'id' => $entry->id, 'title' => $entry->title] : null;
        } elseif ($taskId) {
            $task = Task::where('user_id', $user->id)->find($taskId);
            $context = $task ? ['type' => 'task', 'id' => $task->id, 'title' => $task->title] : null;
        }

        return Inertia::render('Files/Index', [
            'files'   => $files,
            'counts'  => $counts,
            'filters' => $request->only('search', 'type', 'entry', 'task'),
            'context' => $context,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'files'           => 'required|array|min:1|max:10',
            'files.*'         => 'required|file|max:20480', // 20 MB max
            'attachable_type' => 'nullable|in:entry,task',
            'attachable_id'   => 'nullable|integer',
        ]);

        $user     = $request->user();
        $uploaded = [];

        // Resolver el modelo morphable
        $attachableClass = null;
        $attachableModel = null;
        if ($request->filled('attachable_type') && $request->filled('attachable_id')) {
            $attachableClass = $request->attachable_type === 'entry' ? Entry::class : Task::class;
            $attachableModel = $attachableClass::where('user_id', $user->id)->find($request->attachable_id);
        }

        foreach ($request->file('files') as $file) {
            $uuid      = Str::uuid();
            $ext       = $file->getClientOriginalExtension();
            $filename  = "{$uuid}.{$ext}";
            $folder    = "attachments/{$user->id}";
            $path      = $file->storeAs($folder, $filename, 'public');

            $attachment = Attachment::create([
                'user_id'         => $user->id,
                'attachable_type' => $attachableModel ? $attachableClass : null,
                'attachable_id'   => $attachableModel ? $attachableModel->id : null,
                'filename'        => $filename,
                'original_name'   => $file->getClientOriginalName(),
                'mime_type'       => $file->getMimeType(),
                'size'            => $file->getSize(),
                'path'            => $path,
                'disk'            => 'public',
                'ocr_text'        => null,
            ]);

            // Procesar OCR en background (solo para imágenes)
            if (str_starts_with($file->getMimeType(), 'image/')) {
                ProcessOcr::dispatch($attachment)->delay(now()->addSeconds(2));
            }

            $uploaded[] = $attachment;
        }

        // Redirigir al contexto si venía de una entrada o tarea
        if ($attachableModel && $request->attachable_type === 'entry') {
            return redirect()->route('entries.show', $attachableModel->id)
                ->with('success', count($uploaded) . ' archivo(s) subido(s).');
        }
        if ($attachableModel && $request->attachable_type === 'task') {
            return redirect()->route('tasks.show', $attachableModel->id)
                ->with('success', count($uploaded) . ' archivo(s) subido(s).');
        }

        return redirect()->route('files.index')
            ->with('success', count($uploaded) . ' archivo(s) subido(s).');
    }

    public function destroy(Request $request, Attachment $attachment)
    {
        abort_unless($attachment->user_id === $request->user()->id, 403);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'Archivo eliminado.');
    }
}
