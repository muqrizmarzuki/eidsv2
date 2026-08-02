<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION', 'RESOLVED'])
                  ->default('OPEN')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'RESOLVED'])
                  ->default('OPEN')
                  ->change();
        });
    }
};
