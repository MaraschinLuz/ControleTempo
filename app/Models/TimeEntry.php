<?php

namespace App\Models;

use App\Enums\EntryStatus;
use App\Enums\EntryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'project_id', 'activity_id', 'description', 'started_at', 'ended_at',
        'duration_seconds', 'entry_type', 'status', 'created_by', 'approved_by', 'approved_at',
        'rejection_reason', 'justification', 'is_edited',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime', 'ended_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime', 'duration_seconds' => 'integer',
            'entry_type' => EntryType::class, 'status' => EntryStatus::class, 'is_edited' => 'boolean',
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

    public function activity()
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function audits()
    {
        return $this->hasMany(TimeEntryAudit::class);
    }

    public function scopeCounted(Builder $query): Builder
    {
        return $query->whereIn('status', [EntryStatus::Approved->value, EntryStatus::Completed->value]);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isManagerOrAdmin() ? $query : $query->where('user_id', $user->id);
    }

    public function getFormattedDurationAttribute(): string
    {
        $hours = intdiv($this->duration_seconds, 3600);
        $minutes = intdiv($this->duration_seconds % 3600, 60);

        return ($hours ? $hours.'h' : '').($minutes ? $minutes.'min' : ($hours ? '' : '0min'));
    }
}
