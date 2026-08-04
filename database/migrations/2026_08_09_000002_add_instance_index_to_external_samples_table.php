<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_samples', function (Blueprint $table) {
            // Which physical instance of the element this section belongs to
            // (e.g. Playground #2 of 3) — sample_index is the section number
            // *within* that instance (Table 6's "10m length section per sample").
            $table->integer('instance_index')->default(1)->after('element_code');
        });
    }

    public function down(): void
    {
        Schema::table('external_samples', function (Blueprint $table) {
            $table->dropColumn('instance_index');
        });
    }
};
