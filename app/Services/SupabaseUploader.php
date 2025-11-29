<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseUploader
{
    public static function upload($file, $folder = 'products')
    {
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = env('SUPABASE_KEY');  // ANON KEY

        $bucket = env('SUPABASE_BUCKET');

        if (! $file) {
            throw new \Exception('No file provided.');
        }

        $ext = $file->getClientOriginalExtension();
        $filename = uniqid().'.'.$ext;
        $path = "$folder/$filename";

        $response = Http::withHeaders([
            'apikey' => $supabaseKey,
            'Authorization' => "Bearer $supabaseKey",
        ])->attach(
            'file',
            file_get_contents($file->getRealPath()),
            $filename
        )->post("$supabaseUrl/storage/v1/object/$bucket/$path");

        if ($response->failed()) {
            throw new \Exception('Upload gagal: '.$response->body());
        }

        return "$supabaseUrl/storage/v1/object/public/$bucket/$path";
    }
}
