<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Tests\JetstreamTestCase;

class ProfileInformationTest extends JetstreamTestCase
{

    public function test_profile_information_can_be_updated(): void
    {
        $this->actingAs($user = Usuario::factory()->create());

        $this->put('/user/profile-information', [
            'name' => 'Test Name',
            'email' => 'test@example.com',
        ]);

        $this->assertEquals('Test Name', $user->fresh()->name);
        $this->assertEquals('test@example.com', $user->fresh()->email);
    }
}
