<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuarios\StoreUsuarioRequest;
use App\Http\Requests\Usuarios\UpdateUsuarioRequest;
use App\Models\Usuario;
use App\Services\Usuarios\RolService;
use App\Services\Usuarios\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Database\QueryException;

class UsuarioController extends Controller
{
    public function __construct(
        protected UsuarioService $service,
        protected RolService $rolService
    ) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('Usuarios/Index', [
            'users' => $this->service->getAllPaginated(),
            'roles' => $this->rolService->getAll(),
            'vinculosDisponibles' => $this->service->vinculosDisponibles(),
            'filters' => [],
        ]);
    }

    public function search(Request $request): InertiaResponse
    {
        $filters = $request->only('search_term');

        return Inertia::render('Usuarios/Index', [
            'users' => $this->service->search($filters),
            'roles' => $this->rolService->getAll(),
            'vinculosDisponibles' => $this->service->vinculosDisponibles(),
            'filters' => $filters,
        ]);
    }

    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            if (isset($data['role_id'])) {
                $data['rol_id'] = $data['role_id'];
                unset($data['role_id']);
            }
            $user = $this->service->create($data);
            DB::commit();
            return response()->json(['message' => 'Usuario creado correctamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear el usuario: ' . $e->getMessage()], 500);
        }
    }

    public function update(UpdateUsuarioRequest $request, string $id): JsonResponse
    {
        $user = Usuario::findOrFail($id);

        DB::beginTransaction();
        try {
            $data = $request->validated();
            if (isset($data['role_id'])) {
                $data['rol_id'] = $data['role_id'];
                unset($data['role_id']);
            }
            $this->service->update($user->id, $data);
            DB::commit();
            return response()->json(['message' => 'Usuario actualizado correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar el usuario: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $user = Usuario::findOrFail($id);
        if (Auth::user()->id == $user->id) {
            return response()->json(['message' => 'No puedes dar de baja tu propio usuario.'], 403);
        }

        DB::beginTransaction();
        try {
            $user->update(['esta_activo' => false]);
            DB::commit();

            return response()->json(['message' => 'Usuario dado de baja correctamente.']);
        } catch (QueryException $e) {
            DB::rollBack();

            return response()->json(['message' => 'Error de base de datos al dar de baja.'], 500);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Ocurrió un error inesperado.'], 500);
        }
    }
}
