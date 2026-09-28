<?php

use App\Models\User;
use Database\Seeders\OperatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get(route('tickets.create'))->assertRedirect(route('login'));
});

test('public registration is not available', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', ['name' => 'Budi', 'phone' => '081234567890', 'password' => 'rahasia123'])->assertNotFound();

    $this->get(route('login'))->assertOk()->assertDontSee('Daftar operator');

    expect(User::count())->toBe(0);
});

test('an administrator is sent to the admin dashboard after login', function () {
    User::factory()->admin()->create(['phone' => '081234567890', 'password' => 'rahasia123']);

    $this->post(route('login.store'), [
        'phone' => '081234567890',
        'password' => 'rahasia123',
    ])->assertRedirect(route('admin.dashboard'));
});

test('a deactivated account cannot log in', function () {
    User::factory()->inactive()->create(['phone' => '081234567890', 'password' => 'rahasia123']);

    $this->post(route('login.store'), [
        'phone' => '081234567890',
        'password' => 'rahasia123',
    ])->assertSessionHasErrors('phone');

    $this->assertGuest();
});

test('a user deactivated while logged in is signed out on the next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('tickets.create'))->assertOk();

    $user->update(['is_active' => false]);

    $this->actingAs($user->fresh())
        ->get(route('tickets.create'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('phone');

    $this->assertGuest();
});

test('logging in records the last login time', function () {
    $user = User::factory()->create(['phone' => '081234567890', 'password' => 'rahasia123']);

    $this->post(route('login.store'), ['phone' => '081234567890', 'password' => 'rahasia123']);

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

test('an operator can log in with a phone number in any format', function () {
    User::factory()->create(['phone' => '081234567890', 'password' => 'rahasia123']);

    $this->post(route('login.store'), [
        'phone' => '+6281234567890',
        'password' => 'rahasia123',
    ])->assertRedirect(route('tickets.create'));

    $this->assertAuthenticated();
});

test('seeded accounts can log in and re-seeding does not duplicate them', function () {
    $this->seed(OperatorSeeder::class);
    $this->seed(OperatorSeeder::class);

    expect(User::count())->toBe(3)
        ->and(User::where('phone', '081200000001')->sole()->isAdmin())->toBeTrue();

    $this->post(route('login.store'), [
        'phone' => '081200000001',
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

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
