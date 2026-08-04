<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sampling_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('building_category', ['A', 'B', 'C', 'D'])->unique();
            $table->decimal('gfa_divisor', 8, 2);
            $table->integer('min_samples');
            $table->integer('max_samples');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sampling_rules');
    }
};
