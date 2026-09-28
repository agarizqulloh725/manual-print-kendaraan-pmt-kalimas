<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Sends a ticket image (vehicle photo, ticket photo, barcode) straight from storage/app/public.
 *
 * Built to work on shared hosting:
 * - no public/storage symlink needed;
 * - the file is read with a plain file read and returned as a normal response, instead of a stream
 *   (streaming relies on fpassthru(), which some hosts disable);
 * - the content type comes from the file extension, not the fileinfo extension;
 * - the URL has no .jpg/.png ending, so an nginx proxy in front of Apache cannot swallow it as a static file.
 * Only logged-in users can see images (the route sits in the auth group).
 */
class MediaController extends Controller
{
    public function __invoke(Ticket $ticket, string $kind): Response
    {
        $path = $ticket->photoPath($kind);
        $disk = Storage::disk('public');

        abort_unless($path && $disk->exists($path), 404);

        $contents = $disk->get($path);

        abort_if($contents === null, 404);

        return response($contents, 200, [
            'Content-Type' => Ticket::photoMimeType($path),
            'Content-Length' => (string) strlen($contents),
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
