<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('component_assessments', function (Blueprint $table) {
            $table->dropColumn([
                'component_name', 'weightage',
                'finishing_status', 'hollow_status',
                'levelling_mm', 'levelling_status',
                'joint_mm', 'joint_status',
                'crack_status',
            ]);
            $table->boolean('na')->default(false)->after('component_code');
        });
    }

    public function down(): void
    {
        Schema::table('component_assessments', function (Blueprint $table) {
            $table->dropColumn('na');
            $table->string('component_name')->after('component_code');
            $table->decimal('weightage', 5, 2)->after('component_name');
            $table->enum('finishing_status', ['PASS', 'FAIL'])->default('PASS');
            $table->enum('hollow_status', ['PASS', 'FAIL'])->default('PASS');
            $table->decimal('levelling_mm', 5, 2)->nullable();
            $table->enum('levelling_status', ['PASS', 'FAIL'])->default('PASS');
            $table->decimal('joint_mm', 5, 2)->nullable();
            $table->enum('joint_status', ['PASS', 'FAIL'])->default('PASS');
            $table->enum('crack_status', ['PASS', 'FAIL'])->default('PASS');
        });
    }
};
