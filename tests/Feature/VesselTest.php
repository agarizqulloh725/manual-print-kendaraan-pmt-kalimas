<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

test('vessels are fetched from the PTOS-R schedule board', function () {
    Http::fake([
        'ptosr.pelindo.co.id/*' => Http::response([
            [
                'VOYAGE_NO' => 'I00000147126616010088',
                'KD_KAPAL' => 'I000001471',
                'NAMA_KAPAL' => 'MILA UTAMA',
                'NM_OPERATOR' => 'TIMUR MILA UTAMA, PT',
                'KD_PORT_DEST' => 'IDBDJ',
                'NM_PORT_DEST' => 'Banjarmasin',
                'NM_DERMAGA' => 'JAMRUD UTARA',
                'ETA' => '2026/09/28 08:00',
                'ETD' => '2026/09/28 16:00',
                'VESOPS_STATUS' => 'DEBARKASI',
            ],
        ]),
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('vessels.index'))
        ->assertOk()
        ->assertJsonPath('data.0.voyage_no', 'I00000147126616010088')
        ->assertJsonPath('data.0.vessel_name', 'MILA UTAMA')
        ->assertJsonPath('data.0.destination_port_name', 'BANJARMASIN');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'kd_cabang=61')
        && str_contains($request->url(), 'kd_terminal=601'));
});

test('an HTML response from PTOS-R returns a readable JSON error', function () {
    Http::fake([
        'ptosr.pelindo.co.id/*' => Http::response('<html>Maintenance</html>', 200, ['Content-Type' => 'text/html']),
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('vessels.index'))
        ->assertStatus(502)
        ->assertJsonStructure(['message']);
});

test('a PTOS-R connection failure returns a readable JSON error', function () {
    Http::fake([
        'ptosr.pelindo.co.id/*' => Http::failedConnection(),
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('vessels.index'))
        ->assertStatus(502);
});
