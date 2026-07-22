<?php

namespace App\Enums;

enum UserRole: string
{
    case Collaborator = 'collaborator';
    case Manager = 'manager';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Collaborator => 'Colaborador',
            self::Manager => 'Gestor',
            self::Admin => 'Administrador',
        };
    }
}
