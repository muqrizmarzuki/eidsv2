<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('component_assessments', function (Blueprint $table) {
            $table->foreignId('sample_id')->nullable()->change();
            $table->foreignId('external_sample_id')->nullable()->after('sample_id')
                  ->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('component_assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('external_sample_id');
            $table->foreignId('sample_id')->nullable(false)->change();
        });
    }
};
