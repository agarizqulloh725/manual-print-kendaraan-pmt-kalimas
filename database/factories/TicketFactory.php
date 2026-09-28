<?php

namespace Database\Factories;

use App\Enums\VehicleClass;
use App\Enums\WeightMode;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_number' => 'TMB-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'user_id' => User::factory(),
            'voyage_no' => 'I'.fake()->numerify('####################'),
            'vessel_code' => 'I'.fake()->numerify('#########'),
            'vessel_name' => fake()->randomElement(['MILA UTAMA', 'EGON, KM', 'DLN BATU LAYAR']),
            'operator_name' => fake()->company(),
            'destination_port_code' => 'IDBDJ',
            'destination_port_name' => fake()->randomElement(['BANJARMASIN', 'LEMBAR']),
            'berth_name' => 'JAMRUD UTARA',
            'plate_number' => 'L '.fake()->numerify('####').' '.fake()->lexify('??'),
            'vehicle_class' => fake()->randomElement(VehicleClass::cases()),
            'weight_mode' => WeightMode::Manual,
            'weight_ton' => fake()->randomFloat(2, 1, 40),
            'print_count' => 1,
            'last_printed_at' => now(),
        ];
    }

    /**
     * Indicate that the vehicle has been confirmed as entered in PTOSR.
     */
    public function ptosrVerified(): static
    {
        return $this->state(fn (array $attributes) => [
            'ptosr_verified_at' => now(),
            'ptosr_verified_by' => User::factory(),
            'ptosr_reference' => fake()->numerify('PTOSR-#######'),
        ]);
    }
}
