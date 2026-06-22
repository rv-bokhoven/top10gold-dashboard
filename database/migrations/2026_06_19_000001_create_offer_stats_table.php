<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_stats', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date');

            // offer_id is null/leeg op de campagne-rij (landing-niveau), waar
            // LP Views en cost staan. Voor offers staat hier het RedTrack offer-id.
            $table->string('offer_id')->nullable();
            $table->string('offer_title')->nullable();

            $table->unsignedInteger('lp_views')->default(0);
            $table->unsignedInteger('lp_clicks')->default(0);
            $table->unsignedInteger('clicks')->default(0);

            $table->unsignedInteger('leads')->default(0);    // convtype1
            $table->unsignedInteger('qleads')->default(0);   // convtype2 (quality lead, met revenue)
            $table->unsignedInteger('sales')->default(0);    // convtype3
            $table->unsignedInteger('conversions')->default(0); // leads + qleads + sales

            $table->decimal('cost', 12, 4)->default(0);
            $table->decimal('revenue', 12, 4)->default(0);

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // offer_id kan NULL zijn; in SQLite telt elke NULL als uniek, daarom
            // slaan we de campagne-rij op met een vaste sentinel (zie OfferStat).
            $table->unique(['stat_date', 'offer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_stats');
    }
};
