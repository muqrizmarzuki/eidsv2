<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_elements', function (Blueprint $table) {
            $table->id();
            $table->string('element_code')->unique(); // e.g. EXT_DRAIN
            $table->string('name');
            $table->string('group'); // Infrastructure / Facilities or Amenities
            $table->boolean('default_present')->default(true);
            $table->integer('sample_count')->default(1); // Table 6 sampling guideline, fixed count
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_elements');
    }
};
