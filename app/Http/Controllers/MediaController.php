<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves ticket photos straight from storage/app/public.
 *
 * Going through Laravel instead of the public/storage symlink means photos work on shared hosting
 * where symlinks are missing or not followed, the URL always matches the host the user opened, and
 * only logged-in users can see them. The URL has no file extension on purpose: some hosting setups
 * (nginx in front of Apache) serve *.jpg URLs as static files and never reach PHP.
 */
class MediaController extends Controller
{
    public function __invoke(Ticket $ticket, string $kind): StreamedResponse
    {
        $path = match ($kind) {
            'vehicle' => $ticket->vehicle_photo_path,
            'ticket' => $ticket->ticket_photo_path,
            'barcode' => $ticket->barcode_path,
        };

        $disk = Storage::disk('public');

        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, [
            // The URL carries a version derived from the file name, so a replaced photo gets a new URL.
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
