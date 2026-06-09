<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('esim_plans', 'tax_amount')) {
            return;
        }

        Schema::table('esim_plans', function (Blueprint $table): void {
            $table->decimal('tax_amount', 12, 2)->default(0)->after('retail_price');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('esim_plans', 'tax_amount')) {
            return;
        }

        Schema::table('esim_plans', function (Blueprint $table): void {
            $table->dropColumn('tax_amount');
        });
    }
};
