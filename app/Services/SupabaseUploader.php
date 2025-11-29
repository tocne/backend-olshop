<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseUploader
{
    public static function upload($file, $folder = 'products')
    {
        $supabaseUrl  = rtrim(env('SUPABASE_URL'), '/');
        $supabaseKey  = env('SUPABASE_SERVICE_ROLE');   // WAJIB pakai service_role
        $bucket       = env('SUPABASE_BUCKET');

        if (!$file || !$file->isValid()) {
            throw new \Exception("File invalid atau tidak ditemukan.");
        }

        // Generate filename aman
        $fileExt  = $file->getClientOriginalExtension();
        $fileName = uniqid() . '.' . $fileExt;
        $path     = "$folder/$fileName";

        // Upload ke Supabase (tanpa multipart!)
        $response = Http::withHeaders([
            "apikey"        => $supabaseKey,
            "Authorization" => "Bearer {$supabaseKey}",
            "Content-Type"  => $file->getMimeType(),
        ])->send(
            "POST",
            "{$supabaseUrl}/storage/v1/object/{$bucket}/{$path}",
            ["body" => file_get_contents($file->getRealPath())]
        );

        if ($response->failed()) {
            throw new \Exception("Upload gagal: " . $response->body());
        }

        // Public URL
        return "{$supabaseUrl}/storage/v1/object/public/{$bucket}/{$path}";
    }
}
