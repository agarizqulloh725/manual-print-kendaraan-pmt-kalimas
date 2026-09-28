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
        'weight_ton' => '12,5',
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
        ->weight_ton->toBe('12.50')
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
        'weight_ton' => null,
    ]));

    expect((float) Ticket::sole()->weight_ton)->toBe(VehicleClass::VB->defaultWeightTon());
});

test('manual weight mode requires a weight', function () {
    $this->actingAs($this->operator)
        ->post(route('tickets.store'), ticketPayload(['weight_ton' => null]))
        ->assertSessionHasErrors('weight_ton');

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

test('photos and barcode cannot be uploaded or changed after the ticket is saved', function () {
    $ticket = Ticket::factory()->for($this->operator)->create([
        'ticket_photo_path' => $ticketPhoto = UploadedFile::fake()->image('tiket.jpg')->store('tickets/ticket', 'public'),
        'barcode_value' => 'ASLI-001',
    ]);

    $this->actingAs($this->operator)
        ->post("/tickets/{$ticket->id}/photos", [
            'vehicle_photo' => UploadedFile::fake()->image('kendaraan.jpg'),
            'ticket_photo' => UploadedFile::fake()->image('baru.jpg'),
            'barcode_value' => 'DIUBAH',
        ])
        ->assertNotFound();

    expect($ticket->fresh())
        ->vehicle_photo_path->toBeNull()
        ->ticket_photo_path->toBe($ticketPhoto)
        ->barcode_value->toBe('ASLI-001');
});

test('the reprint list only shows the photos that exist, without any upload form', function () {
    $withPhotos = Ticket::factory()->for($this->operator)->create([
        'plate_number' => 'L 1111 AA',
        'vehicle_photo_path' => UploadedFile::fake()->image('k.jpg')->store('tickets/vehicle', 'public'),
    ]);
    $withoutPhotos = Ticket::factory()->for($this->operator)->create(['plate_number' => 'L 2222 BB']);

    $response = $this->actingAs($this->operator)->get(route('tickets.index'))->assertOk();

    // Stored photo opens in the viewer; missing photos are simply left out.
    $response->assertSee('href="'.e($withPhotos->photoUrl('vehicle')).'" data-lightbox=', false)
        ->assertSee('1/3')
        ->assertDontSee('data-lightbox="Foto Tiket · L 1111 AA"', false);

    // No upload form or photo inputs for any ticket.
    $response->assertDontSee('enctype="multipart/form-data"', false)
        ->assertDontSee('data-photo-input', false)
        ->assertDontSee('data-camera-open', false);

    // A ticket without photos has nothing to open.
    $response->assertSee('Tidak ada foto')
        ->assertSee('id="photos_toggle_'.$withoutPhotos->id.'" class="peer sr-only" disabled', false);
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
        ->assertSee($ticket->photoUrl('barcode'));
});

test('a barcode without a ticket photo is ignored', function () {
    $this->actingAs($this->operator)->post(route('tickets.store'), ticketPayload([
        'barcode_image' => UploadedFile::fake()->image('barcode.png'),
    ]));

    expect(Ticket::sole()->barcode_path)->toBeNull();
});

test('the weight is entered in tonnes: typing 1 means 1 ton', function (string $typed, string $stored, string $printed) {
    $this->actingAs($this->operator)
        ->post(route('tickets.store'), ticketPayload(['weight_ton' => $typed]))
        ->assertSessionHasNoErrors();

    $ticket = Ticket::sole();

    expect($ticket->weight_ton)->toBe($stored);

    $this->actingAs($this->operator)
        ->get(route('tickets.print', $ticket))
        ->assertSee($printed);
})->with([
    'one ton' => ['1', '1.00', '1,00 Ton'],
    'decimal comma' => ['12,5', '12.50', '12,50 Ton'],
    'decimal point' => ['0.35', '0.35', '0,35 Ton'],
]);

test('tonnage accepts at most two decimals', function () {
    $this->actingAs($this->operator)
        ->post(route('tickets.store'), ticketPayload(['weight_ton' => '1,255']))
        ->assertSessionHasErrors('weight_ton');
});

test('the printed ticket shows the weight as tonnage in tonnes', function () {
    $ticket = Ticket::factory()->for($this->operator)->create(['weight_ton' => 12.5]);

    $this->actingAs($this->operator)
        ->get(route('tickets.print', $ticket))
        ->assertSee('Tonase')
        ->assertSee('12,50 Ton')
        ->assertDontSee('Kg');
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

test('tickets can be searched by barcode value', function () {
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'L 1111 AA', 'barcode_value' => 'ABC-777']);
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'W 2222 BB', 'barcode_value' => 'XYZ-888']);

    $this->actingAs($this->operator)
        ->get(route('tickets.index', ['search' => 'ABC-777']))
        ->assertSee('L 1111 AA')
        ->assertDontSee('W 2222 BB');
});

test('uploaded ticket photos must be images', function () {
    $this->actingAs($this->operator)
        ->post(route('tickets.store'), ticketPayload([
            'ticket_photo' => UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('ticket_photo');

    expect(Ticket::count())->toBe(0);
});
