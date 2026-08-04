<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weightage_architectural_elements', function (Blueprint $table) {
            // 'room' = inspected at internal-finish room samples (Floor, Wall...).
            // 'building' = Table 3's "External finishes" group (Roof, External
            // Wall, Apron/Drain, Car Park) — sampled via their own building-level
            // sections/lengths per Table 3, never tied to a specific room.
            $table->enum('sampling_scope', ['room', 'building'])->default('room')->after('scoring_mode');
        });
    }

    public function down(): void
    {
        Schema::table('weightage_architectural_elements', function (Blueprint $table) {
            $table->dropColumn('sampling_scope');
        });
    }
};
