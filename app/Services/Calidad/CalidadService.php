<?php

namespace App\Services\Calidad;

use Illuminate\Support\Facades\Http;

class CalidadService
{
    protected $apiKey;
    protected $endpoint;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    }

    public function generateContent(string $prompt)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("{$this->endpoint}?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if ($response->successful()) {
            return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'Sin respuesta';
        }

        return 'Error: ' . $response->body();
    }

    
}
