<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
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

        $content = $disk->get($path);
        return response($content, 200)
            ->header('Content-Type', $mime)
            ->header('ETag', '"'.$etag.'"')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified).' GMT');
    }
}
