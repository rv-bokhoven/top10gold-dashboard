<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_ad_stats', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date');

            $table->string('campaign_id');
            $table->string('campaign_name')->nullable();
            $table->string('ad_group_id');
            $table->string('ad_group_name')->nullable();
            $table->string('ad_id');
            $table->string('ad_name')->nullable();
            $table->string('ad_type')->nullable();

            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->decimal('cost', 12, 2)->default(0);     // EUR (cost_micros / 1e6)
            $table->decimal('conversions', 12, 2)->default(0);
            $table->decimal('conversions_value', 12, 2)->default(0);

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['stat_date', 'ad_id']);
            $table->index('stat_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_ad_stats');
    }
};
