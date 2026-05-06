<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_esims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('customer_email')->index();
            $table->string('iccid')->unique();
            $table->string('nickname')->nullable();
            $table->string('current_bundle_code')->nullable()->index();
            $table->string('status', 50)->default('pending_install_details')->index();
            $table->string('matching_id')->nullable();
            $table->string('smdp_address')->nullable();
            $table->string('activation_code')->nullable();
            $table->text('qr_code_url')->nullable();
            $table->json('install_details')->nullable();
            $table->json('last_status')->nullable();
            $table->boolean('topup_supported')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->foreignId('source_order_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_esims');
    }
};
