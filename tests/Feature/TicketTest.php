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
    Storage::fake('public');
    $this->operator = User::factory()->create();
});

function ticketPayload(array $overrides = []): array
{
    return [
        'voyage_no' => 'I00000147126616010088',
        'vessel_code' => 'I000001471',
        'vessel_name' => 'MILA UTAMA',
        'operator_name' => 'TIMUR MILA UTAMA, PT',
        'destination_port_code' => 'IDBDJ',
        'destination_port_name' => 'BANJARMASIN',
        'berth_name' => 'JAMRUD UTARA',
        'plate_number' => 'l  1234   xy',
        'vehicle_class' => VehicleClass::VB->value,
        'weight_mode' => WeightMode::Manual->value,
        'weight_kg' => 12500,
        ...$overrides,
    ];
}

test('the input form renders', function () {
    $this->actingAs($this->operator)
        ->get(route('tickets.create'))
        ->assertOk()
        ->assertSee('Kapal Beroperasi');
});

test('an operator can save a ticket with photos and is sent to the print page', function () {
    $response = $this->actingAs($this->operator)->post(route('tickets.store'), ticketPayload([
        'vehicle_photo' => UploadedFile::fake()->image('kendaraan.jpg'),
        'ticket_photo' => UploadedFile::fake()->image('tiket.jpg'),
    ]));

    $ticket = Ticket::sole();

    $response->assertRedirect(route('tickets.print', $ticket))->assertSessionHas('autoprint', true);

    expect($ticket)
        ->user_id->toBe($this->operator->id)
        ->plate_number->toBe('L 1234 XY')
        ->weight_kg->toBe(12500)
        ->print_count->toBe(1)
        ->ticket_number->toBe('TMB-'.now()->format('Ymd').'-0001');

    Storage::disk('public')->assertExists([$ticket->vehicle_photo_path, $ticket->ticket_photo_path]);
});

test('a ticket can be saved without any photos', function () {
    $this->actingAs($this->operator)
        ->post(route('tickets.store'), ticketPayload())
        ->assertSessionHasNoErrors();

    expect(Ticket::sole())
        ->vehicle_photo_path->toBeNull()
        ->ticket_photo_path->toBeNull()
        ->barcode_path->toBeNull();
});

test('both photo fields are marked optional on the input form', function () {
    $this->actingAs($this->operator)
        ->get(route('tickets.create'))
        ->assertSeeInOrder(['Foto Kendaraan', 'OPSIONAL', 'Foto Tiket', 'OPSIONAL']);
});

test('both photo fields open the camera and still allow picking a file', function () {
    $this->actingAs($this->operator)
        ->get(route('tickets.create'))
        ->assertSee('data-camera-open="vehicle_photo"', false)
        ->assertSee('data-camera-open="ticket_photo"', false)
        ->assertSee('for="vehicle_photo"', false)
        ->assertSee('📁 Pilih File')
        ->assertSee('capture="environment" class="sr-only" tabindex="-1" aria-hidden="true" data-camera-fallback="vehicle_photo"', false)
        ->assertSee('data-camera-fallback="ticket_photo"', false);
});

test('automatic weight mode uses the vehicle class estimate', function () {
    $this->actingAs($this->operator)->post(route('tickets.store'), ticketPayload([
        'weight_mode' => WeightMode::Automatic->value,
        'weight_kg' => null,
    ]));

    expect(Ticket::sole()->weight_kg)->toBe(VehicleClass::VB->defaultWeightKg());
});

test('manual weight mode requires a weight', function () {
    $this->actingAs($this->operator)
        ->post(route('tickets.store'), ticketPayload(['weight_kg' => null]))
        ->assertSessionHasErrors('weight_kg');

    expect(Ticket::count())->toBe(0);
});

test('ticket numbers increase per day', function () {
    $this->actingAs($this->operator)->post(route('tickets.store'), ticketPayload());
    $this->actingAs($this->operator)->post(route('tickets.store'), ticketPayload());

    expect(Ticket::pluck('ticket_number')->all())->toBe([
        'TMB-'.now()->format('Ymd').'-0001',
        'TMB-'.now()->format('Ymd').'-0002',
    ]);
});

test('the print page shows the ticket details', function () {
    $ticket = Ticket::factory()->for($this->operator)->create(['plate_number' => 'L 9999 AB']);

    $this->actingAs($this->operator)
        ->get(route('tickets.print', $ticket))
        ->assertOk()
        ->assertSee('L 9999 AB')
        ->assertSee($ticket->vessel_name);
});

