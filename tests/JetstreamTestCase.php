<?php

namespace Tests;

/**
 * Base para pruebas Jetstream/Fortify heredadas del skeleton.
 * El proyecto usa Usuario + esquema del grupo; estas pruebas se omiten.
 */
abstract class JetstreamTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped(
            'Prueba Jetstream legacy incompatible con el modelo Usuario y esquema db_grupo23sa.'
        );
    }
}
