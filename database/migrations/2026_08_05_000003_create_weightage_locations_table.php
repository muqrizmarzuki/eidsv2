<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weightage_locations', function (Blueprint $table) {
            $table->id();
            $table->enum('building_category', ['A', 'B', 'C', 'D'])->unique();
            $table->decimal('principal_pct', 5, 2);
            $table->decimal('service_pct', 5, 2);
            $table->decimal('circulation_pct', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weightage_locations');
    }
};
