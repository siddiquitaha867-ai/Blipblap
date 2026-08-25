<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $columns = [
            'phone_number' => ['type' => 'string', 'length' => 50],
            'address_line1' => ['type' => 'string', 'length' => 255],
            'town' => ['type' => 'string', 'length' => 120],
            'city' => ['type' => 'string', 'length' => 120],
            'country' => ['type' => 'string', 'length' => 120],
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn('users', $column)) {
                continue;
            }

            Schema::table('users', function (Blueprint $table) use ($column, $definition): void {
                $table->string($column, $definition['length'])->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('users', 'phone_number') ? 'phone_number' : null,
            Schema::hasColumn('users', 'address_line1') ? 'address_line1' : null,
            Schema::hasColumn('users', 'town') ? 'town' : null,
            Schema::hasColumn('users', 'city') ? 'city' : null,
            Schema::hasColumn('users', 'country') ? 'country' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
