<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')
                  ->nullable()
                  ->constrained('component_assessments')
                  ->nullOnDelete();
            $table->string('component_name');
            $table->string('location');
            $table->text('defect_description');
            $table->string('photo_path')->nullable();
            $table->enum('severity', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'RESOLVED'])->default('OPEN');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defects');
    }
};
