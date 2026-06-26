<?php

namespace App\Http\Controllers\Calidad;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\Calidad\CalidadService;

class CalidadController extends Controller
{

    protected $gemini;

    public function __construct(CalidadService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function index(): Response
    {
        return Inertia::render('Calidad/Prompt/Index');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
        ]);

        $promptEstructurado = <<<PROMPT
            Actúa como un veterinario clínico. 
            Evalúa los síntomas que te indicaré y responde 
            **EXCLUSIVAMENTE** en formato JSON con el siguiente esquema:

            {
            "diagnostico": "breve descripción del diagnóstico preliminar",
            "causas": ["causa probable 1", "causa probable 2"],
            "recomendaciones": ["acción recomendada 1", "acción recomendada 2"],
            "medicamentos": ["Medicamento 1", "Medicamento 2"]
            }
            Incluye un 95% de las veces al menos un medicamento y una dosis aproximada.
            No incluyas ninguna explicación adicional. 
            Si no estás seguro, escribe `"No determinado"` en los campos. 
            Usa solo texto plano, sin comillas especiales ni caracteres no válidos.

            Síntomas: {$request->prompt}
        PROMPT;


        $response = $this->gemini->generateContent($promptEstructurado);
        $parsed = $this->extractJsonFromMarkdown($response);

        if ($parsed) {
            return response()->json($parsed);
        }

        return response()->json([
            'diagnostico' => 'No se pudo interpretar la respuesta del modelo.',
            'respuesta_raw' => $response
        ]);
    }


    private function extractJsonFromMarkdown(string $text): ?array
    {
        // Elimina las etiquetas ```json ... ``` si existen
        if (preg_match('/```json(.*?)```/s', $text, $matches)) {
            $cleanJson = trim($matches[1]);
        } else {
            $cleanJson = trim($text);
        }

        $decoded = json_decode($cleanJson, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
