<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Handmatige correcties die BOVENOP de gesynchte RedTrack-aggregaten
        // worden toegepast, zodat ze een re-sync overleven (offer_stats wordt
        // telkens overschreven vanuit RedTrack). Deltas per (datum, offer, source).
        Schema::create('stat_corrections', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date');
            $table->string('offer_id');            // sluit aan op offer_stats.offer_id
            $table->string('source')->default('google');

            $table->integer('d_lp_views')->default(0);
            $table->integer('d_lp_clicks')->default(0);
            $table->integer('d_clicks')->default(0);
            $table->integer('d_leads')->default(0);
            $table->integer('d_qleads')->default(0);
            $table->integer('d_sales')->default(0);
            // conversies-delta wordt afgeleid: d_leads + d_qleads + d_sales.

            $table->decimal('revenue', 12, 4)->default(0);
            $table->string('revenue_currency', 3)->default('USD'); // USD of EUR

            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['stat_date', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stat_corrections');
    }
};
