<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('referral_logs', function (Blueprint $table) {
            $table->id();
            $table->string('book_title')->nullable();
            $table->string('referral_code', 100)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 100)->nullable();
            $table->datetime('whatsapp_click_time')->nullable();
            $table->enum('status', ['belum beli', 'beli', 'pending', 'gagal', 'challenge'])->default('belum beli');
            $table->string('nama_pembeli')->nullable();
            $table->text('alamat')->nullable();
            $table->string('nomor_pembeli', 20)->nullable();
            $table->string('order_id', 64)->nullable();
            $table->decimal('harga', 10, 2)->nullable();
            $table->decimal('harga_asli', 10, 2)->nullable();
            $table->decimal('diskon_amount', 10, 2)->nullable();
            $table->string('payment_type')->nullable();
            $table->datetime('paid_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('referral_logs');
    }
};