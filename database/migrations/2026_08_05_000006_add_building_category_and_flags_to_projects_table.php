<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->enum('building_category', ['A', 'B', 'C', 'D'])->default('A')->after('building_type');
            $table->boolean('car_park_present')->default(true)->after('building_category');
            $table->boolean('apron_drain_present')->default(true)->after('car_park_present');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['building_category', 'car_park_present', 'apron_drain_present']);
        });
    }
};
