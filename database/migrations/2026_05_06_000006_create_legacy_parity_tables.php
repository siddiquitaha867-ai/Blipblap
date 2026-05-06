<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esim_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_esim_id')->nullable()->index();
            $table->foreignId('esim_order_id')->nullable()->index();
            $table->string('event_type', 100)->index();
            $table->json('event_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('customer_email')->nullable()->index();
            $table->foreignId('esim_order_id')->nullable()->index();
            $table->foreignId('customer_esim_id')->nullable()->index();
            $table->integer('points')->default(0);
            $table->string('event_type', 100)->index();
            $table->string('event_status', 50)->default('pending')->index();
            $table->string('event_reference')->nullable()->index();
            $table->json('event_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('customer_email')->nullable()->index();
            $table->integer('points_balance')->default(0)->index();
            $table->integer('lifetime_points_earned')->default(0);
            $table->integer('lifetime_points_redeemed')->default(0);
            $table->timestamp('last_event_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'customer_email']);
        });

        Schema::create('campaign_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('customer_email')->nullable()->index();
            $table->foreignId('esim_order_id')->nullable()->index();
            $table->string('event_type', 100)->index();
            $table->string('event_status', 50)->default('queued')->index();
            $table->json('event_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('promotion_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('rule_type', 100)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('conditions')->nullable();
            $table->json('actions')->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_rules');
        Schema::dropIfExists('campaign_events');
        Schema::dropIfExists('loyalty_balances');
        Schema::dropIfExists('loyalty_events');
        Schema::dropIfExists('esim_events');
    }
};
