<?php

namespace App\Repositories\Servicios;

use App\Models\Servicios\Raza;

class RazaRepository
{
    public function __construct(protected Raza $model) {}

    public function getAll()
    {
        return $this->model
            ->with('especie:id,nombre')
            ->orderByDesc('actualizado_en')
            ->paginate(15);
    }

    public function findById($id)
    {
        return $this->model->with('especie:id,nombre')->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    public function search()
    {
        $search = trim((string) request('search', ''));
        $specieId = (int) request('specie_id', request('especie_id', 0));

        $query = $this->model->newQuery()->with('especie:id,nombre');

        if ($specieId > 0) {
            $query->where('especie_id', $specieId);
        } elseif ($search === '') {
            return collect();
        }

        if ($search !== '') {
            $query->where('nombre', 'like', "%{$search}%");
        }

        return $query->orderBy('nombre')->orderBy('id')->get()
            ->unique(fn (Raza $r) => mb_strtolower(trim($r->nombre ?? '')))
            ->values();
    }

    public function findOrCreate($name, $specieId)
    {
        return $this->model->firstOrCreate([
            'nombre' => $name,
            'especie_id' => $specieId,
        ]);
    }
}
