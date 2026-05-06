<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('esim-go')->index();
            $table->string('context')->index();
            $table->string('endpoint');
            $table->unsignedInteger('http_status')->nullable();
            $table->string('result', 40)->index();
            $table->foreignId('esim_order_id')->nullable()->index();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
