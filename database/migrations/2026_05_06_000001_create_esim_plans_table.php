<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esim_plans', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code')->unique();
            $table->string('slug')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('coverage_type', 40)->index();
            $table->string('country_iso', 10)->nullable()->index();
            $table->string('country_name')->nullable()->index();
            $table->string('region_name')->nullable()->index();
            $table->decimal('data_amount', 12, 2)->nullable();
            $table->string('data_unit', 20)->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->boolean('unlimited')->default(false);
            $table->boolean('topup_supported')->default(false);
            $table->decimal('supplier_price', 12, 2)->default(0);
            $table->decimal('retail_price', 12, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->json('network_json')->nullable();
            $table->json('raw_payload')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esim_plans');
    }
};
