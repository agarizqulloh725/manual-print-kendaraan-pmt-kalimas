<?php

use App\Models\User;
use Database\Seeders\OperatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get(route('tickets.create'))->assertRedirect(route('login'));
});

test('an operator can register with a phone number', function () {
    $this->post(route('register.store'), [
        'name' => 'Budi Operator',
        'phone' => '+62 812-3456-7890',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertRedirect(route('tickets.create'));

    $this->assertAuthenticated();
    expect(User::sole()->phone)->toBe('081234567890');
});

test('a phone number can only be registered once', function () {
    User::factory()->create(['phone' => '081234567890']);

    $this->post(route('register.store'), [
        'name' => 'Budi Operator',
        'phone' => '6281234567890',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ])->assertSessionHasErrors('phone');

    $this->assertGuest();
});

test('an operator can log in with a phone number in any format', function () {
    User::factory()->create(['phone' => '081234567890', 'password' => 'rahasia123']);

    $this->post(route('login.store'), [
        'phone' => '+6281234567890',
        'password' => 'rahasia123',
    ])->assertRedirect(route('tickets.create'));

    $this->assertAuthenticated();
});

test('seeded operators can log in and re-seeding does not duplicate them', function () {
    $this->seed(OperatorSeeder::class);
    $this->seed(OperatorSeeder::class);

    expect(User::count())->toBe(3);

    $this->post(route('login.store'), [
        'phone' => '081200000001',
        'password' => 'password',
    ])->assertRedirect(route('tickets.create'));

    $this->assertAuthenticated();
});

test('login fails with a wrong password', function () {
    User::factory()->create(['phone' => '081234567890', 'password' => 'rahasia123']);

    $this->post(route('login.store'), [
        'phone' => '081234567890',
        'password' => 'salah',
    ])->assertSessionHasErrors('phone');

    $this->assertGuest();
});

test('an operator can log out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
