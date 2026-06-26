<?php

namespace Tests\Concerns;

trait SkipsLegacyJetstreamTests
{
    protected function skipIfLegacyJetstream(): void
    {
        $this->markTestSkipped(
            'Prueba Jetstream legacy incompatible con el esquema Usuario/roles del grupo.'
        );
    }
}
