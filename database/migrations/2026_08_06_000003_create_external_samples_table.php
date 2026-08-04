<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('element_code');
            $table->integer('sample_index');
            $table->string('label'); // e.g. "External Drain #1"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_samples');
    }
};
