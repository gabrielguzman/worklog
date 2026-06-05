<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; }
        h1 { color: #1f2937; margin-bottom: 10px; }
        .subtitle { color: #6b7280; margin-bottom: 20px; }
        h2 { color: #374151; border-bottom: 2px solid #3b82f6; padding-bottom: 8px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #3b82f6; color: white; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .metric { display: inline-block; margin-right: 20px; }
        .metric-value { font-size: 24px; font-weight: bold; color: #3b82f6; }
        .metric-label { font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <h1>📅 Reporte Diario</h1>
    <p class="subtitle">{{ $date->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}</p>

    <div>
        <div class="metric">
            <div class="metric-value">{{ count($entries) }}</div>
            <div class="metric-label">Entradas</div>
        </div>
        <div class="metric">
            <div class="metric-value">{{ count($tasks) }}</div>
            <div class="metric-label">Tareas Completadas</div>
        </div>
    </div>

    <h2>📝 Entradas del Día</h2>
    @if($entries->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Proyecto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                    <tr>
                        <td>{{ $entry->entry_time }}</td>
                        <td>{{ $entry->title }}</td>
                        <td>{{ strtoupper($entry->type) }}</td>
                        <td>{{ $entry->project?->name ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color: #9ca3af;">Sin entradas</p>
    @endif

    <h2>✅ Tareas Completadas</h2>
    @if($tasks->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Prioridad</th>
                    <th>Proyecto</th>
                    <th>Completada a las</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasks as $task)
                    <tr>
                        <td>{{ $task->title }}</td>
                        <td>{{ ucfirst($task->priority) }}</td>
                        <td>{{ $task->project?->name ?? '-' }}</td>
                        <td>{{ $task->completed_at?->format('H:i') ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color: #9ca3af;">Sin tareas completadas</p>
    @endif

    <p style="margin-top: 20px; font-size: 12px; color: #6b7280;">Generado: {{ now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY [a las] HH:mm') }}</p>
</body>
</html>
