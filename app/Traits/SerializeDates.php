<?php

namespace App\Traits;

use DateTimeInterface;

trait SerializeDates
{
    protected function serializeDate(DateTimeInterface $date)
    {
        $timezone = config('app.timezone');
        return $date->setTimezone($timezone)->format('d-m-Y H:i:s');
    }
}
