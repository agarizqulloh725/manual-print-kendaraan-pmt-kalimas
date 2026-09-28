<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('barcode_value')->nullable()->index()->after('barcode_path');
            $table->string('barcode_format', 30)->nullable()->after('barcode_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['barcode_value']);
            $table->dropColumn(['barcode_value', 'barcode_format']);
        });
    }
};
