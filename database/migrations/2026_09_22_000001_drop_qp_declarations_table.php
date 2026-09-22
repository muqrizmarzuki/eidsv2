<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    private const QP_CODES = ['QP_SKIM_COAT', 'QP_WATER_TIGHTNESS'];

    public function up(): void
    {
        if (Schema::hasTable('qp_declarations')) {
            $disk = config('filesystems.default');
            DB::table('qp_declarations')->whereNotNull('evidence_path')->pluck('evidence_path')
                ->each(fn ($path) => Storage::disk($disk)->delete($path));
        }

        Schema::dropIfExists('qp_declarations');

        DB::table('weightage_architectural_elements')->whereIn('component_code', self::QP_CODES)->delete();
    }

    public function down(): void
    {
        Schema::create('qp_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('item_code', self::QP_CODES);
            $table->boolean('declared')->default(false);
            $table->string('evidence_path')->nullable();
            $table->timestamp('declared_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'item_code']);
        });

        DB::table('weightage_architectural_elements')->insertOrIgnore([
            ['component_code' => 'QP_SKIM_COAT', 'name' => 'Skim Coat or Prepacked Plaster', 'group' => 'Material and functional test', 'breakdown_pct' => 3, 'scoring_mode' => 'declaration', 'sampling_scope' => 'room', 'optional' => false, 'sort_order' => 11, 'created_at' => now(), 'updated_at' => now()],
            ['component_code' => 'QP_WATER_TIGHTNESS', 'name' => 'Wet-area Water-tightness Test', 'group' => 'Material and functional test', 'breakdown_pct' => 3, 'scoring_mode' => 'declaration', 'sampling_scope' => 'room', 'optional' => false, 'sort_order' => 12, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
};
