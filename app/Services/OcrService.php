<?php

namespace App\Services;

use OpenAI\Client;
use Illuminate\Support\Facades\Storage;

class OcrService
{
    private Client $client;

    public function __construct()
    {
        $this->client = \OpenAI::client(config('services.openai.api_key'));
    }

    // Extraer texto de una imagen usando OpenAI Vision
    public function extractText(string $imagePath, string $mimeType = 'image/jpeg'): ?string
    {
        try {
            // Solo procesar imágenes
            if (!str_starts_with($mimeType, 'image/')) {
                return null;
            }

            // Leer el archivo de storage
            $disk = Storage::disk('public');
            if (!$disk->exists($imagePath)) {
                return null;
            }

            $imageContent = $disk->get($imagePath);
            $base64       = base64_encode($imageContent);

            // Llamar a GPT-4 Vision
            $response = $this->client->messages()->create([
                'model'      => 'gpt-4-vision',
                'max_tokens' => 1024,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Por favor extrae TODO el texto de esta imagen de manera clara y legible. Mantén la estructura y formato original.',
                            ],
                            [
                                'type'      => 'image',
                                'source'    => [
                                    'type'      => 'base64',
                                    'media_type' => $mimeType,
                                    'data'      => $base64,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            return $response->content[0]->text ?? null;
        } catch (\Exception $e) {
            \Log::warning('OCR error: ' . $e->getMessage());
            return null;
        }
    }
}
