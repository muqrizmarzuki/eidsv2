<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->constrained()->cascadeOnDelete();
            $table->enum('result', ['PASS', 'FAIL'])->default('PASS');
            $table->decimal('numeric_value', 8, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['component_assessment_id', 'checklist_item_id'], 'assessment_answers_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
    }
};
