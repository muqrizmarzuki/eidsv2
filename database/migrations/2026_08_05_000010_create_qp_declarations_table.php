<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qp_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('item_code', ['QP_SKIM_COAT', 'QP_WATER_TIGHTNESS']);
            $table->boolean('declared')->default(false);
            $table->string('evidence_path')->nullable();
            $table->timestamp('declared_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'item_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qp_declarations');
    }
};
