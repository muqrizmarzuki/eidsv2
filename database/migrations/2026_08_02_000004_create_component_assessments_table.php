<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sample_id')->constrained('project_samples')->cascadeOnDelete();
            $table->string('component_code');
            $table->string('component_name');
            $table->decimal('weightage', 5, 2);
            $table->enum('finishing_status', ['PASS', 'FAIL'])->default('PASS');
            $table->enum('hollow_status', ['PASS', 'FAIL'])->default('PASS');
            $table->decimal('levelling_mm', 5, 2)->nullable();
            $table->enum('levelling_status', ['PASS', 'FAIL'])->default('PASS');
            $table->decimal('joint_mm', 5, 2)->nullable();
            $table->enum('joint_status', ['PASS', 'FAIL'])->default('PASS');
            $table->enum('crack_status', ['PASS', 'FAIL'])->default('PASS');
            $table->enum('overall_sample_status', ['PASS', 'FAIL'])->default('PASS');
            $table->string('photo_path')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_assessments');
    }
};
