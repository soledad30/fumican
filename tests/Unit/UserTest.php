<?php

namespace Tests\Unit;

use App\Models\Usuario;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_user_full_name_accessor_return_correct_format(): void
    {
        $user = Usuario::factory()->create([
            'nombre' => 'Ruben Cano',
        ]);

        $this->assertEquals('Ruben Cano', $user->full_name);
    }

    public function test_example(): void
    {
        $this->assertTrue(true);
    }
}
