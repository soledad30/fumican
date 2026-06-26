<?php

namespace App\Repositories\Servicios;

use App\Models\Servicios\Especie;

class EspecieRepository
{
    public function __construct(protected Especie $model) {}

    public function getAll()
    {
        return $this->model
            ->orderByDesc('actualizado_en')
            ->paginate(15);
    }

    public function findById($id)
    {
        return $this->model->findOrFail($id);
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
        $query = $this->model->newQuery();

        if ($search !== '') {
            $query->where('nombre', 'like', "%{$search}%");
        }

        return $query->orderBy('nombre')->orderBy('id')->get()
            ->unique(fn (Especie $e) => mb_strtolower(trim($e->nombre ?? '')))
            ->values();
    }

    public function findOrCreate($name)
    {
        return $this->model->firstOrCreate(['nombre' => $name]);
    }

    public function listAll()
    {
        return $this->model->orderBy('nombre')->orderBy('id')->get()
            ->unique(fn (Especie $e) => mb_strtolower(trim($e->nombre ?? '')))
            ->values();
    }
}
