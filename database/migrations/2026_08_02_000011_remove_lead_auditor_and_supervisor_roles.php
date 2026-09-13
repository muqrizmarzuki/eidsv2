<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('project_supervisor');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text IN ('admin', 'inspector', 'contractor'))");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'inspector', 'contractor'])
                      ->default('inspector')
                      ->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text IN ('admin', 'lead_auditor', 'inspector', 'supervisor', 'contractor'))");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'lead_auditor', 'inspector', 'supervisor', 'contractor'])
                      ->default('inspector')
                      ->change();
            });
        }

        Schema::create('project_supervisor', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'user_id']);
        });
    }
};
