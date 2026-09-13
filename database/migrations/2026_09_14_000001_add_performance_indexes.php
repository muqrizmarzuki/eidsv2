<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $table->index(['project_id', 'status']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('status');
            $table->index('overall_score');
        });

        Schema::table('component_assessments', function (Blueprint $table) {
            $table->index(['project_id', 'component_code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'status']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['overall_score']);
        });

        Schema::table('component_assessments', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'component_code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });
    }
};
