<?php

namespace App\Services\Servicios;

use App\Models\Servicios\Mascota;
use App\Repositories\Servicios\MascotaRepository;
use App\Support\LegacyFieldMapper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MascotaService
{
    public function __construct(protected MascotaRepository $repository) {}

    public function getAll()
    {
        return $this->repository->getAll();
    }

    public function listForSelect()
    {
        return $this->repository->listForSelect();
    }

    public function getById($id)
    {
        return $this->repository->findById($id);
    }

    public function create(array $data, ?UploadedFile $photo = null)
    {
        $pet = $this->repository->create(LegacyFieldMapper::mascota($data));

        if ($photo) {
            $this->guardarFoto($pet, $photo);
            $pet->refresh();
        }

        return $pet->load(['owner', 'breed.specie']);
    }

    public function update($id, array $data, ?UploadedFile $photo = null)
    {
        $pet = $this->repository->findById($id);
        $this->repository->update($id, LegacyFieldMapper::mascota($data));

        if ($photo) {
            $this->guardarFoto($pet, $photo);
            $pet->refresh();
        }

        return $pet->load(['owner', 'breed.specie']);
    }

    public function guardarFoto(Mascota $pet, UploadedFile $photo): void
    {
        if ($pet->url_foto) {
            Storage::disk('public')->delete($pet->url_foto);
        }

        $path = $photo->store('mascotas', 'public');
        $pet->update(['url_foto' => $path]);
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }

    public function search(array $filters)
    {
        return $this->repository->searchWithFilters($filters);
    }

    public function autocompleteSearch(?string $term)
    {
        if (empty($term)) {
            return [];
        }

        $pets = $this->repository->autocompleteSearch($term);
        foreach ($pets as $pet) {
            $pet->owner_full_name = $pet->owner?->first_name.' '.$pet->owner?->last_name;
            $pet->specie_and_breed = $pet->breed?->specie?->name.' - '.$pet->breed?->name;
        }

        return $pets;
    }
}
