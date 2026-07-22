<?php

namespace App\Enums;

enum EntryStatus: string
{
    case Running = 'running';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Running => 'Em andamento', self::Pending => 'Pendente',
            self::Approved => 'Aprovado', self::Rejected => 'Rejeitado',
            self::Completed => 'Concluído', self::Cancelled => 'Cancelado',
        };
    }
}
