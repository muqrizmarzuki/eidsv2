<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_no')->unique();
            $table->string('project_name');
            $table->string('location')->nullable();
            $table->string('developer_name');
            $table->string('contractor_name');
            $table->enum('building_type', ['teres', 'semi_d', 'banglo'])->default('teres');
            $table->integer('total_units')->default(1);
            $table->decimal('floor_area_sqm', 8, 2)->default(0.00);
            $table->integer('calculated_samples')->default(1);
            $table->decimal('overall_score', 5, 2)->default(0.00);
            $table->enum('status', ['draf', 'dalam_pemeriksaan', 'selesai'])
                  ->default('dalam_pemeriksaan');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
