<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseUploader
{
    public static function upload($file, $folder = 'products')
    {
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = env('SUPABASE_SERVICE_ROLE_KEY');
        $bucket = env('SUPABASE_BUCKET');

        if (!$file) {
            throw new \Exception("File tidak ditemukan untuk upload.");
        }

        $fileExt = $file->getClientOriginalExtension();
        $fileName = uniqid() . '.' . $fileExt;
        $path = $folder . '/' . $fileName;

        // Upload file ke Supabase Storage
        $response = Http::withHeaders([
            'apikey' => $supabaseKey,
            'Authorization' => 'Bearer ' . $supabaseKey,
            'Content-Type' => $file->getMimeType(),
            'Cache-Control' => '3600'
        ])->put(
            "$supabaseUrl/storage/v1/object/$bucket/$path",
            file_get_contents($file)
        );

        if ($response->failed()) {
            throw new \Exception("Upload gagal: " . $response->body());
        }

        // URL public yang langsung bisa diakses dari FE
        return "$supabaseUrl/storage/v1/object/public/$bucket/$path";
    }
}
