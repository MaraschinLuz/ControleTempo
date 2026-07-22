<?php

namespace App\Enums;

enum EntryType: string
{
    case Timer = 'timer';
    case Manual = 'manual';

    public function label(): string
    {
        return $this === self::Timer ? 'Cronômetro' : 'Manual';
    }
}
