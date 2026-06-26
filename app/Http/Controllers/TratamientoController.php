<?php

namespace App\Http\Controllers;

use App\Models\Servicios\ConsultaMedica;
use App\Models\Servicios\Tratamiento;
use App\Traits\RegistraBitacora;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TratamientoController extends Controller
{
    use RegistraBitacora;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'consulta_id' => 'required|integer|exists:consultas_medicas,id',
            'producto_id' => 'nullable|integer|exists:productos,id',
            'cantidad' => 'nullable|integer|min:1',
            'instrucciones_dosis' => 'nullable|string',
            'notas' => 'nullable|string',
        ]);

        $tratamiento = Tratamiento::create([
            'consulta_medica_id' => $data['consulta_id'],
            'producto_id' => $data['producto_id'] ?? null,
            'cantidad' => $data['cantidad'] ?? null,
            'instrucciones_dosis' => $data['instrucciones_dosis'] ?? null,
            'notas' => $data['notas'] ?? null,
        ]);
        $this->registrarBitacora('crear', 'tratamientos', "Tratamiento registrado para consulta #{$data['consulta_id']}");

        return response()->json(['message' => 'Tratamiento registrado correctamente.', 'tratamiento' => $tratamiento], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'producto_id' => 'nullable|integer|exists:productos,id',
            'cantidad' => 'nullable|integer|min:1',
            'instrucciones_dosis' => 'nullable|string',
            'notas' => 'nullable|string',
        ]);

        $tratamiento = Tratamiento::findOrFail($id);
        $tratamiento->update($data);
        $this->registrarBitacora('editar', 'tratamientos', "Tratamiento #{$id} actualizado");

        return response()->json(['message' => 'Tratamiento actualizado correctamente.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $tratamiento = Tratamiento::findOrFail($id);
        $tratamiento->delete();
        $this->registrarBitacora('eliminar', 'tratamientos', "Tratamiento #{$id} eliminado");

        return response()->json(['message' => 'Tratamiento eliminado correctamente.']);
    }

    public function porConsulta(int $consultaId): JsonResponse
    {
        $tratamientos = Tratamiento::with('producto')
            ->where('consulta_medica_id', $consultaId)
            ->get();

        return response()->json($tratamientos);
    }
}
