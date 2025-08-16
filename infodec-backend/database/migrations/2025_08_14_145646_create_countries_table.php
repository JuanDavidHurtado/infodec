<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country', function (Blueprint $table) {
            $table->id('idCountry');
            $table->string('conNameSpa', 100)->nullable(false);
            $table->string('conNameGer', 100)->nullable(false);
            $table->string('conCurrency', 50)->nullable(false);
            $table->string('conSymbol', 5)->nullable(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country');
    }
};
