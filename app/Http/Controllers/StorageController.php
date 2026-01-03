<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageController extends Controller
{
    /**
     * Stream a file from the public storage disk.
     * This lets /storage/... URLs work even when no symlink exists.
     */
    public function show(string $path)
    {
        // Basic traversal hardening
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            abort(404);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            abort(404);
        }

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $lastModified = $disk->lastModified($path);
        $etag = Str::substr(md5($path.'|'.$lastModified), 0, 16);

        // Simple ETag handling
        $ifNoneMatch = request()->headers->get('if-none-match');
        if ($ifNoneMatch && trim($ifNoneMatch, '"') === $etag) {
            return response()->noContent(304);
        }

        $stream = $disk->readStream($path);
        if ($stream === false) {
            abort(404);
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'ETag' => '"'.$etag.'"',
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified).' GMT',
            // Cache public assets; ETag supports revalidation
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
