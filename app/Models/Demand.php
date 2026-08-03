<?php

namespace App\Models;

use App\Enums\DemandPriority;
use App\Enums\DemandStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Demand extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'status' => DemandStatus::class,
            'priority' => DemandPriority::class,
            'due_date' => 'date',
            'position' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function project()
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }
}
