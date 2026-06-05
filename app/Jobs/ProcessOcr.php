<?php

namespace App\Jobs;

use App\Models\Attachment;
use App\Services\OcrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessOcr implements ShouldQueue
{
    use Queueable;

    public $timeout = 120; // segundos máximo para procesar

    public function __construct(
        public Attachment $attachment
    ) {}

    public function handle(OcrService $ocr): void
    {
        // Solo procesar imágenes
        if (!str_starts_with($this->attachment->mime_type, 'image/')) {
            return;
        }

        // Extraer texto
        $text = $ocr->extractText(
            $this->attachment->path,
            $this->attachment->mime_type
        );

        // Guardar
        if ($text) {
            $this->attachment->update(['ocr_text' => $text]);
        }
    }
}