test('reprinting increments the print counter', function () {
    $ticket = Ticket::factory()->for($this->operator)->create(['print_count' => 1]);

    $this->actingAs($this->operator)
        ->post(route('tickets.reprint', $ticket))
        ->assertRedirect(route('tickets.print', $ticket));

    expect($ticket->fresh()->print_count)->toBe(2);
});

test('the reprint list can be searched by plate number', function () {
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'L 1111 AA']);
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'W 2222 BB']);

    $this->actingAs($this->operator)
        ->get(route('tickets.index', ['search' => '1111']))
        ->assertOk()
        ->assertSee('L 1111 AA')
        ->assertDontSee('W 2222 BB');
});

test('the reprint list can be filtered by vessel, class and PTOSR status', function () {
    Ticket::factory()->for($this->operator)->ptosrVerified()->create(['plate_number' => 'L 1111 AA', 'voyage_no' => 'V1', 'vessel_name' => 'MILA UTAMA', 'vehicle_class' => 'VB']);
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'L 2222 BB', 'voyage_no' => 'V1', 'vessel_name' => 'MILA UTAMA', 'vehicle_class' => 'VB']);
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'W 3333 CC', 'voyage_no' => 'V2', 'vehicle_class' => 'VB']);

    $this->actingAs($this->operator)
        ->get(route('tickets.index', ['voyage_no' => 'V1', 'vehicle_class' => 'VB', 'ptosr' => Ticket::PTOSR_UNVERIFIED]))
        ->assertOk()
        ->assertSee('L 2222 BB')
        ->assertDontSee('L 1111 AA')
        ->assertDontSee('W 3333 CC')
        ->assertSee('🚢 MILA UTAMA');
});

test('the reprint list shows tickets from an earlier date range', function () {
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'L 1111 AA', 'created_at' => now()->subDays(3)]);

    $this->actingAs($this->operator)->get(route('tickets.index'))->assertDontSee('L 1111 AA');

    $this->actingAs($this->operator)
        ->get(route('tickets.index', ['date_from' => now()->subDays(5)->toDateString(), 'date_to' => now()->toDateString()]))
        ->assertSee('L 1111 AA');
});

test('the reprint list is paginated ten tickets per page', function () {
    Ticket::factory()->for($this->operator)->count(12)->create();

    $this->actingAs($this->operator)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertViewHas('tickets', fn ($tickets) => $tickets->count() === 10 && $tickets->total() === 12)
        ->assertSee('1 / 2')
        ->assertSee('rel="next"', false);

    $this->actingAs($this->operator)
        ->get(route('tickets.index', ['page' => 2]))
        ->assertViewHas('tickets', fn ($tickets) => $tickets->count() === 2);
});

test('a ticket photo can be added after printing and replaces the old one', function () {
    $ticket = Ticket::factory()->for($this->operator)->create([
        'ticket_photo_path' => UploadedFile::fake()->image('lama.jpg')->store('tickets/ticket', 'public'),
    ]);
    $oldPath = $ticket->ticket_photo_path;

    $this->actingAs($this->operator)
        ->from(route('tickets.index'))
        ->post(route('tickets.photos', $ticket), [
            'ticket_photo' => UploadedFile::fake()->image('baru.jpg'),
        ])
        ->assertRedirect(route('tickets.index'));

    $ticket->refresh();

    Storage::disk('public')->assertExists($ticket->ticket_photo_path);
    Storage::disk('public')->assertMissing($oldPath);
});

test('the extracted barcode is stored separately and the original ticket photo is kept', function () {
    $this->actingAs($this->operator)->post(route('tickets.store'), ticketPayload([
        'ticket_photo' => UploadedFile::fake()->image('tiket.jpg', 1200, 900),
        'barcode_image' => UploadedFile::fake()->image('barcode.png', 600, 150),
    ]));

    $ticket = Ticket::sole();

    expect($ticket->barcode_path)->toStartWith('tickets/barcode/')
        ->and($ticket->ticket_photo_path)->toStartWith('tickets/ticket/');

    Storage::disk('public')->assertExists([$ticket->ticket_photo_path, $ticket->barcode_path]);

    $this->actingAs($this->operator)
        ->get(route('tickets.print', $ticket))
        ->assertSee($ticket->barcodeUrl());
});

