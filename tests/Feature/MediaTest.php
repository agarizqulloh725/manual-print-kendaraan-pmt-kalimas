<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    foreach (['vehicle' => $ticket->photoUrl('vehicle'), 'ticket' => $ticket->photoUrl('ticket'), 'barcode' => $ticket->photoUrl('barcode')] as $kind => $url) {
        expect(parse_url($url, PHP_URL_PATH))->toBe("/tickets/{$ticket->id}/photos/{$kind}")
            ->and($url)->not->toContain('/storage/')
            ->and($url)->toContain('?v=');
    }
});

test('a logged-in user can view a stored photo', function () {
    $path = UploadedFile::fake()->image('kendaraan.jpg', 40, 30)->store('tickets/vehicle/2026-09', 'public');
    $ticket = Ticket::factory()->for($this->operator)->create(['vehicle_photo_path' => $path]);

    $response = $this->actingAs($this->operator)->get($ticket->photoUrl('vehicle'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Cache-Control'))->toContain('private')
        ->and($response->getContent())->toBe(Storage::disk('public')->get($path));
});

test('uploaded photos are viewable right after saving a ticket', function () {
    $this->actingAs($this->operator)->post(route('tickets.store'), [
        'voyage_no' => 'V1',
        'vessel_name' => 'MILA UTAMA',
        'destination_port_name' => 'BANJARMASIN',
        'plate_number' => 'L 1234 XY',
        'vehicle_class' => 'VB',
        'weight_mode' => 'manual',
        'weight_ton' => 12,
        'vehicle_photo' => UploadedFile::fake()->image('kendaraan.jpg'),
        'ticket_photo' => UploadedFile::fake()->image('tiket.jpg'),
        'barcode_image' => UploadedFile::fake()->image('barcode.png'),
    ]);

    $ticket = Ticket::sole();

    $this->actingAs($this->operator)->get($ticket->photoUrl('vehicle'))->assertOk();
    $this->actingAs($this->operator)->get($ticket->photoUrl('ticket'))->assertOk();
    $this->actingAs($this->operator)->get($ticket->photoUrl('barcode'))->assertOk()->assertHeader('Content-Type', 'image/png');
});

test('photoUrl is the single entry point for every image kind and photoCount counts them', function () {
    $ticket = Ticket::factory()->for($this->operator)->create([
        'vehicle_photo_path' => 'tickets/vehicle/2026-09/a.jpg',
        'barcode_path' => 'tickets/barcode/2026-09/b.png',
    ]);

    expect($ticket->photoUrl('vehicle'))->toContain("/tickets/{$ticket->id}/photos/vehicle?v=")
        ->and($ticket->photoUrl('ticket'))->toBeNull()
        ->and($ticket->photoUrl('barcode'))->toContain("/tickets/{$ticket->id}/photos/barcode?v=")
        ->and($ticket->photoUrl('bukan-jenis-foto'))->toBeNull()
        ->and($ticket->photoCount())->toBe(2);
});

test('the content type comes from the file extension, without the fileinfo extension', function (string $file, string $type) {
    expect(Ticket::photoMimeType($file))->toBe($type);
})->with([
    ['tickets/vehicle/a.jpg', 'image/jpeg'],
    ['tickets/vehicle/a.JPEG', 'image/jpeg'],
    ['tickets/barcode/b.png', 'image/png'],
    ['tickets/ticket/c.webp', 'image/webp'],
]);

test('images are sent as a normal response, not a stream', function () {
    $path = UploadedFile::fake()->image('barcode.png', 30, 10)->store('tickets/barcode', 'public');
    $ticket = Ticket::factory()->for($this->operator)->create(['barcode_path' => $path]);

    $response = $this->actingAs($this->operator)->get($ticket->photoUrl('barcode'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Content-Length', (string) strlen(Storage::disk('public')->get($path)))
        ->assertHeader('Content-Disposition', 'inline; filename="'.basename($path).'"');

    expect($response->baseResponse)->not->toBeInstanceOf(StreamedResponse::class);
});

test('the photo url version follows the stored file, so a different file never reuses a cached image', function () {
    $ticket = Ticket::factory()->for($this->operator)->create(['ticket_photo_path' => 'tickets/ticket/2026-09/aaa.jpg']);
    $url = $ticket->photoUrl('ticket');

    expect($ticket->fresh()->photoUrl('ticket'))->toBe($url);

    $ticket->ticket_photo_path = 'tickets/ticket/2026-09/bbb.jpg';

    expect($ticket->photoUrl('ticket'))->not->toBe($url);
});

test('guests cannot view photos', function () {
    $ticket = Ticket::factory()->for($this->operator)->create([
        'vehicle_photo_path' => UploadedFile::fake()->image('kendaraan.jpg')->store('tickets/vehicle', 'public'),
    ]);

    $this->get($ticket->photoUrl('vehicle'))->assertRedirect(route('login'));
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
