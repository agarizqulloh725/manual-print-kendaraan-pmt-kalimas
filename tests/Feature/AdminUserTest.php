<?php

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
});

test('the user list can be searched and filtered', function () {
    User::factory()->create(['name' => 'Budi Operator', 'phone' => '081211111111']);
    User::factory()->inactive()->create(['name' => 'Sari Operator', 'phone' => '081222222222']);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index', ['search' => '+62 812-1111']))
        ->assertOk()
        ->assertSee('Budi Operator')
        ->assertDontSee('Sari Operator');

    $this->actingAs($this->admin)
        ->get(route('admin.users.index', ['status' => 'inactive']))
        ->assertSee('Sari Operator')
        ->assertDontSee('Budi Operator');
});

test('an administrator can create an operator account', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.store'), [
            'name' => 'Budi Operator',
            'phone' => '+62 812-3456-7890',
            'role' => UserRole::Operator->value,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::where('phone', '081234567890')->sole();

    expect($user)
        ->name->toBe('Budi Operator')
        ->role->toBe(UserRole::Operator)
        ->is_active->toBeTrue()
        ->and(Hash::check('rahasia123', $user->password))->toBeTrue();
});

test('a phone number can only be used once', function () {
    User::factory()->create(['phone' => '081234567890']);

    $this->actingAs($this->admin)
        ->post(route('admin.users.store'), [
            'name' => 'Budi',
            'phone' => '6281234567890',
            'role' => UserRole::Operator->value,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])
        ->assertSessionHasErrors('phone');
});

test('an administrator can update a user and keep the password when left empty', function () {
    $user = User::factory()->create(['password' => 'lama12345']);

    $this->actingAs($this->admin)
        ->put(route('admin.users.update', $user), [
            'name' => 'Nama Baru',
            'phone' => '081277777777',
            'role' => UserRole::Admin->value,
            'is_active' => '0',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('admin.users.index'));

    $user->refresh();

    expect($user)
        ->name->toBe('Nama Baru')
        ->phone->toBe('081277777777')
        ->role->toBe(UserRole::Admin)
        ->is_active->toBeFalse()
        ->and(Hash::check('lama12345', $user->password))->toBeTrue();
});

test('an administrator can reset a user password', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
        'name' => $user->name,
        'phone' => $user->phone,
        'role' => UserRole::Operator->value,
        'is_active' => '1',
        'password' => 'baru12345',
        'password_confirmation' => 'baru12345',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('baru12345', $user->fresh()->password))->toBeTrue();
});

test('an administrator cannot demote or deactivate themselves', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'phone' => $this->admin->phone,
            'role' => UserRole::Operator->value,
            'is_active' => '0',
        ])
        ->assertSessionHasErrors(['role', 'is_active']);

    expect($this->admin->fresh())
        ->isAdmin()->toBeTrue()
        ->is_active->toBeTrue();
});

test('a user without tickets can be deleted', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect(route('admin.users.index'));

    expect($user->fresh())->toBeNull();
});

test('a user with tickets cannot be deleted and must be deactivated instead', function () {
    $user = User::factory()->create();
    Ticket::factory()->for($user)->create();

    $this->actingAs($this->admin)
        ->from(route('admin.users.index'))
        ->delete(route('admin.users.destroy', $user))
        ->assertSessionHasErrors('user');

    expect($user->fresh())->not->toBeNull();
});

test('an administrator cannot delete themselves', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.users.index'))
        ->delete(route('admin.users.destroy', $this->admin))
        ->assertSessionHasErrors('user');

    expect($this->admin->fresh())->not->toBeNull();
});
