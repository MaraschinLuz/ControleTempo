<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_schedule_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('column_1', 100)->nullable();
            $table->string('column_2', 100)->nullable();
            $table->text('demand')->nullable();
            $table->text('ai_suggestion')->nullable();
            $table->string('completion_status', 30)->nullable();
            $table->date('execution_date')->nullable();
            $table->string('responsible')->nullable();
            $table->string('client_responsible')->nullable();
            $table->string('client_contact')->nullable();
            $table->text('scope')->nullable();
            $table->text('completed_demands')->nullable();
            $table->text('remaining_work')->nullable();
            $table->date('completion_date')->nullable();
            $table->decimal('hours', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_schedule_rows');
    }
};
