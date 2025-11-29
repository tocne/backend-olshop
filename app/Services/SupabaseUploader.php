<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseUploader
{
    public static function upload($file, $folder = 'products')
{
    $supabaseUrl = env('SUPABASE_URL');
    $supabaseKey = env('SUPABASE_KEY');
    $bucket = env('SUPABASE_BUCKET');

    if (!$file) {
        throw new \Exception("File tidak ditemukan untuk upload.");
    }

    // --- LOGGING UNTUK DEBUG ---
    \Log::info("MASUK_UPLOAD", [
        "file_is_valid" => $file->isValid(),
        "original_name" => $file->getClientOriginalName(),
        "real_path" => $file->getRealPath(),
        "size" => $file->getSize(),
        "mime" => $file->getMimeType(),
    ]);
    // ----------------------------

    // Nama file aman
    $fileExt = $file->getClientOriginalExtension();
    $fileName = uniqid() . '.' . $fileExt;
    $path = "$folder/$fileName";

    // Upload file
    $response = Http::withHeaders([
        'apikey' => $supabaseKey,
        'Authorization' => "Bearer $supabaseKey",
    ])->attach(
        'file',
        file_get_contents($file->getRealPath()),  // WAJIB pakai realPath
        $fileName
    )->post("$supabaseUrl/storage/v1/object/$bucket/$path");

    if ($response->failed()) {
        $error = $response->json() ?? "Unknown error";
        \Log::error("UPLOAD_SUPABASE_FAILED", [
            "error" => $error,
            "status" => $response->status(),
        ]);
        throw new \Exception("Upload gagal: " . json_encode($error));
    }

    return "$supabaseUrl/storage/v1/object/public/$bucket/$path";
}

}
