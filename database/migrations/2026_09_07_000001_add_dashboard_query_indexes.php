<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_stats', function (Blueprint $table) {
            // De dashboardfilters gebruiken steeds een bron met een datumbereik.
            $table->index(['source', 'stat_date'], 'offer_stats_source_date_index');

            // Een offer-detailpagina leest één offer over meerdere dagen.
            $table->index(['offer_id', 'stat_date'], 'offer_stats_offer_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('offer_stats', function (Blueprint $table) {
            $table->dropIndex('offer_stats_source_date_index');
            $table->dropIndex('offer_stats_offer_date_index');
        });
    }
};
