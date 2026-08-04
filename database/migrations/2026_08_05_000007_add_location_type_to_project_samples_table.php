<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_samples', function (Blueprint $table) {
            $table->enum('location_type', ['principal', 'service', 'circulation'])
                  ->default('principal')->after('location_name');
        });
    }

    public function down(): void
    {
        Schema::table('project_samples', function (Blueprint $table) {
            $table->dropColumn('location_type');
        });
    }
};
