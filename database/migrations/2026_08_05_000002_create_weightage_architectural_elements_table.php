<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weightage_architectural_elements', function (Blueprint $table) {
            $table->id();
            $table->string('component_code')->unique();
            $table->string('name');
            $table->string('group'); // Internal finishes / External finishes / Material and functional test
            $table->decimal('breakdown_pct', 5, 2);
            $table->enum('scoring_mode', ['sample_average', 'declaration'])->default('sample_average');
            $table->boolean('optional')->default(false); // may legitimately not exist in a project
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weightage_architectural_elements');
    }
};
