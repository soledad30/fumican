<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Tests\JetstreamTestCase;

class BrowserSessionsTest extends JetstreamTestCase
{

    public function test_other_browser_sessions_can_be_logged_out(): void
    {
        $this->actingAs(Usuario::factory()->create());

        $response = $this->delete('/user/other-browser-sessions', [
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
    }
}
