<?php

namespace Database\Seeders;

use App\Models\Servicios\Servicio;
use Illuminate\Database\Seeder;

class ServicioSeeder extends Seeder
{
    public function run(): void
    {
        $servicios = [
            ['nombre' => 'Consulta médica', 'descripcion' => 'Evaluación clínica general', 'precio' => 80.00],
            ['nombre' => 'Vacunación', 'descripcion' => 'Aplicación de vacunas', 'precio' => 50.00],
            ['nombre' => 'Cirugía menor', 'descripcion' => 'Procedimientos quirúrgicos menores', 'precio' => 200.00],
            ['nombre' => 'Peluquería canina', 'descripcion' => 'Baño y corte de pelo', 'precio' => 60.00],
            ['nombre' => 'Desparasitación', 'descripcion' => 'Control de parásitos internos y externos', 'precio' => 40.00],
        ];

        foreach ($servicios as $servicio) {
            Servicio::firstOrCreate(
                ['nombre' => $servicio['nombre']],
                array_merge($servicio, ['esta_activo' => true])
            );
        }
    }
}
