<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('applies_to'); // component_code, e.g. A1_FLOOR
            $table->string('defect_group'); // e.g. "Finishing", "Alignment and Evenness"
            $table->integer('sort_order')->default(0);
            $table->text('question_text');
            $table->string('method_tool')->nullable();
            $table->string('tolerance_text')->nullable(); // e.g. "<= 3 mm / 1.2 m", null = no numeric tolerance
            $table->enum('input_type', ['pass_fail', 'numeric_with_tolerance'])->default('pass_fail');
            $table->decimal('tolerance_max_mm', 6, 2)->nullable(); // numeric threshold used for auto PASS/FAIL
            $table->text('guide_tools')->nullable();
            $table->json('guide_procedure')->nullable(); // ordered list of step strings
            $table->string('guide_result_thresholds')->nullable();
            $table->json('guide_photos')->nullable(); // list of {url, caption}
            $table->timestamps();

            $table->index('applies_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};