test('a barcode without a ticket photo is ignored', function () {
    $this->actingAs($this->operator)->post(route('tickets.store'), ticketPayload([
        'barcode_image' => UploadedFile::fake()->image('barcode.png'),
    ]));

    expect(Ticket::sole()->barcode_path)->toBeNull();
});

test('the barcode can be re-cropped from the existing ticket photo', function () {
    $ticket = Ticket::factory()->for($this->operator)->create([
        'ticket_photo_path' => UploadedFile::fake()->image('tiket.jpg')->store('tickets/ticket', 'public'),
        'barcode_path' => UploadedFile::fake()->image('lama.png')->store('tickets/barcode', 'public'),
    ]);
    $originalPhoto = $ticket->ticket_photo_path;
    $oldBarcode = $ticket->barcode_path;

    $this->actingAs($this->operator)
        ->post(route('tickets.photos', $ticket), [
            'barcode_image' => UploadedFile::fake()->image('baru.png'),
        ])
        ->assertSessionHasNoErrors();

    $ticket->refresh();

    expect($ticket->ticket_photo_path)->toBe($originalPhoto)
        ->and($ticket->barcode_path)->not->toBe($oldBarcode);
    Storage::disk('public')->assertMissing($oldBarcode);
});

test('replacing the ticket photo without a new barcode removes the stale barcode', function () {
    $ticket = Ticket::factory()->for($this->operator)->create([
        'ticket_photo_path' => UploadedFile::fake()->image('tiket.jpg')->store('tickets/ticket', 'public'),
        'barcode_path' => UploadedFile::fake()->image('lama.png')->store('tickets/barcode', 'public'),
    ]);
    $oldBarcode = $ticket->barcode_path;

    $this->actingAs($this->operator)->post(route('tickets.photos', $ticket), [
        'ticket_photo' => UploadedFile::fake()->image('baru.jpg'),
    ]);

    expect($ticket->fresh()->barcode_path)->toBeNull();
    Storage::disk('public')->assertMissing($oldBarcode);
});

test('the scanned barcode value and format are stored and printed', function () {
    $this->actingAs($this->operator)->post(route('tickets.store'), ticketPayload([
        'barcode_value' => '  TMB-TIKET-0001234 ',
        'barcode_format' => 'Code128',
    ]));

    $ticket = Ticket::sole();

    expect($ticket)
        ->barcode_value->toBe('TMB-TIKET-0001234')
        ->barcode_format->toBe('Code128');

    $this->actingAs($this->operator)
        ->get(route('tickets.print', $ticket))
        ->assertSee('TMB-TIKET-0001234');
});

test('a barcode format without a value is not stored', function () {
    $this->actingAs($this->operator)->post(route('tickets.store'), ticketPayload([
        'barcode_value' => '',
        'barcode_format' => 'Code128',
    ]));

    expect(Ticket::sole())
        ->barcode_value->toBeNull()
        ->barcode_format->toBeNull();
});

test('the barcode value can be corrected from the reprint tab', function () {
    $ticket = Ticket::factory()->for($this->operator)->create([
        'barcode_value' => 'SALAH-BACA',
        'barcode_format' => 'Code128',
    ]);

    $this->actingAs($this->operator)
        ->post(route('tickets.photos', $ticket), [
            'barcode_value' => 'TMB-TIKET-0009999',
            'barcode_format' => '',
        ])
        ->assertSessionHasNoErrors();

    expect($ticket->fresh())
        ->barcode_value->toBe('TMB-TIKET-0009999')
        ->barcode_format->toBeNull();
});

test('tickets can be searched by barcode value', function () {
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'L 1111 AA', 'barcode_value' => 'ABC-777']);
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'W 2222 BB', 'barcode_value' => 'XYZ-888']);

    $this->actingAs($this->operator)
        ->get(route('tickets.index', ['search' => 'ABC-777']))
        ->assertSee('L 1111 AA')
        ->assertDontSee('W 2222 BB');
});

test('uploaded ticket photos must be images', function () {
    $ticket = Ticket::factory()->for($this->operator)->create();

    $this->actingAs($this->operator)
        ->post(route('tickets.photos', $ticket), [
            'ticket_photo' => UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'),
        ])
        ->assertSessionHasErrors('ticket_photo');
});
