<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Operators type the weight in tonnes ("1" means 1 ton), so store tonnes with two decimals.
 *
 * Existing data: manually typed values were already meant as tonnes and are kept as-is;
 * "otomatis" rows hold the per-class estimate in kilograms and are divided by 1000.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->decimal('weight_ton', 10, 2)->default(0)->after('weight_mode');
        });

        DB::table('tickets')->update([
            'weight_ton' => DB::raw("CASE WHEN weight_mode = 'otomatis' THEN weight_kg / 1000.0 ELSE weight_kg END"),
        ]);

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('weight_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedInteger('weight_kg')->default(0)->after('weight_mode');
        });

        DB::table('tickets')->update([
            'weight_kg' => DB::raw("CASE WHEN weight_mode = 'otomatis' THEN ROUND(weight_ton * 1000) ELSE ROUND(weight_ton) END"),
        ]);

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('weight_ton');
        });
    }
};
