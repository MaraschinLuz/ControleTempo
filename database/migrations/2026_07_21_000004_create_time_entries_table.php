<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->dateTimeTz('started_at')->index();
            $table->dateTimeTz('ended_at')->nullable()->index();
            $table->unsignedBigInteger('duration_seconds')->default(0);
            $table->string('entry_type')->index();
            $table->string('status')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTimeTz('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('justification')->nullable();
            $table->boolean('is_edited')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'started_at', 'ended_at']);
        });

        DB::statement("CREATE UNIQUE INDEX time_entries_one_running_per_user ON time_entries (user_id) WHERE status = 'running' AND deleted_at IS NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
