<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Hosting diagnostics for ticket images: is the storage folder there and readable, does every stored
 * image file exist, and which URL does the app give it. Run on the server: php artisan photos:check
 */
#[Signature('photos:check {--limit=20 : Number of most recent tickets with images to check}')]
#[Description('Check that ticket images exist in storage and show the URL used to display them')]
class CheckPhotos extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $disk = Storage::disk('public');
        $root = $disk->path('');

        $this->components->info('Lingkungan');
        $this->components->twoColumnDetail('APP_URL', (string) config('app.url'));
        $this->components->twoColumnDetail('Folder foto', $root);
        $this->components->twoColumnDetail('Folder ada', is_dir($root) ? '<fg=green>ya</>' : '<fg=red>TIDAK</>');
        $this->components->twoColumnDetail('Folder bisa ditulis', is_writable($root) ? '<fg=green>ya</>' : '<fg=red>TIDAK (chmod -R 775 storage)</>');
        $this->components->twoColumnDetail('PHP', PHP_VERSION);

        $query = Ticket::query()->where(function ($query) {
            foreach (Ticket::PHOTO_KINDS as $column) {
                $query->orWhereNotNull($column);
            }
        });

        $this->newLine();
        $this->components->info('Gambar di '.$query->count().' tiket (dicek '.$this->option('limit').' terbaru)');

        $rows = [];
        $missing = 0;

        foreach ($query->latest()->limit((int) $this->option('limit'))->get() as $ticket) {
            foreach (array_keys(Ticket::PHOTO_KINDS) as $kind) {
                $path = $ticket->photoPath($kind);

                if (! $path) {
                    continue;
                }

                $exists = $disk->exists($path);
                $readable = $exists && is_readable($disk->path($path));
                $missing += $readable ? 0 : 1;

                $rows[] = [
                    $ticket->ticket_number,
                    $kind,
                    $path,
                    match (true) {
                        $readable => '<fg=green>OK</> ('.number_format($disk->size($path) / 1024, 0).' KB)',
                        $exists => '<fg=red>TIDAK BISA DIBACA</>',
                        default => '<fg=red>FILE TIDAK ADA</>',
                    },
                    $ticket->photoUrl($kind),
                ];
            }
        }

        if ($rows === []) {
            $this->components->warn('Belum ada tiket dengan gambar.');

            return self::SUCCESS;
        }

        $this->table(['Tiket', 'Jenis', 'Lokasi file', 'Status', 'URL'], $rows);

        if ($missing > 0) {
            $this->components->error("{$missing} gambar tidak ditemukan / tidak bisa dibaca di {$root}");

            return self::FAILURE;
        }

        $this->components->info('Semua file gambar ada. Buka salah satu URL di atas di browser (dalam keadaan login) untuk memastikan gambar tampil.');

        return self::SUCCESS;
    }
}
