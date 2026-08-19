<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_stats', function (Blueprint $table) {
            // Traffic-source (google/bing). Bestaande rijen zijn allemaal Google.
            $table->string('source')->default('google')->after('offer_id');
        });

        DB::table('offer_stats')->update(['source' => 'google']);

        // Unique nu per (datum, offer, source) zodat Google en Bing naast elkaar
        // kunnen bestaan en per-source upserts betrouwbaar werken.
        Schema::table('offer_stats', function (Blueprint $table) {
            $table->dropUnique(['stat_date', 'offer_id']);
            $table->unique(['stat_date', 'offer_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('offer_stats', function (Blueprint $table) {
            $table->dropUnique(['stat_date', 'offer_id', 'source']);
            $table->unique(['stat_date', 'offer_id']);
            $table->dropColumn('source');
        });
    }
};
