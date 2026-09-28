<?php

use App\Http\Controllers\VesselController;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('the dashboard shows today\'s monitoring numbers', function () {
    $operator = User::factory()->create(['name' => 'Budi Operator']);
    Ticket::factory()->for($operator)->count(2)->create(['vessel_name' => 'MILA UTAMA', 'voyage_no' => 'V1']);
    Ticket::factory()->for($operator)->ptosrVerified()->create(['vessel_name' => 'MILA UTAMA', 'voyage_no' => 'V1']);
    Ticket::factory()->for($operator)->create(['created_at' => now()->subDays(2)]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('stats', fn (array $stats) => $stats['tickets_today'] === 3
            && $stats['ptosr_today'] === 1
            && $stats['operators_today'] === 1
            && $stats['non_ptosr_backlog'] === 3)
        ->assertViewHas('trend', fn (array $trend) => count($trend) === 7
            && end($trend)['total'] === 3
            && $trend[4]['total'] === 1)
        ->assertSee('Budi Operator')
        ->assertSee('MILA UTAMA');
});

test('the dashboard shows the last PTOS-R API check', function () {
    Http::fake(['ptosr.pelindo.co.id/*' => Http::response([
        ['VOYAGE_NO' => 'V1', 'NAMA_KAPAL' => 'MILA UTAMA', 'NM_PORT_DEST' => 'Banjarmasin', 'ETD' => '2026/09/28 16:00'],
    ])]);

    $this->actingAs($this->admin)->getJson(route('vessels.index'))->assertOk();

    expect(Cache::get(VesselController::STATUS_CACHE_KEY))->toMatchArray(['ok' => true, 'vessels' => 1]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSee('✔ Terhubung')
        ->assertSee('1 kapal diterima');
});

test('a failed PTOS-R call is shown on the dashboard', function () {
    Http::fake(['ptosr.pelindo.co.id/*' => Http::failedConnection()]);

    $this->actingAs($this->admin)->getJson(route('vessels.index'))->assertStatus(502);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSee('✘ Gagal');
});
