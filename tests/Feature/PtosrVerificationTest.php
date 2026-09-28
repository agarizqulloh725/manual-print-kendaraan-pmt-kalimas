<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->operator = User::factory()->create();
});

test('the vehicle detail shows the ticket and an unverified PTOSR status', function () {
    $ticket = Ticket::factory()->for($this->operator)->create(['plate_number' => 'L 1234 XY', 'barcode_value' => 'ABC-777']);

    $this->actingAs($this->operator)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('L 1234 XY')
        ->assertSee('ABC-777')
        ->assertSee('⚠ NON PTOSR')
        ->assertSee('Verifikasi: Sudah Diinput di PTOSR')
        ->assertSeeInOrder(['Data Kendaraan', 'Foto Kendaraan', 'Verifikasi PTOSR']);
});

test('an operator can mark a vehicle as entered in PTOSR', function () {
    $ticket = Ticket::factory()->for($this->operator)->create();

    $this->actingAs($this->operator)
        ->post(route('tickets.ptosr.store', $ticket), [
            'ptosr_reference' => 'PTOSR-0001',
            'ptosr_note' => 'Diinput shift pagi',
        ])
        ->assertRedirect(route('tickets.show', $ticket));

    $ticket->refresh();

    expect($ticket->isPtosrVerified())->toBeTrue()
        ->and($ticket->ptosr_verified_by)->toBe($this->operator->id)
        ->and($ticket->ptosr_reference)->toBe('PTOSR-0001')
        ->and($ticket->ptosr_note)->toBe('Diinput shift pagi');

    $this->actingAs($this->operator)
        ->get(route('tickets.show', $ticket))
        ->assertSee('✔ PTOSR')
        ->assertSee('PTOSR-0001')
        ->assertSee($this->operator->name);
});

test('the PTOSR reference is optional', function () {
    $ticket = Ticket::factory()->for($this->operator)->create();

    $this->actingAs($this->operator)
        ->post(route('tickets.ptosr.store', $ticket))
        ->assertSessionHasNoErrors();

    expect($ticket->fresh()->isPtosrVerified())->toBeTrue();
});

test('a PTOSR verification can be cancelled', function () {
    $ticket = Ticket::factory()->for($this->operator)->ptosrVerified()->create();

    $this->actingAs($this->operator)
        ->delete(route('tickets.ptosr.destroy', $ticket))
        ->assertRedirect(route('tickets.show', $ticket));

    expect($ticket->fresh())
        ->isPtosrVerified()->toBeFalse()
        ->ptosr_verified_by->toBeNull()
        ->ptosr_reference->toBeNull();
});

test('guests cannot verify tickets', function () {
    $ticket = Ticket::factory()->create();

    $this->post(route('tickets.ptosr.store', $ticket))->assertRedirect(route('login'));

    expect($ticket->fresh()->isPtosrVerified())->toBeFalse();
});
