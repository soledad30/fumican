<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Tests\JetstreamTestCase;

class DeleteApiTokenTest extends JetstreamTestCase
{

    public function test_api_tokens_can_be_deleted(): void
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

        $this->delete('/user/api-tokens/'.$token->id);

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
