<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->operator = User::factory()->create();
});

test('photo urls go through laravel, not the storage symlink, and have no file extension', function () {
    $ticket = Ticket::factory()->for($this->operator)->create([
        'vehicle_photo_path' => 'tickets/vehicle/2026-09/abc123.jpg',
        'ticket_photo_path' => 'tickets/ticket/2026-09/def456.jpg',
        'barcode_path' => 'tickets/barcode/2026-09/ghi789.png',
    ]);

    foreach (['vehicle' => $ticket->vehiclePhotoUrl(), 'ticket' => $ticket->ticketPhotoUrl(), 'barcode' => $ticket->barcodeUrl()] as $kind => $url) {
        expect(parse_url($url, PHP_URL_PATH))->toBe("/tickets/{$ticket->id}/photos/{$kind}")
            ->and($url)->not->toContain('/storage/')
            ->and($url)->toContain('?v=');
    }
});

test('a logged-in user can view a stored photo', function () {
    $path = UploadedFile::fake()->image('kendaraan.jpg', 40, 30)->store('tickets/vehicle/2026-09', 'public');
    $ticket = Ticket::factory()->for($this->operator)->create(['vehicle_photo_path' => $path]);

    $response = $this->actingAs($this->operator)->get($ticket->vehiclePhotoUrl());

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Cache-Control'))->toContain('private')
        ->and($response->streamedContent())->toBe(Storage::disk('public')->get($path));
});

test('uploaded photos are viewable right after saving a ticket', function () {
    $this->actingAs($this->operator)->post(route('tickets.store'), [
        'voyage_no' => 'V1',
        'vessel_name' => 'MILA UTAMA',
        'destination_port_name' => 'BANJARMASIN',
        'plate_number' => 'L 1234 XY',
        'vehicle_class' => 'VB',
        'weight_mode' => 'manual',
        'weight_kg' => 12000,
        'vehicle_photo' => UploadedFile::fake()->image('kendaraan.jpg'),
        'ticket_photo' => UploadedFile::fake()->image('tiket.jpg'),
        'barcode_image' => UploadedFile::fake()->image('barcode.png'),
    ]);

    $ticket = Ticket::sole();

    $this->actingAs($this->operator)->get($ticket->vehiclePhotoUrl())->assertOk();
    $this->actingAs($this->operator)->get($ticket->ticketPhotoUrl())->assertOk();
    $this->actingAs($this->operator)->get($ticket->barcodeUrl())->assertOk()->assertHeader('Content-Type', 'image/png');
});

test('replacing a photo changes its url so browsers do not show the cached old one', function () {
    $ticket = Ticket::factory()->for($this->operator)->create([
        'ticket_photo_path' => UploadedFile::fake()->image('lama.jpg')->store('tickets/ticket', 'public'),
    ]);
    $oldUrl = $ticket->ticketPhotoUrl();

    $this->actingAs($this->operator)->post(route('tickets.photos', $ticket), [
        'ticket_photo' => UploadedFile::fake()->image('baru.jpg'),
    ]);

    expect($ticket->fresh()->ticketPhotoUrl())->not->toBe($oldUrl);
});

test('guests cannot view photos', function () {
    $ticket = Ticket::factory()->for($this->operator)->create([
        'vehicle_photo_path' => UploadedFile::fake()->image('kendaraan.jpg')->store('tickets/vehicle', 'public'),
    ]);

    $this->get($ticket->vehiclePhotoUrl())->assertRedirect(route('login'));
});

test('a ticket without that photo, or with a missing file, returns not found', function () {
    $ticket = Ticket::factory()->for($this->operator)->create([
        'vehicle_photo_path' => 'tickets/vehicle/2026-09/sudah-terhapus.jpg',
        'ticket_photo_path' => null,
    ]);

    $this->actingAs($this->operator)->get(route('tickets.photo', [$ticket, 'vehicle']))->assertNotFound();
    $this->actingAs($this->operator)->get(route('tickets.photo', [$ticket, 'ticket']))->assertNotFound();
});

test('only the three photo kinds can be requested', function () {
    $ticket = Ticket::factory()->for($this->operator)->create();

    $this->actingAs($this->operator)->get("/tickets/{$ticket->id}/photos/..%2F..%2F.env")->assertNotFound();
    $this->actingAs($this->operator)->get("/tickets/{$ticket->id}/photos/password")->assertNotFound();
});
