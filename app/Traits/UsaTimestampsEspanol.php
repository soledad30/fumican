<?php

namespace App\Traits;

trait UsaTimestampsEspanol
{
    public function getCreatedAtColumn(): string
    {
        return 'creado_en';
    }

    public function getUpdatedAtColumn(): string
    {
        return 'actualizado_en';
    }
}
