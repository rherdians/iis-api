<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->string('kode_referal', 100)->unique();
            $table->string('username', 100);
            $table->integer('usage')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('referral_codes');
    }
};