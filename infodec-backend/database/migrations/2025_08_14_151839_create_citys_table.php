<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city', function (Blueprint $table) {
            $table->id('idCity');
            $table->string('citNameSpa', 100)->nullable(false);
            $table->string('citNameGer', 100)->nullable(false);
            $table->unsignedBigInteger('country_idCountry')->nullable(false);

            $table->foreign('country_idCountry')
                  ->references('idCountry')
                  ->on('country')
                  ->onDelete('no action')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city');
    }
};
