<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectScheduleColumn extends Model
{
    protected $fillable = [
        'column_key',
        'label',
        'type',
        'width',
        'is_custom',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'is_custom' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
