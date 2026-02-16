<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageController extends Controller
{
    /**
     * Serve files from MinIO through Laravel proxy.
     * This allows client-side access to MinIO files via public domain.
     * 
     * Example: https://api.indekost.ozanqs.my.id/storage/payment-proofs/abc123.jpg
     * Backend fetches from: http://192.168.100.247:9000/indekost/payment-proofs/abc123.jpg
     */
    public function show(Request $request, string $path): StreamedResponse
    {
        // Validate path exists in S3/MinIO
        if (!Storage::disk('s3')->exists($path)) {
            abort(404, 'File not found');
        }

        // Get file from MinIO
        $file = Storage::disk('s3')->get($path);
        $mimeType = Storage::disk('s3')->mimeType($path);

        // Stream response to client
        return response()->stream(function () use ($file) {
            echo $file;
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline', // Display in browser instead of download
            'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
        ]);
    }

    /**
     * Download files from MinIO (force download instead of display).
     */
    public function download(Request $request, string $path): StreamedResponse
    {
        if (!Storage::disk('s3')->exists($path)) {
            abort(404, 'File not found');
        }

        $filename = basename($path);
        
        return Storage::disk('s3')->download($path, $filename);
    }
}
