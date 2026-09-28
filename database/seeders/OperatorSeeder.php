<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OperatorSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Default operator accounts. Safe to run repeatedly: existing phone numbers are updated, not duplicated.
     *
     * @var list<array{name: string, phone: string, password: string}>
     */
    private const OPERATORS = [
        ['name' => 'Admin Operator', 'phone' => '081200000001', 'password' => 'password'],
        ['name' => 'Operator Shift 1', 'phone' => '081200000002', 'password' => 'password'],
        ['name' => 'Operator Shift 2', 'phone' => '081200000003', 'password' => 'password'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::OPERATORS as $operator) {
            User::updateOrCreate(
                ['phone' => $operator['phone']],
                ['name' => $operator['name'], 'password' => $operator['password']],
            );
        }
    }
}
