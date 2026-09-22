<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_partners', function (Blueprint $table) {
            $table->id();
            $table->string('offer_id')->unique();
            $table->string('offer_title')->nullable();
            $table->string('partner')->nullable();
            $table->string('platform_url')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('deal_model')->nullable();          // cpl | cpql
            $table->decimal('payout', 10, 2)->nullable();      // bedrag per lead/qlead
            $table->string('payout_currency', 3)->default('USD');
            $table->boolean('has_revshare')->default(false);
            $table->decimal('revshare_pct', 5, 2)->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_partners');
    }
};
