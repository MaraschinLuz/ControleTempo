<?php

namespace App\Policies;

use App\Enums\EntryStatus;
use App\Models\Setting;
use App\Models\TimeEntry;
use App\Models\User;

class TimeEntryPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TimeEntry $entry): bool
    {
        return $user->isManager() || $entry->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->active;
    }

    public function update(User $user, TimeEntry $entry): bool
    {
        if ($user->isManager()) {
            return true;
        }

        return $entry->user_id === $user->id && $entry->status !== EntryStatus::Running && Setting::bool('allow_collaborator_edit', true);
    }

    public function delete(User $user, TimeEntry $entry): bool
    {
        if ($user->isManager()) {
            return true;
        }

        return $entry->user_id === $user->id && Setting::bool('allow_collaborator_delete', false);
    }

    public function controlTimer(User $user, TimeEntry $entry): bool
    {
        return $entry->user_id === $user->id && $entry->status === EntryStatus::Running;
    }

    public function approve(User $user, TimeEntry $entry): bool
    {
        return $user->isManager();
    }

    public function viewAudit(User $user, TimeEntry $entry): bool
    {
        return $user->isManager();
    }
}
