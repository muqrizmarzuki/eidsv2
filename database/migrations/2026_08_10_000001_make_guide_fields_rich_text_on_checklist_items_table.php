<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            // guide_tools/guide_procedure/guide_result_thresholds now store the
            // rich-text editor's HTML output (bold, lists, inline images) rather
            // than plain text/JSON arrays — guide_photos is dropped since images
            // are now embedded directly in that HTML instead of a separate list.
            $table->text('guide_procedure')->nullable()->change();
            $table->text('guide_result_thresholds')->nullable()->change();
            $table->dropColumn('guide_photos');
        });
    }

    public function down(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->json('guide_procedure')->nullable()->change();
            $table->string('guide_result_thresholds')->nullable()->change();
            $table->json('guide_photos')->nullable();
        });
    }
};
