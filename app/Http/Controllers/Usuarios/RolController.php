<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuarios\DestroyRolRequest;
use App\Http\Requests\Usuarios\StoreRolRequest;
use App\Http\Requests\Usuarios\UpdateRolRequest;
use App\Services\Usuarios\PermisoService;
use App\Services\Usuarios\RolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RolController extends Controller
{
    public function __construct(protected RolService $rolService, protected PermisoService $permisoService) {}

    public function index()
    {
        $roles = $this->rolService->getAllPaginated();
        $permissions = $this->permisoService->getAll();

        return Inertia::render('Usuarios/Roles/Index', [
            'roles' => $roles,
            'rolesExistentes' => $this->rolService->listarParaValidacion(),
            'permissions' => $permissions,
            'filters' => request()->only('search_term'),
        ]);
    }

    public function store(StoreRolRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            if (isset($data['name'])) {
                $data['nombre'] = $data['name'];
                unset($data['name']);
            }
            $role = $this->rolService->create($data);
            $role->syncPermisos($data['permissions']);
            DB::commit();

            return response()->json(['message' => 'Rol creado correctamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Error al crear el rol: '.$e->getMessage()], 500);
        }
    }

    public function update(UpdateRolRequest $request, string $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            if (isset($data['name'])) {
                $data['nombre'] = $data['name'];
                unset($data['name']);
            }
            $this->rolService->update($id, $data);
            $role = $this->rolService->getById($id);
            $role->syncPermisos($data['permissions']);
            DB::commit();

            return response()->json(['message' => 'Rol actualizado correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Error al actualizar el rol: '.$e->getMessage()], 500);
        }
    }

    public function destroy(DestroyRolRequest $request, string $id): JsonResponse
    {
        try {
            $role = $this->rolService->getById($id);

            if ($this->rolService->esRolProtegido($role->nombre)) {
                return response()->json(['message' => 'No se puede eliminar un rol del sistema.'], 403);
            }

            if ($role->usuarios()->exists()) {
                return response()->json(['message' => 'No se puede eliminar un rol con usuarios asignados.'], 409);
            }

            DB::beginTransaction();
            $role->permisos()->detach();
            $this->rolService->delete($id);
            DB::commit();

            return response()->json(['message' => 'Rol eliminado correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Ocurrió un error al eliminar el rol.'], 500);
        }
    }
}
