<?php

namespace Database\Seeders;

use App\Models\Entry;
use App\Models\FocusSession;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WorkLogSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario de prueba
        $user = User::firstOrCreate(
            ['email' => 'dev@worklog.test'],
            [
                'name'     => 'Gabriel Dev',
                'password' => Hash::make('password'),
            ]
        );

        // Proyectos
        $projects = collect([
            ['name' => 'WorkLog App',     'color' => '#3B82F6', 'description' => 'La app que estás construyendo ahora mismo'],
            ['name' => 'API REST',         'color' => '#10B981', 'description' => 'Backend de microservicios'],
            ['name' => 'Dashboard Admin',  'color' => '#F59E0B', 'description' => 'Panel de administración interno'],
            ['name' => 'Migración BD',     'color' => '#EF4444', 'description' => 'Migración de MySQL a PostgreSQL'],
        ])->map(fn($p) => Project::firstOrCreate(
            ['user_id' => $user->id, 'name' => $p['name']],
            [...$p, 'user_id' => $user->id]
        ));

        // Tags
        $tagNames = ['backend', 'frontend', 'bug', 'feature', 'refactor', 'deploy', 'urgente', 'docs', 'tests', 'api'];
        $tagColors = ['#3B82F6', '#10B981', '#EF4444', '#8B5CF6', '#F59E0B', '#EC4899', '#EF4444', '#6B7280', '#14B8A6', '#3B82F6'];

        $tags = collect($tagNames)->mapWithKeys(function ($name, $i) use ($user, $tagColors) {
            $tag = Tag::firstOrCreate(
                ['user_id' => $user->id, 'name' => $name],
                ['color' => $tagColors[$i]]
            );
            return [$name => $tag];
        });

        // Plantillas
        foreach ([
            ['name' => 'Reunión de equipo',  'type' => 'entry', 'icon' => 'users',    'fields' => ['title' => 'Reunión - ', 'type' => 'reunion',     'content' => "**Participantes:**\n\n**Temas:**\n\n**Decisiones:**\n\n**Próximos pasos:**"]],
            ['name' => 'Deploy',              'type' => 'entry', 'icon' => 'rocket',   'fields' => ['title' => 'Deploy ',    'type' => 'deploy',      'content' => "**Versión:**\n\n**Cambios:**\n\n**Resultado:**\n\n**Issues:**"]],
            ['name' => 'Code Review',         'type' => 'entry', 'icon' => 'code',     'fields' => ['title' => 'CR: ',       'type' => 'code_review', 'content' => "**PR:**\n\n**Observaciones:**\n\n**Aprobado:** sí/no"]],
            ['name' => 'Bug Report',          'type' => 'task',  'icon' => 'bug',      'fields' => ['priority' => 'high',    'description' => "**Descripción:**\n\n**Pasos para reproducir:**\n\n**Comportamiento esperado:**"]],
        ] as $t) {
            Template::firstOrCreate(
                ['user_id' => $user->id, 'name' => $t['name']],
                ['type' => $t['type'], 'icon' => $t['icon'], 'fields' => $t['fields'], 'user_id' => $user->id]
            );
        }

        // Entradas de los últimos 7 días
        $entryData = [
            ['title' => 'Setup inicial de WorkLog con Laravel + Inertia', 'type' => 'general',       'days_ago' => 0, 'hour' => '09:15', 'project' => 'WorkLog App',    'tags' => ['backend', 'feature']],
            ['title' => 'Reunión de planificación del sprint 12',          'type' => 'reunion',       'days_ago' => 0, 'hour' => '10:30', 'project' => 'WorkLog App',    'tags' => ['docs']],
            ['title' => 'Implementación de migraciones y modelos',        'type' => 'general',       'days_ago' => 0, 'hour' => '14:00', 'project' => 'WorkLog App',    'tags' => ['backend', 'feature']],
            ['title' => 'Deploy v2.3.1 a staging',                        'type' => 'deploy',        'days_ago' => 1, 'hour' => '16:45', 'project' => 'API REST',       'tags' => ['deploy', 'backend']],
            ['title' => 'Code review PR #38 — Refactor de AuthService',   'type' => 'code_review',   'days_ago' => 1, 'hour' => '11:00', 'project' => 'API REST',       'tags' => ['refactor', 'backend']],
            ['title' => 'Bug fix: error 500 en login con OAuth',          'type' => 'general',       'days_ago' => 2, 'hour' => '09:00', 'project' => 'API REST',       'tags' => ['bug', 'urgente']],
            ['title' => 'Investigación: implementar cache con Redis',      'type' => 'investigacion', 'days_ago' => 3, 'hour' => '10:15', 'project' => 'Dashboard Admin','tags' => ['backend', 'api']],
            ['title' => 'Configuración de GitHub Actions para CI/CD',     'type' => 'general',       'days_ago' => 4, 'hour' => '09:30', 'project' => 'WorkLog App',    'tags' => ['deploy']],
            ['title' => 'Reunión con cliente — demo del dashboard',       'type' => 'reunion',       'days_ago' => 5, 'hour' => '14:00', 'project' => 'Dashboard Admin','tags' => ['frontend', 'docs']],
            ['title' => 'Optimización de queries lentas en reportes',     'type' => 'general',       'days_ago' => 6, 'hour' => '11:30', 'project' => 'Dashboard Admin','tags' => ['backend', 'api']],
        ];

        $entries = [];
        foreach ($entryData as $e) {
            $date    = now()->subDays($e['days_ago'])->format('Y-m-d');
            $project = $projects->firstWhere('name', $e['project']);

            $entry = Entry::create([
                'user_id'    => $user->id,
                'project_id' => $project?->id,
                'title'      => $e['title'],
                'content'    => "## {$e['title']}\n\nContenido de la entrada de trabajo. Acá van los detalles de lo que se hizo, decisiones tomadas y cualquier nota relevante para recordar después.\n\n- Punto importante 1\n- Punto importante 2\n- Observaciones finales",
                'type'       => $e['type'],
                'entry_date' => $date,
                'entry_time' => $e['hour'] . ':00',
                'is_pinned'  => false,
            ]);

            $entry->tags()->sync(
                collect($e['tags'])->map(fn($t) => $tags[$t]->id)->filter()->toArray()
            );

            $entries[] = $entry;
        }

        // Tareas
        $taskData = [
            ['title' => 'Implementar Dashboard con métricas del día',   'priority' => 'high',   'status' => 'in_progress', 'project' => 'WorkLog App',    'entry' => 0, 'tags' => ['frontend', 'feature']],
            ['title' => 'CRUD de Entradas con editor de texto',          'priority' => 'high',   'status' => 'pending',     'project' => 'WorkLog App',    'entry' => 2, 'tags' => ['frontend', 'backend']],
            ['title' => 'Upload de archivos con previsualización',       'priority' => 'medium', 'status' => 'pending',     'project' => 'WorkLog App',    'entry' => 2, 'tags' => ['feature']],
            ['title' => 'Implementar búsqueda global con Algolia',       'priority' => 'low',    'status' => 'pending',     'project' => 'WorkLog App',    'entry' => null, 'tags' => ['backend']],
            ['title' => 'Fix: JWT expiración en refresh token',          'priority' => 'urgent', 'status' => 'in_progress', 'project' => 'API REST',       'entry' => 5, 'tags' => ['bug', 'urgente']],
            ['title' => 'Escribir tests de integración para /auth',      'priority' => 'medium', 'status' => 'pending',     'project' => 'API REST',       'entry' => null, 'tags' => ['tests', 'backend']],
            ['title' => 'Actualizar documentación Swagger de la API',    'priority' => 'low',    'status' => 'done',        'project' => 'API REST',       'entry' => 4, 'tags' => ['docs', 'api']],
            ['title' => 'Configurar Redis para sesiones',                'priority' => 'medium', 'status' => 'pending',     'project' => 'Dashboard Admin','entry' => 6, 'tags' => ['backend']],
            ['title' => 'Preparar slides para demo con cliente',         'priority' => 'high',   'status' => 'done',        'project' => 'Dashboard Admin','entry' => 8, 'tags' => ['docs']],
            ['title' => 'Revisar plan de migración de tablas legacy',    'priority' => 'medium', 'status' => 'pending',     'project' => 'Migración BD',   'entry' => null, 'tags' => ['backend', 'refactor']],
        ];

        $createdTasks = [];
        foreach ($taskData as $i => $t) {
            $project     = $projects->firstWhere('name', $t['project']);
            $entryId     = $t['entry'] !== null ? ($entries[$t['entry']]->id ?? null) : null;
            $completedAt = $t['status'] === 'done' ? now()->subDays(rand(1, 3)) : null;

            $task = Task::create([
                'user_id'      => $user->id,
                'project_id'   => $project?->id,
                'entry_id'     => $entryId,
                'title'        => $t['title'],
                'description'  => "Descripción detallada de la tarea. Incluir contexto, criterios de aceptación y notas técnicas relevantes.",
                'priority'     => $t['priority'],
                'status'       => $t['status'],
                'due_date'     => now()->addDays(rand(1, 14))->format('Y-m-d'),
                'completed_at' => $completedAt,
                'sort_order'   => $i,
            ]);

            $task->tags()->sync(
                collect($t['tags'])->map(fn($t) => $tags[$t]->id)->filter()->toArray()
            );

            $createdTasks[] = $task;
        }

        // Focus sessions para las tareas in_progress
        $inProgressTasks = array_filter($createdTasks, fn($t) => $t->status === 'in_progress');
        foreach ($inProgressTasks as $task) {
            FocusSession::factory()->count(rand(2, 4))->create([
                'user_id' => $user->id,
                'task_id' => $task->id,
            ]);
        }

        $this->command->info("✓ WorkLog seeder completado:");
        $this->command->info("  Usuario: dev@worklog.test / password");
        $this->command->info("  Proyectos: {$projects->count()}");
        $this->command->info("  Tags: {$tags->count()}");
        $this->command->info("  Entradas: " . count($entries));
        $this->command->info("  Tareas: " . count($createdTasks));
    }
}
