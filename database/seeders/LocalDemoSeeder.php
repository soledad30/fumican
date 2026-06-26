<?php

namespace Database\Seeders;

use App\Enums\RolEnum;
use App\Models\Servicios\Cliente;
use App\Models\Servicios\Especie;
use App\Models\Servicios\Mascota;
use App\Models\Servicios\Raza;
use App\Models\Servicios\Veterinario;
use App\Models\Servicios\Servicio;
use App\Models\Usuario;
use App\Models\Usuarios\Rol;
use App\Models\Ventas\Almacen;
use App\Models\Ventas\Categoria;
use App\Models\Ventas\Producto;
use App\Models\Ventas\Proveedor;
use Illuminate\Database\Seeder;

class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedEspeciesYRazas();
        $this->seedClientesYMascotas();
        $this->seedVeterinarios();
        $this->seedVentas();
        $this->seedServicios();
        $this->call(DemoCompletoSeeder::class);
    }

    private function seedEspeciesYRazas(): void
    {
        $datos = [
            'Canino' => ['Labrador', 'Pastor Alemán', 'Bulldog'],
            'Felino' => ['Siamés', 'Persa', 'Mestizo'],
            'Ave' => ['Canario', 'Periquito'],
        ];

        foreach ($datos as $especieNombre => $razas) {
            $especie = Especie::firstOrCreate(['nombre' => $especieNombre]);
            foreach ($razas as $razaNombre) {
                Raza::firstOrCreate(
                    ['nombre' => $razaNombre, 'especie_id' => $especie->id],
                    ['nombre' => $razaNombre, 'especie_id' => $especie->id]
                );
            }
        }
    }

    private function seedClientesYMascotas(): void
    {
        $nombres = [
            ['María', 'López'],
            ['Carlos', 'Pérez'],
            ['Ana', 'Gutiérrez'],
            ['Luis', 'Fernández'],
            ['Sofía', 'Ramos'],
        ];

        $razas = Raza::all();
        $rolCliente = Rol::where('nombre', RolEnum::CLIENTE->value)->first();

        foreach ($nombres as $index => [$nombre, $apellido]) {
            $cliente = Cliente::create([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'ci' => (string) fake()->unique()->numerify('########'),
                'telefono' => fake()->phoneNumber(),
                'genero' => fake()->randomElement(['masculino', 'femenino']),
                'direccion' => fake()->address(),
            ]);

            if ($index === 0 && $rolCliente) {
                $usuarioCliente = Usuario::create([
                    'nombre' => "{$nombre} {$apellido}",
                    'email' => 'cliente@demo.vet',
                    'password' => bcrypt('12345678'),
                    'esta_activo' => true,
                    'rol_id' => $rolCliente->id,
                ]);
                $cliente->update(['usuario_id' => $usuarioCliente->id]);
            }

            Mascota::create([
                'nombre' => fake()->firstName(),
                'peso' => fake()->randomFloat(1, 2, 35),
                'color' => fake()->safeColorName(),
                'genero' => fake()->randomElement(['macho', 'hembra']),
                'fecha_nacimiento' => fake()->date(),
                'cliente_id' => $cliente->id,
                'raza_id' => $razas->random()->id,
            ]);
        }
    }

    private function seedVeterinarios(): void
    {
        Veterinario::create([
            'nombre' => 'Roberto',
            'apellido' => 'Méndez',
            'ci' => '1234567',
            'telefono' => '70000001',
            'email' => 'roberto.mendez@demo.vet',
            'licencia' => 'VET-001',
            'es_especialista' => false,
            'esta_activo' => true,
        ]);

        Veterinario::create([
            'nombre' => 'Laura',
            'apellido' => 'Vargas',
            'ci' => '7654321',
            'telefono' => '70000002',
            'email' => 'laura.vargas@demo.vet',
            'licencia' => 'VET-002',
            'es_especialista' => true,
            'especialidad' => 'Cirugía',
            'esta_activo' => true,
        ]);
    }

    private function seedVentas(): void
    {
        $categorias = ['Antibióticos', 'Analgésicos', 'Vacunas', 'Alimentos', 'Higiene'];
        foreach ($categorias as $nombre) {
            Categoria::firstOrCreate(['nombre' => $nombre]);
        }

        $categoriaIds = Categoria::pluck('id');

        foreach (range(1, 15) as $i) {
            Producto::create([
                'nombre' => "Producto demo {$i}",
                'dosificacion' => '1 comprimido / 8h',
                'fabricante' => 'VetPharma',
                'fecha_vencimiento' => now()->addYear()->toDateString(),
                'sustancia_controlada' => false,
                'categoria_id' => $categoriaIds->random(),
            ]);
        }

        foreach (range(1, 5) as $i) {
            Proveedor::firstOrCreate(
                ['nombre' => "Proveedor {$i}"],
                [
                    'pais' => 'Bolivia',
                    'telefono' => fake()->phoneNumber(),
                    'email' => fake()->companyEmail(),
                    'direccion' => fake()->address(),
                ]
            );
        }

        Almacen::firstOrCreate(
            ['nombre' => 'Almacén Central'],
            ['ubicacion' => 'Planta baja', 'descripcion' => 'Principal']
        );
        Almacen::firstOrCreate(
            ['nombre' => 'Farmacia'],
            ['ubicacion' => 'Recepción', 'descripcion' => 'Venta directa']
        );
    }

    private function seedServicios(): void
    {
        $servicios = [
            ['nombre' => 'Consulta médica', 'descripcion' => 'Evaluación clínica general', 'precio' => 80.00],
            ['nombre' => 'Vacunación', 'descripcion' => 'Aplicación de vacunas', 'precio' => 50.00],
            ['nombre' => 'Cirugía menor', 'descripcion' => 'Procedimientos quirúrgicos menores', 'precio' => 200.00],
            ['nombre' => 'Peluquería canina', 'descripcion' => 'Baño y corte de pelo', 'precio' => 60.00],
            ['nombre' => 'Desparasitación', 'descripcion' => 'Control de parásitos', 'precio' => 40.00],
        ];

        foreach ($servicios as $servicio) {
            Servicio::firstOrCreate(
                ['nombre' => $servicio['nombre']],
                array_merge($servicio, ['esta_activo' => true])
            );
        }
    }
}
