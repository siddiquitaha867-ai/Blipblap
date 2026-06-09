<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone_number', 50)->nullable()->after('referral_code');
            $table->string('address_line1')->nullable()->after('phone_number');
            $table->string('town', 120)->nullable()->after('address_line1');
            $table->string('city', 120)->nullable()->after('town');
            $table->string('country', 120)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'phone_number',
                'address_line1',
                'town',
                'city',
                'country',
            ]);
        });
    }
};
