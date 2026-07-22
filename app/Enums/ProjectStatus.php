<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planejado', self::Active => 'Ativo',
            self::Paused => 'Pausado', self::Completed => 'Concluído',
        };
    }
}
