<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arch_external_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('component_code'); // A7_ROOF, A8_EXT_WALL, A9_APRON_DRAIN, A10_CAR_PARK
            $table->integer('sample_index');
            $table->string('label'); // e.g. "Roof - Section 3"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arch_external_samples');
    }
};
