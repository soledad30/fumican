<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Tests\JetstreamTestCase;

class ApiTokenPermissionsTest extends JetstreamTestCase
{

    public function test_api_token_permissions_can_be_updated(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        $this->actingAs($user = Usuario::factory()->withPersonalTeam()->create());

        $token = $user->tokens()->create([
            'name' => 'Test Token',
            'token' => Str::random(40),
            'abilities' => ['create', 'read'],
        ]);

        $this->put('/user/api-tokens/'.$token->id, [
            'name' => $token->name,
            'permissions' => [
                'delete',
                'missing-permission',
            ],
        ]);

        $this->assertTrue($user->fresh()->tokens->first()->can('delete'));
        $this->assertFalse($user->fresh()->tokens->first()->can('read'));
        $this->assertFalse($user->fresh()->tokens->first()->can('missing-permission'));
    }
}
