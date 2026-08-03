<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['client_id', 'name', 'description', 'status', 'estimated_hours', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return ['status' => ProjectStatus::class, 'estimated_hours' => 'decimal:2', 'start_date' => 'date', 'end_date' => 'date'];
    }

    public function client()
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function scheduleRows()
    {
        return $this->hasMany(ProjectScheduleRow::class);
    }

    public function scheduleColumns()
    {
        return $this->hasMany(ProjectScheduleColumn::class);
    }

    public function demands()
    {
        return $this->hasMany(Demand::class);
    }

    public function isActive(): bool
    {
        return $this->status === ProjectStatus::Active && $this->client?->active;
    }
}
