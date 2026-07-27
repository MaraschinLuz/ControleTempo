<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_schedule_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('column_key', 80);
            $table->string('label');
            $table->string('type', 30);
            $table->unsignedSmallInteger('width')->default(220);
            $table->boolean('is_custom')->default(false);
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['project_id', 'column_key']);
            $table->unique(['project_id', 'position']);
        });

        Schema::table('project_schedule_rows', function (Blueprint $table) {
            $table->json('custom_data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('project_schedule_rows', function (Blueprint $table) {
            $table->dropColumn('custom_data');
        });

        Schema::dropIfExists('project_schedule_columns');
    }
};
