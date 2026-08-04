<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('component_assessments', function (Blueprint $table) {
            $table->foreignId('arch_sample_id')->nullable()->after('external_sample_id')
                  ->constrained('arch_external_samples')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('component_assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('arch_sample_id');
        });
    }
};
