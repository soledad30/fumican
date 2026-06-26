<?php

namespace App\Services\Servicios;

use App\Repositories\Servicios\VeterinarioRepository;
use App\Support\LegacyFieldMapper;

class VeterinarioService
{
    private const ESPECIALIDADES_BASE = [
        'Anestesiología',
        'Cardiología',
        'Cirugía',
        'Dermatología',
        'Etología',
        'Medicina felina',
        'Medicina interna',
        'Neurología',
        'Nutrición',
        'Odontología',
        'Oftalmología',
        'Oncología',
        'Radiología',
        'Reproducción',
        'Traumatología',
        'Medicina de animales exóticos',
    ];

    public function __construct(protected VeterinarioRepository $repository) {}

    public function listar(array $filters = [])
    {
        if (! empty($filters['search_term'])) {
            return $this->repository->search($filters);
        }

        return $this->repository->getAllPaginated();
    }

    public function buscar(array $filters)
    {
        return $this->listar($filters);
    }

    public function crear(array $data)
    {
        $mapped = LegacyFieldMapper::veterinario($data);

        return $this->repository->create($mapped);
    }

    public function actualizar(int $id, array $data)
    {
        $mapped = LegacyFieldMapper::veterinario($data);

        if (array_key_exists('is_specialist', $data) || array_key_exists('es_especialista', $data)) {
            $esEspecialista = filter_var(
                $data['is_specialist'] ?? $data['es_especialista'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            );
            $mapped['es_especialista'] = $esEspecialista;
            if (! $esEspecialista) {
                $mapped['especialidad'] = null;
            }
        }

        return $this->repository->update($id, $mapped);
    }

    public function eliminar(int $id)
    {
        return $this->repository->delete($id);
    }

    public function sinUsuario()
    {
        return $this->repository->sinUsuario();
    }

    public function listarEspecialidades(): array
    {
        return collect(self::ESPECIALIDADES_BASE)
            ->merge($this->repository->especialidadesDistintas())
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
