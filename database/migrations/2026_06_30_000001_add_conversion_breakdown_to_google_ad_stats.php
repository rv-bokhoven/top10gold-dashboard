<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_ad_stats', function (Blueprint $table) {
            $table->decimal('conv_lpclick', 12, 2)->default(0)->after('conversions');
            $table->decimal('conv_lead', 12, 2)->default(0)->after('conv_lpclick');
            $table->decimal('conv_qlead', 12, 2)->default(0)->after('conv_lead');
            $table->decimal('conv_sale', 12, 2)->default(0)->after('conv_qlead');
        });
    }

    public function down(): void
    {
        Schema::table('google_ad_stats', function (Blueprint $table) {
            $table->dropColumn(['conv_lpclick', 'conv_lead', 'conv_qlead', 'conv_sale']);
        });
    }
};
