<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('operators cannot open any admin page', function (string $routeName) {
    $operator = User::factory()->create();
    $ticket = Ticket::factory()->for($operator)->create();

    $parameters = match ($routeName) {
        'admin.users.edit' => $operator,
        'admin.tickets.edit' => $ticket,
        default => [],
    };

    $this->actingAs($operator)->get(route($routeName, $parameters))->assertForbidden();
})->with(['admin.dashboard', 'admin.users.index', 'admin.users.create', 'admin.users.edit', 'admin.tickets.index', 'admin.tickets.edit']);

test('operators cannot change users or tickets through admin endpoints', function () {
    $operator = User::factory()->create();
    $other = User::factory()->create();
    $ticket = Ticket::factory()->for($operator)->create();

    $this->actingAs($operator)->post(route('admin.users.store'), [
        'name' => 'Penyusup', 'phone' => '081299999999', 'role' => 'admin',
        'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
    ])->assertForbidden();
    $this->actingAs($operator)->delete(route('admin.users.destroy', $other))->assertForbidden();
    $this->actingAs($operator)->delete(route('admin.tickets.destroy', $ticket))->assertForbidden();

    expect(User::where('phone', '081299999999')->exists())->toBeFalse()
        ->and($other->fresh())->not->toBeNull()
        ->and($ticket->fresh())->not->toBeNull();
});

test('guests are sent to login from admin pages', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('administrators see an admin link in the operator app, operators do not', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('tickets.create'))
        ->assertSee(route('admin.dashboard'));

    $this->actingAs(User::factory()->create())
        ->get(route('tickets.create'))
        ->assertDontSee(route('admin.dashboard'));
});
