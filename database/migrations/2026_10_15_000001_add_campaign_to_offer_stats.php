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
            // Google-campagne-id (uit RedTrack sub6). Lege string = onbekend
            // (historische data van vóór de split, of verkeer zonder sub6).
            $table->string('campaign_id')->default('')->after('source');
        });

        DB::table('offer_stats')->update(['campaign_id' => '']);

        // Unique nu ook per campagne zodat Search/PMax naast elkaar bestaan en
        // de per-campagne upserts betrouwbaar werken.
        Schema::table('offer_stats', function (Blueprint $table) {
            $table->dropUnique(['stat_date', 'offer_id', 'source']);
            $table->unique(['stat_date', 'offer_id', 'source', 'campaign_id'], 'offer_stats_date_offer_source_campaign_unique');
            $table->index(['campaign_id', 'stat_date'], 'offer_stats_campaign_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('offer_stats', function (Blueprint $table) {
            $table->dropIndex('offer_stats_campaign_date_index');
            $table->dropUnique('offer_stats_date_offer_source_campaign_unique');
            $table->unique(['stat_date', 'offer_id', 'source']);
            $table->dropColumn('campaign_id');
        });
    }
};
