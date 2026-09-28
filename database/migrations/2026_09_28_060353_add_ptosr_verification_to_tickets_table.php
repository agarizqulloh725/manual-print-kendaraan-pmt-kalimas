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
            $table->timestamp('ptosr_verified_at')->nullable()->index()->after('barcode_format');
            $table->foreignId('ptosr_verified_by')->nullable()->after('ptosr_verified_at')->constrained('users')->nullOnDelete();
            $table->string('ptosr_reference', 50)->nullable()->after('ptosr_verified_by');
            $table->string('ptosr_note')->nullable()->after('ptosr_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ptosr_verified_by');
            $table->dropIndex(['ptosr_verified_at']);
            $table->dropColumn(['ptosr_verified_at', 'ptosr_reference', 'ptosr_note']);
        });
    }
};
