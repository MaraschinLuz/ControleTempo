<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Model;

class TimeEntryAudit extends Model
{
    protected $fillable = ['time_entry_id', 'changed_by', 'action', 'old_values', 'new_values'];

    protected function casts(): array
    {
        return ['action' => AuditAction::class, 'old_values' => 'array', 'new_values' => 'array'];
    }

    public function timeEntry()
    {
        return $this->belongsTo(TimeEntry::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by')->withTrashed();
    }
}
