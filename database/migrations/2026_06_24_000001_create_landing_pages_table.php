<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('url', 1024);
            $table->string('url_hash', 64)->unique();   // sha1(url), voor de unieke index
            $table->string('campaigns')->nullable();     // komma-lijst van campagnes die deze pagina gebruiken
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->boolean('ok')->default(false);
            $table->string('error')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
