<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_external_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('element_code');
            $table->boolean('present')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'element_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_external_elements');
    }
};
