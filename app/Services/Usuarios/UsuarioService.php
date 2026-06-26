<?php

namespace App\Services\Usuarios;

use App\Models\Servicios\Cliente;
use App\Models\Servicios\Veterinario;
use App\Models\Usuario;
use App\Repositories\Usuarios\UsuarioRepository;

class UsuarioService
{
    public function __construct(protected UsuarioRepository $repository) {}

    public function getAllPaginated()
    {
        return $this->repository->getAllPaginated();
    }

    public function search(array $filters)
    {
        return $this->repository->search($filters);
    }

    public function getById(string $id)
    {
        return $this->repository->getById($id);
    }

    public function vinculosDisponibles(): array
    {
        return [
            'clientes' => Cliente::query()
                ->whereNull('usuario_id')
                ->orderBy('nombre')
                ->orderBy('apellido')
                ->get(['id', 'nombre', 'apellido', 'ci', 'telefono', 'fecha_nacimiento', 'direccion']),
            'veterinarios' => Veterinario::query()
                ->whereNull('usuario_id')
                ->where('esta_activo', true)
                ->orderBy('nombre')
                ->orderBy('apellido')
                ->get(['id', 'nombre', 'apellido', 'email', 'es_especialista', 'especialidad']),
        ];
    }

    public function create(array $data)
    {
        if (isset($data['role_id'])) {
            $data['rol_id'] = $data['role_id'];
            unset($data['role_id']);
        }

        $clienteId = $data['cliente_id'] ?? null;
        $veterinarioId = $data['veterinario_id'] ?? null;
        $reactivarId = $data['reactivar_usuario_id'] ?? null;
        unset($data['cliente_id'], $data['veterinario_id'], $data['reactivar_usuario_id']);

        if ($reactivarId) {
            $this->update((string) $reactivarId, array_merge($data, ['esta_activo' => true]));

            return $this->getById((string) $reactivarId);
        }

        if (isset($data['first_name']) || isset($data['last_name'])) {
            $data['nombre'] = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
            unset($data['first_name'], $data['last_name']);
        }

        if (empty($data['password'])) {
            $partes = preg_split('/\s+/', trim($data['nombre'] ?? 'Usuario'), 2);
            $data['password'] = ($partes[0] ?? 'User').substr($partes[1] ?? 'X', 0, 1).now()->year;
        }

        $data['esta_activo'] = $data['esta_activo'] ?? true;

        $user = $this->repository->create($data);

        if ($clienteId) {
            Cliente::query()
                ->where('id', $clienteId)
                ->whereNull('usuario_id')
                ->update(['usuario_id' => $user->id]);
        }

        if ($veterinarioId) {
            Veterinario::query()
                ->where('id', $veterinarioId)
                ->whereNull('usuario_id')
                ->update(['usuario_id' => $user->id]);
        }

        return $user;
    }

    public function update(string $id, array $data)
    {
        if (isset($data['role_id'])) {
            $data['rol_id'] = $data['role_id'];
            unset($data['role_id']);
        }

        if (isset($data['first_name']) || isset($data['last_name'])) {
            $data['nombre'] = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
            unset($data['first_name'], $data['last_name']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (! array_key_exists('esta_activo', $data)) {
            unset($data['esta_activo']);
        }

        unset($data['cliente_id'], $data['veterinario_id'], $data['reactivar_usuario_id']);

        return $this->repository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }
}
