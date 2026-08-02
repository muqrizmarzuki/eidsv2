<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('project_supervisor');

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'inspector', 'contractor'])
                  ->default('inspector')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'lead_auditor', 'inspector', 'supervisor', 'contractor'])
                  ->default('inspector')
                  ->change();
        });

        Schema::create('project_supervisor', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'user_id']);
        });
    }
};
