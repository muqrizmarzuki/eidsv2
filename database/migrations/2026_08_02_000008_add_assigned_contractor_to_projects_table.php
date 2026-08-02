<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('assigned_contractor_id')->nullable()->after('assigned_to')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['assigned_contractor_id']);
            $table->dropColumn('assigned_contractor_id');
        });
    }
};
