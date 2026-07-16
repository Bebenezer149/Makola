<?php
// app/Services/SupabaseStorageService.php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupabaseStorageService
{
    private string $baseUrl;
    private string $serviceKey;
    private string $bucket;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.supabase.url'), '/');
        $this->serviceKey = config('services.supabase.service_key');
        $this->bucket = config('services.supabase.bucket');
    }

    public function uploadProductImage(UploadedFile $file): string
    {
        $fileName = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $path = "products/{$fileName}";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->serviceKey}",
            'Content-Type' => $file->getMimeType(),
            'x-upsert' => 'true',
        ])->withBody(
            file_get_contents($file->getRealPath()),
            $file->getMimeType()
        )->post("{$this->baseUrl}/storage/v1/object/{$this->bucket}/{$path}");

        if ($response->failed()) {
            throw new \RuntimeException('Upload to Supabase failed: ' . $response->body());
        }

        // Return public URL
        return "{$this->baseUrl}/storage/v1/object/public/{$this->bucket}/{$path}";
    }

    public function deleteProductImage(string $publicUrl): bool
    {
        $prefix = "{$this->baseUrl}/storage/v1/object/public/{$this->bucket}/";
        $path = Str::after($publicUrl, $prefix);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->serviceKey}",
        ])->delete("{$this->baseUrl}/storage/v1/object/{$this->bucket}/{$path}");

        return $response->successful();
    }
}