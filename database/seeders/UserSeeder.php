<?php

namespace Database\Seeders;

use App\Enums\RolEnum;
use App\Models\Usuario;
use App\Models\Usuarios\Rol;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $rolPropietario = Rol::where('nombre', RolEnum::PROPIETARIO->value)->first();

        Usuario::create([
            'nombre' => 'Juan Pablo Rodriguez',
            'email' => 'juancho123sc@gmail.com',
            'password' => bcrypt('12345678'),
            'esta_activo' => true,
            'rol_id' => $rolPropietario?->id,
        ]);

        Usuario::create([
            'nombre' => 'Jose Armando Gutierrez Lopez',
            'email' => 'armando@gmail.com',
            'password' => bcrypt('12345678'),
            'esta_activo' => true,
            'rol_id' => $rolPropietario?->id,
        ]);

        $rolRecepcionista = Rol::where('nombre', RolEnum::RECEPCIONISTA->value)->first();
        Usuario::firstOrCreate(
            ['email' => 'recepcion@demo.vet'],
            [
                'nombre' => 'Sofía Recepción',
                'password' => bcrypt('12345678'),
                'esta_activo' => true,
                'rol_id' => $rolRecepcionista?->id,
            ]
        );

        $roles = Rol::whereIn('nombre', [
            RolEnum::VETERINARIO->value,
            RolEnum::CLIENTE->value,
            RolEnum::ADMINISTRADOR->value,
        ])->get();

        Usuario::factory()->count(10)->create()->each(function (Usuario $user) use ($roles) {
            $user->update(['rol_id' => $roles->random()->id]);
        });
    }
}
