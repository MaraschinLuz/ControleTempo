<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectScheduleRow extends Model
{
    protected $fillable = [
        'position',
        'column_1',
        'column_2',
        'demand',
        'ai_suggestion',
        'completion_status',
        'execution_date',
        'responsible',
        'client_responsible',
        'client_contact',
        'scope',
        'completed_demands',
        'remaining_work',
        'completion_date',
        'hours',
    ];

    protected function casts(): array
    {
        return [
            'execution_date' => 'date',
            'completion_date' => 'date',
            'hours' => 'decimal:2',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
