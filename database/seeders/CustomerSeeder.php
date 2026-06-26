<?php

namespace Database\Seeders;

use App\Models\Servicios\Cliente;
use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cliente::factory()
            ->count(100)
            ->create();
    }
}
