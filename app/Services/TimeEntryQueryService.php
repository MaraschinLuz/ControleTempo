<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class TimeEntryQueryService
{
    public function apply(Builder $query, array $filters): Builder
    {
        $sort = in_array($filters['sort'] ?? '', ['started_at', 'duration_seconds', 'status', 'created_at'], true) ? $filters['sort'] : 'started_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('started_at', '>=', $v.' 00:00:00'))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('started_at', '<=', $v.' 23:59:59'))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['client_id'] ?? null, fn ($q, $v) => $q->whereHas('project', fn ($p) => $p->where('client_id', $v)))
            ->when($filters['project_id'] ?? null, fn ($q, $v) => $q->where('project_id', $v))
            ->when($filters['activity_id'] ?? null, fn ($q, $v) => $q->where('activity_id', $v))
            ->when($filters['entry_type'] ?? null, fn ($q, $v) => $q->where('entry_type', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderBy($sort, $direction);
    }
}
