<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OperatorSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Default accounts. Safe to run repeatedly: existing phone numbers are updated, not duplicated.
     *
     * @var list<array{name: string, phone: string, password: string, role: UserRole}>
     */
    private const USERS = [
        ['name' => 'Administrator', 'phone' => '081200000001', 'password' => 'password', 'role' => UserRole::Admin],
        ['name' => 'Operator Shift 1', 'phone' => '081200000002', 'password' => 'password', 'role' => UserRole::Operator],
        ['name' => 'Operator Shift 2', 'phone' => '081200000003', 'password' => 'password', 'role' => UserRole::Operator],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::USERS as $user) {
            User::updateOrCreate(
                ['phone' => $user['phone']],
                ['name' => $user['name'], 'password' => $user['password'], 'role' => $user['role'], 'is_active' => true],
            );
        }
    }
}
