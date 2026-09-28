<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->operator = User::factory()->create();
});

test('the vessel report lists one row per voyage for the selected period', function () {
    Ticket::factory()->for($this->operator)->count(2)->create([
        'voyage_no' => 'V1', 'vessel_name' => 'MILA UTAMA', 'destination_port_name' => 'BANJARMASIN', 'weight_ton' => 1,
    ]);
    Ticket::factory()->for($this->operator)->ptosrVerified()->create([
        'voyage_no' => 'V1', 'vessel_name' => 'MILA UTAMA', 'destination_port_name' => 'BANJARMASIN', 'weight_ton' => 1,
    ]);
    Ticket::factory()->for($this->operator)->create([
        'voyage_no' => 'V2', 'vessel_name' => 'EGON, KM', 'created_at' => now()->subDays(3),
    ]);

    $this->actingAs($this->operator)
        ->get(route('reports.vessels'))
        ->assertOk()
        ->assertSee('MILA UTAMA')
        ->assertDontSee('EGON, KM')
        ->assertViewHas('vessels', fn ($vessels) => $vessels->count() === 1
            && (int) $vessels->first()->total_vehicles === 3
            && (int) $vessels->first()->ptosr_vehicles === 1
            && (float) $vessels->first()->total_weight_ton === 3.0);
});

test('the vessel report can be searched by vessel name', function () {
    Ticket::factory()->for($this->operator)->create(['voyage_no' => 'V1', 'vessel_name' => 'MILA UTAMA']);
    Ticket::factory()->for($this->operator)->create(['voyage_no' => 'V2', 'vessel_name' => 'DLN BATU LAYAR']);

    $this->actingAs($this->operator)
        ->get(route('reports.vessels', ['vessel' => 'batu']))
        ->assertSee('DLN BATU LAYAR')
        ->assertDontSee('MILA UTAMA');
});

test('the vessel detail lists every vehicle of that voyage with its PTOSR status', function () {
    Ticket::factory()->for($this->operator)->ptosrVerified()->create(['voyage_no' => 'V1', 'plate_number' => 'L 1111 AA', 'created_at' => now()->subDays(5)]);
    Ticket::factory()->for($this->operator)->create(['voyage_no' => 'V1', 'plate_number' => 'L 2222 BB']);
    Ticket::factory()->for($this->operator)->create(['voyage_no' => 'V2', 'plate_number' => 'W 3333 CC']);

    $this->actingAs($this->operator)
        ->get(route('reports.vessel', 'V1'))
        ->assertOk()
        ->assertSee('L 1111 AA')
        ->assertSee('L 2222 BB')
        ->assertDontSee('W 3333 CC')
        ->assertSee('✔ PTOSR')
        ->assertSee('⚠ NON PTOSR');
});

test('the vessel detail can be filtered by PTOSR status', function () {
    Ticket::factory()->for($this->operator)->ptosrVerified()->create(['voyage_no' => 'V1', 'plate_number' => 'L 1111 AA']);
    Ticket::factory()->for($this->operator)->create(['voyage_no' => 'V1', 'plate_number' => 'L 2222 BB']);

    $this->actingAs($this->operator)
        ->get(route('reports.vessel', ['voyageNo' => 'V1', 'ptosr' => Ticket::PTOSR_UNVERIFIED]))
        ->assertSee('L 2222 BB')
        ->assertDontSee('L 1111 AA');
});

test('an unknown voyage returns not found', function () {
    $this->actingAs($this->operator)
        ->get(route('reports.vessel', 'TIDAK-ADA'))
        ->assertNotFound();
});

test('the vehicle report can be filtered by search, class and PTOSR status', function () {
    Ticket::factory()->for($this->operator)->ptosrVerified()->create(['plate_number' => 'L 1111 AA', 'vehicle_class' => 'VB']);
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'L 2222 BB', 'vehicle_class' => 'VB']);
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'W 3333 CC', 'vehicle_class' => 'IVA']);

    $this->actingAs($this->operator)
        ->get(route('reports.vehicles', ['vehicle_class' => 'VB', 'ptosr' => Ticket::PTOSR_VERIFIED]))
        ->assertOk()
        ->assertSee('L 1111 AA')
        ->assertDontSee('L 2222 BB')
        ->assertDontSee('W 3333 CC');

    $this->actingAs($this->operator)
        ->get(route('reports.vehicles', ['search' => '3333']))
        ->assertSee('W 3333 CC')
        ->assertDontSee('L 1111 AA');
});

test('the report can be exported as CSV with the filtered rows', function () {
    Ticket::factory()->for($this->operator)->ptosrVerified()->create(['plate_number' => 'L 1111 AA', 'voyage_no' => 'V1', 'barcode_value' => 'ABC-777']);
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'W 2222 BB', 'voyage_no' => 'V2']);

    $response = $this->actingAs($this->operator)
        ->get(route('reports.export', ['voyage_no' => 'V1']))
        ->assertOk()
        ->assertDownload();

    $csv = $response->streamedContent();

    expect($csv)
        ->toContain('"No Tiket"')
        ->toContain('L 1111 AA')
        ->toContain('"Nilai Barcode"')
        ->toContain('ABC-777')
        ->toContain('"Status PTOSR"')
        ->toContain(';PTOSR;')
        ->not->toContain('W 2222 BB');
});

test('exporting a voyage includes vehicles from earlier days', function () {
    Ticket::factory()->for($this->operator)->create(['plate_number' => 'L 1111 AA', 'voyage_no' => 'V1', 'created_at' => now()->subDays(2)]);

    $csv = $this->actingAs($this->operator)
        ->get(route('reports.export', ['voyage_no' => 'V1']))
        ->streamedContent();

    expect($csv)->toContain('L 1111 AA');
});

test('active filters are shown as removable chips with a count badge', function () {
    Ticket::factory()->for($this->operator)->create(['voyage_no' => 'V1', 'vessel_name' => 'MILA UTAMA']);

    $response = $this->actingAs($this->operator)
        ->get(route('reports.vehicles', [
            'voyage_no' => 'V1',
            'vehicle_class' => 'VB',
            'ptosr' => Ticket::PTOSR_UNVERIFIED,
        ]))
        ->assertOk()
        ->assertViewHas('chips', fn (array $chips) => collect($chips)->pluck('label')->all() === [
            '📅 Hari ini', '🚢 MILA UTAMA', '⚠ NON PTOSR', 'Gol. VB',
        ]);

    $response->assertSee('Hapus filter Gol. VB')
        ->assertDontSee('Hapus filter 📅 Hari ini')
        ->assertSee('Reset semua')
        ->assertSeeInOrder(['📅 Hari ini', 'NON PTOSR', 'Cari plat / no tiket / barcode...']);
});

test('total weight is only shown per vessel', function () {
    Ticket::factory()->for($this->operator)->create(['voyage_no' => 'V1', 'weight_ton' => 12.35]);

    $this->actingAs($this->operator)->get(route('reports.vehicles'))->assertDontSee('Total Tonase');
    $this->actingAs($this->operator)->get(route('reports.vessels'))
        ->assertDontSee('Total Tonase')
        ->assertDontSee('NON PTOSR</p>', false)
        ->assertSee('12,35 Ton');
    $this->actingAs($this->operator)->get(route('reports.vessel', 'V1'))->assertSee('Total Tonase')->assertSee('12,35 Ton');
});

test('the report rejects an end date before the start date', function () {
    $this->actingAs($this->operator)
        ->get(route('reports.vehicles', ['date_from' => '2026-09-28', 'date_to' => '2026-09-01']))
        ->assertSessionHasErrors('date_to');
});
