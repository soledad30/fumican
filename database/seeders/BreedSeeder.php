<?php

namespace Database\Seeders;

use App\Models\Servicios\Raza;
use App\Models\Servicios\Especie;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BreedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $species = Especie::all();
        foreach ($species as $specie) {
            Raza::factory()
                ->count(3)
                ->create([
                    'specie_id' => $specie->id
                ]);
        }
    }
}
