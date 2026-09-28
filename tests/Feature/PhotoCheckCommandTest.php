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

test('photos:check passes when every stored image exists', function () {
    Ticket::factory()->for($this->operator)->create([
        'ticket_number' => 'TMB-OK-0001',
        'vehicle_photo_path' => UploadedFile::fake()->image('k.jpg')->store('tickets/vehicle', 'public'),
    ]);

    $this->artisan('photos:check')
        ->expectsOutputToContain('TMB-OK-0001')
        ->expectsOutputToContain('Semua file gambar ada')
        ->assertSuccessful();
});

test('photos:check reports images whose file is missing', function () {
    Ticket::factory()->for($this->operator)->create([
        'ticket_number' => 'TMB-HILANG-0001',
        'ticket_photo_path' => 'tickets/ticket/2026-09/sudah-dihapus.jpg',
    ]);

    $this->artisan('photos:check')
        ->expectsOutputToContain('FILE TIDAK ADA')
        ->expectsOutputToContain('1 gambar tidak ditemukan')
        ->assertFailed();
});

test('photos:check says so when no ticket has images yet', function () {
    Ticket::factory()->for($this->operator)->create();

    $this->artisan('photos:check')
        ->expectsOutputToContain('Belum ada tiket dengan gambar')
        ->assertSuccessful();
});
