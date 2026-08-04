<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_external_elements', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->after('element_code');
        });

        // Backfill: previously-present elements get quantity 1 (the old
        // behaviour), previously-absent get 0.
        \DB::table('project_external_elements')->where('present', true)->update(['quantity' => 1]);
        \DB::table('project_external_elements')->where('present', false)->update(['quantity' => 0]);

        Schema::table('project_external_elements', function (Blueprint $table) {
            $table->dropColumn('present');
        });
    }

    public function down(): void
    {
        Schema::table('project_external_elements', function (Blueprint $table) {
            $table->boolean('present')->default(false);
        });
        \DB::table('project_external_elements')->update(['present' => \DB::raw('quantity > 0')]);
        Schema::table('project_external_elements', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
