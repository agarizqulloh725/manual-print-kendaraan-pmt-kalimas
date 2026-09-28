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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('voyage_no')->index();
            $table->string('vessel_code')->nullable();
            $table->string('vessel_name');
            $table->string('operator_name')->nullable();
            $table->string('destination_port_code')->nullable();
            $table->string('destination_port_name');
            $table->string('berth_name')->nullable();
            $table->string('plate_number', 20)->index();
            $table->string('vehicle_class', 10);
            $table->string('weight_mode', 10);
            $table->unsignedInteger('weight_kg');
            $table->string('vehicle_photo_path')->nullable();
            $table->string('ticket_photo_path')->nullable();
            $table->unsignedSmallInteger('print_count')->default(0);
            $table->timestamp('last_printed_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
