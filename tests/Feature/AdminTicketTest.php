<?php

use App\Enums\VehicleClass;
use App\Enums\WeightMode;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->operator = User::factory()->create();
});

test('the ticket list can be filtered like the reports', function () {
    Ticket::factory()->for($this->operator)->ptosrVerified()->create(['plate_number' => 'L 1111 AA']);
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'L 2222 BB']);

    $this->actingAs($this->admin)
        ->get(route('admin.tickets.index', ['ptosr' => Ticket::PTOSR_UNVERIFIED]))
        ->assertOk()
        ->assertSee('L 2222 BB')
        ->assertDontSee('L 1111 AA');
});

test('an administrator can correct a ticket', function () {
    $ticket = Ticket::factory()->for($this->operator)->create([
        'plate_number' => 'L 1111 AA',
        'barcode_value' => 'SALAH',
        'barcode_format' => 'Code128',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.tickets.update', $ticket), [
            'plate_number' => 'l  1234  xy',
            'destination_port_name' => 'lembar',
            'vehicle_class' => VehicleClass::VIB->value,
            'weight_mode' => WeightMode::Manual->value,
            'weight_kg' => 21000,
            'barcode_value' => 'BENAR-001',
            'barcode_format' => 'Code128',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($ticket->fresh())
        ->plate_number->toBe('L 1234 XY')
        ->destination_port_name->toBe('LEMBAR')
        ->vehicle_class->toBe(VehicleClass::VIB)
        ->weight_kg->toBe(21000)
        ->barcode_value->toBe('BENAR-001')
        ->barcode_format->toBeNull();
});

test('deleting a ticket also removes its photos', function () {
    Storage::fake('public');

    $ticket = Ticket::factory()->for($this->operator)->create([
        'vehicle_photo_path' => UploadedFile::fake()->image('k.jpg')->store('tickets/vehicle', 'public'),
        'ticket_photo_path' => UploadedFile::fake()->image('t.jpg')->store('tickets/ticket', 'public'),
        'barcode_path' => UploadedFile::fake()->image('b.png')->store('tickets/barcode', 'public'),
    ]);
    $paths = [$ticket->vehicle_photo_path, $ticket->ticket_photo_path, $ticket->barcode_path];

    $this->actingAs($this->admin)
        ->from(route('admin.tickets.index'))
        ->delete(route('admin.tickets.destroy', $ticket))
        ->assertRedirect(route('admin.tickets.index'));

    expect($ticket->fresh())->toBeNull();
    Storage::disk('public')->assertMissing($paths);
});
