<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $table->text('rectification_notes')->nullable()->after('defect_description');
        });
    }

    public function down(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $table->dropColumn('rectification_notes');
        });
    }
};
