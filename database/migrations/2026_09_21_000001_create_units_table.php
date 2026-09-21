<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('unit_reference');
            $table->string('owner_name')->nullable();
            $table->string('house_type')->nullable();
            $table->string('owner_phone')->nullable();
            $table->string('owner_address')->nullable();
            $table->date('report_date')->nullable();
            $table->date('rectification_deadline')->nullable();
            $table->date('handover_date')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'unit_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
