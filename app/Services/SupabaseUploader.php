<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseUploader
{
    public static function upload($file, $folder = 'products')
{
    try {

        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = env('SUPABASE_KEY');
        $bucket = env('SUPABASE_BUCKET');

        $fileExt = $file->getClientOriginalExtension();
        $fileName = uniqid() . '.' . $fileExt;
        $path = "$folder/$fileName";

        // Upload
        $response = Http::withHeaders([
            'apikey' => $supabaseKey,
            'Authorization' => "Bearer $supabaseKey",
        ])->attach(
            'file',
            file_get_contents($file),
            $fileName
        )->post("$supabaseUrl/storage/v1/object/$bucket/$path");

        if ($response->failed()) {
            \Log::error("SUPABASE ERROR", [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => "$supabaseUrl/storage/v1/object/$bucket/$path",
            ]);

            return null;
        }

        return "$supabaseUrl/storage/v1/object/public/$bucket/$path";

    } catch (\Throwable $e) {

        \Log::error("UPLOAD THROW ERROR", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return null;
    }
}

}
