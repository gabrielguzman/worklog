<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; }
        h1 { color: #1f2937; margin-bottom: 20px; }
        .entry { margin-bottom: 20px; padding: 15px; border-left: 4px solid #3b82f6; background-color: #f0f9ff; }
        .entry-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .entry-title { font-weight: bold; font-size: 14px; }
        .entry-meta { font-size: 12px; color: #6b7280; }
        .entry-type { display: inline-block; padding: 4px 8px; background-color: #dbeafe; color: #1e40af; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .entry-content { font-size: 13px; line-height: 1.5; color: #374151; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>📝 Entradas - Reporte PDF</h1>
    <p>Generado: {{ now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY [a las] HH:mm') }}</p>

    @forelse($entries as $entry)
        <div class="entry">
            <div class="entry-header">
                <div>
                    <div class="entry-title">{{ $entry->title }}</div>
                    <span class="entry-type">{{ strtoupper($entry->type) }}</span>
                    @if($entry->project)
                        <span class="entry-meta" style="margin-left: 10px;">📁 {{ $entry->project->name }}</span>
                    @endif
                </div>
                <div class="entry-meta">
                    {{ $entry->entry_date->format('Y-m-d') }} {{ $entry->entry_time }}
                </div>
            </div>
            <div class="entry-content">{{ $entry->content }}</div>
            @if($entry->tags->count() > 0)
                <div class="entry-meta" style="margin-top: 8px;">
                    📌 {{ $entry->tags->pluck('name')->join(', ') }}
                </div>
            @endif
        </div>
    @empty
        <p style="text-align: center; color: #9ca3af;">Sin entradas</p>
    @endforelse

    <p style="margin-top: 20px; font-size: 12px; color: #6b7280;">Total: {{ count($entries) }} entrada(s)</p>
</body>
</html>
