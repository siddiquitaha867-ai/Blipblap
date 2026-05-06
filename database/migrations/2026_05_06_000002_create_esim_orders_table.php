<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esim_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('customer_email')->index();
            $table->string('order_reference')->nullable()->index();
            $table->string('apply_reference')->nullable()->index();
            $table->string('payment_reference')->nullable()->index();
            $table->string('order_type', 40)->default('new_esim');
            $table->string('bundle_code')->index();
            $table->string('iccid')->nullable()->index();
            $table->string('status', 40)->default('draft')->index();
            $table->string('validation_status', 40)->default('pending')->index();
            $table->string('fulfillment_status', 40)->default('pending_payment')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esim_orders');
    }
};
