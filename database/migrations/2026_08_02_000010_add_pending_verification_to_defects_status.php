<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE defects DROP CONSTRAINT IF EXISTS defects_status_check");
            DB::statement("ALTER TABLE defects ADD CONSTRAINT defects_status_check CHECK (status::text IN ('OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION', 'RESOLVED'))");
        } else {
            Schema::table('defects', function (Blueprint $table) {
                $table->enum('status', ['OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION', 'RESOLVED'])
                      ->default('OPEN')
                      ->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE defects DROP CONSTRAINT IF EXISTS defects_status_check");
            DB::statement("ALTER TABLE defects ADD CONSTRAINT defects_status_check CHECK (status::text IN ('OPEN', 'IN_PROGRESS', 'RESOLVED'))");
        } else {
            Schema::table('defects', function (Blueprint $table) {
                $table->enum('status', ['OPEN', 'IN_PROGRESS', 'RESOLVED'])
                      ->default('OPEN')
                      ->change();
            });
        }
    }
};
