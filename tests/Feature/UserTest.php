<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_home_page_is_accessible(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_user_can_be_created_in_database(): void
    {
        $user = Usuario::factory()->create([
            'nombre' => 'Ruben Cano',
            'email' => 'ruben@ejemplo.com',
        ]);

        $this->assertDatabaseHas('usuarios', [
            'email' => 'ruben@ejemplo.com',
            'nombre' => 'Ruben Cano',
        ]);

        $this->assertEquals('Ruben Cano', $user->fresh()->full_name);
    }
}
