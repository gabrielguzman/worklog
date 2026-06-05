<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExportController extends Controller
{
    // Exportar tareas a CSV
    public function tasksCSV(Request $request)
    {
        $user = $request->user();
        $query = $user->tasks()->with(['project:id,name', 'tags:id,name']);

        if ($search = $request->query('search')) {
            $query->where(fn($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            );
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        $tasks = $query->get();

        return $this->downloadCSV($tasks, 'tareas', ['id', 'title', 'description', 'status', 'priority', 'due_date', 'project', 'tags', 'created_at']);
    }

    // Exportar entradas a CSV
    public function entriesCSV(Request $request)
    {
        $user = $request->user();
        $query = $user->entries()->with(['project:id,name', 'tags:id,name']);

        if ($search = $request->query('search')) {
            $query->where(fn($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
            );
        }
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $entries = $query->get();

        return $this->downloadCSV($entries, 'entradas', ['id', 'title', 'content', 'type', 'entry_date', 'entry_time', 'project', 'tags', 'created_at']);
    }

    // Exportar reporte diario a CSV
    public function reportDailyCSV(Request $request)
    {
        $user = $request->user();
        $date = $request->query('date', now()->format('Y-m-d'));
        $dateCarbon = Carbon::parse($date);

        $entries = $user->entries()->whereDate('entry_date', $date)->get();
        $tasks = $user->tasks()->whereDate('completed_at', $date)->where('status', 'done')->get();

        $data = [
            ['Reporte Diario', $dateCarbon->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')],
            [],
            ['ENTRADAS DEL DÍA'],
            ['Hora', 'Título', 'Tipo', 'Contenido', 'Proyecto'],
        ];

        foreach ($entries as $entry) {
            $data[] = [
                $entry->entry_time,
                $entry->title,
                $entry->type,
                substr($entry->content ?? '', 0, 50),
                $entry->project ? $entry->project->name : '',
            ];
        }

        $data[] = [];
        $data[] = ['TAREAS COMPLETADAS HOY'];
        $data[] = ['Título', 'Prioridad', 'Proyecto', 'Completada'];

        foreach ($tasks as $task) {
            $data[] = [
                $task->title,
                $task->priority,
                $task->project ? $task->project->name : '',
                $task->completed_at ? $task->completed_at->format('H:i') : '',
            ];
        }

        return $this->outputCSV('reporte-diario_' . $date, $data);
    }

    // Exportar reporte semanal a CSV
    public function reportWeeklyCSV(Request $request)
    {
        $user = $request->user();
        $week = $request->query('week', now()->weekOfYear);
        $year = $request->query('year', now()->year);

        $startDate = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();

        $entries = $user->entries()
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->get();

        $tasks = $user->tasks()
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->where('status', 'done')
            ->get();

        $data = [
            ['Reporte Semanal', "Semana $week de $year"],
            ["Del " . $startDate->format('Y-m-d') . " al " . $endDate->format('Y-m-d')],
            [],
            ['ENTRADAS DE LA SEMANA'],
            ['Fecha', 'Hora', 'Título', 'Tipo', 'Proyecto'],
        ];

        foreach ($entries as $entry) {
            $data[] = [
                $entry->entry_date->format('Y-m-d'),
                $entry->entry_time,
                $entry->title,
                $entry->type,
                $entry->project ? $entry->project->name : '',
            ];
        }

        $data[] = [];
        $data[] = ['TAREAS COMPLETADAS ESTA SEMANA'];
        $data[] = ['Completada', 'Título', 'Prioridad', 'Proyecto'];

        foreach ($tasks as $task) {
            $data[] = [
                $task->completed_at ? $task->completed_at->format('Y-m-d H:i') : '',
                $task->title,
                $task->priority,
                $task->project ? $task->project->name : '',
            ];
        }

        return $this->outputCSV('reporte-semanal_' . $week . '_' . $year, $data);
    }

    // ── Helper para descargar CSV ──
    private function downloadCSV($items, $filename, $columns)
    {
        $csv = fopen('php://memory', 'r+');

        // Encabezados
        fputcsv($csv, $columns);

        // Filas
        foreach ($items as $item) {
            $row = [];
            foreach ($columns as $col) {
                if ($col === 'project') {
                    $row[] = $item->project ? $item->project->name : '';
                } elseif ($col === 'tags') {
                    $row[] = $item->tags->pluck('name')->join(', ');
                } elseif (in_array($col, ['entry_date', 'due_date', 'completed_at', 'created_at'])) {
                    $row[] = $item->{$col} ? $item->{$col}->format('Y-m-d H:i') : '';
                } else {
                    $row[] = $item->{$col} ?? '';
                }
            }
            fputcsv($csv, $row);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '_' . now()->format('Y-m-d_His') . '.csv"');
    }

    // ── Helper para salida CSV ──
    private function outputCSV($filename, $data)
    {
        $csv = fopen('php://memory', 'r+');

        foreach ($data as $row) {
            fputcsv($csv, $row);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '_' . now()->format('Y-m-d_His') . '.csv"');
    }
}
