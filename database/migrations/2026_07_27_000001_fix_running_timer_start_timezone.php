<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('time_entries')
            ->where('status', 'running')
            ->where('entry_type', 'timer')
            ->update(['started_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // Data correction: the previous, timezone-shifted value cannot be recovered safely.
    }
};
