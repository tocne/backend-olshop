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

        // Nama file aman
        $fileExt = $file->getClientOriginalExtension();
        $fileName = uniqid() . '.' . $fileExt;
        $path = "$folder/$fileName";

        // Upload file secara aman via multipart

\Log::debug('SUPABASE ENV CHECK', [
    'supabase_url' => $supabaseUrl,
    'bucket'       => $bucket,
    'path'         => $path,
]);
        $response = Http::withHeaders([
            'apikey' => $supabaseKey,
            'Authorization' => "Bearer $supabaseKey",
        ])->attach(
            'file',                       // field name
            file_get_contents($file),     // file content
            $fileName                     // file name
        )->post("$supabaseUrl/storage/v1/object/$bucket/$path");

        // Jika gagal → berikan pesan UTF8 aman
        if ($response->failed()) {
            $error = $response->json() ?? ['error' => 'Upload failed'];
            throw new \Exception("Upload gagal: " . json_encode($error, JSON_UNESCAPED_UNICODE));
        }

        // URL public final
        return "$supabaseUrl/storage/v1/object/public/$bucket/$path";
    }
}
