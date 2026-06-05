<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    public function dailySummary(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today();

        $entries = $user->entries()
            ->whereDate('entry_date', $today)
            ->with(['project:id,name', 'tags:id,name'])
            ->orderBy('entry_date', 'asc')
            ->get();

        $tasksCompleted = $user->tasks()
            ->whereDate('completed_at', $today)
            ->with(['project:id,name', 'tags:id,name'])
            ->get();

        if ($entries->isEmpty() && $tasksCompleted->isEmpty()) {
            return response()->json([
                'summary' => 'Sin registros de hoy. ¡Iniciá tu día creando una entrada!',
                'wordCount' => 0,
            ]);
        }

        $entriesText = $entries->map(fn($e) => "- [{$e->type}] {$e->title}\n  " . substr(strip_tags($e->content), 0, 100))
            ->join("\n\n");

        $tasksText = $tasksCompleted->map(fn($t) => "✓ {$t->title}")
            ->join("\n");

        $prompt = <<<PROMPT
Basándote en las entradas y tareas completadas de hoy, genera un resumen profesional y conciso en español (máximo 4-5 líneas).

Entradas registradas:
{$entriesText}

Tareas completadas hoy:
{$tasksText}

Por favor genera un resumen que:
- Identifique los temas principales
- Destaque logros y progreso
- Sea amigable pero profesional
PROMPT;

        try {
            $client = \OpenAI::client(config('services.openai.api_key'));

            $response = $client->messages()->create([
                'model'      => 'gpt-4-turbo',
                'max_tokens' => 300,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            $summary  = $response->content[0]->text;
            $wordCount = str_word_count($summary);

            return response()->json([
                'summary'   => $summary,
                'wordCount' => $wordCount,
            ]);
        } catch (\Exception $e) {
            Log::warning('AI summary error: ' . $e->getMessage());

            return response()->json([
                'summary'   => 'No se pudo generar el resumen. Intenta más tarde.',
                'wordCount' => 0,
            ], 500);
        }
    }

    public function extractTasks(Request $request)
    {
        $data = $request->validate([
            'entry_id'   => 'required|exists:entries,id',
            'entry_text' => 'required|string|max:5000',
        ]);

        $user  = $request->user();
        $entry = $user->entries()->find($data['entry_id']);

        if (!$entry) {
            return response()->json(['error' => 'Entrada no encontrada'], 404);
        }

        $prompt = <<<PROMPT
Analiza el siguiente texto y extrae todas las tareas, acciones o elementos de trabajo que deben completarse.
Devuelve SOLO un JSON con este formato, sin explicaciones adicionales:
{
    "tasks": [
        {"title": "Descripción breve", "priority": "medium"},
    ]
}

Prioridades: "low", "medium", "high", "urgent"

Texto:
{$data['entry_text']}
PROMPT;

        try {
            $client = \OpenAI::client(config('services.openai.api_key'));

            $response = $client->messages()->create([
                'model'      => 'gpt-4-turbo',
                'max_tokens' => 1024,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            $jsonText = $response->content[0]->text;
            preg_match('/\{.*?\}/s', $jsonText, $matches);
            $parsed = json_decode($matches[0] ?? '{}', true);

            return response()->json([
                'tasks' => $parsed['tasks'] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::warning('AI task extraction error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Error al extraer tareas',
                'tasks' => [],
            ], 500);
        }
    }
}
