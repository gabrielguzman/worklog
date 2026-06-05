<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; }
        h1 { color: #1f2937; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #3b82f6; color: white; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-done { background-color: #d1fae5; color: #065f46; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-in_progress { background-color: #dbeafe; color: #1e40af; }
        .priority { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .priority-urgent { background-color: #fee2e2; color: #991b1b; }
        .priority-high { background-color: #fed7aa; color: #9a3412; }
        .priority-medium { background-color: #fef08a; color: #713f12; }
        .priority-low { background-color: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
    <h1>📋 Tareas - Reporte PDF</h1>
    <p>Generado: {{ now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY [a las] HH:mm') }}</p>

    <table>
        <thead>
            <tr>
                <th>Título</th>
                <th>Descripción</th>
                <th>Estado</th>
                <th>Prioridad</th>
                <th>Proyecto</th>
                <th>Fecha Límite</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tasks as $task)
                <tr>
                    <td><strong>{{ $task->title }}</strong></td>
                    <td>{{ substr($task->description ?? '', 0, 50) }}</td>
                    <td><span class="status status-{{ $task->status }}">{{ ucfirst($task->status) }}</span></td>
                    <td><span class="priority priority-{{ $task->priority }}">{{ ucfirst($task->priority) }}</span></td>
                    <td>{{ $task->project?->name ?? '-' }}</td>
                    <td>{{ $task->due_date?->format('Y-m-d') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #9ca3af;">Sin tareas</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 20px; font-size: 12px; color: #6b7280;">Total: {{ count($tasks) }} tarea(s)</p>
</body>
</html>
