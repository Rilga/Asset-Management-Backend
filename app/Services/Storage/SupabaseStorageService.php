<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SupabaseStorageService
{
    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken(config('services.supabase.service_role_key'))
            ->withoutVerifying();
    }

    public function uploadPdf(UploadedFile $file, string $folder = 'manual-books'): string
    {
        $bucket = config('services.supabase.bucket');
        $baseUrl = rtrim(config('services.supabase.url'), '/');

        $filename = $folder . '/' . uniqid() . '-' . str_replace(' ', '-', $file->getClientOriginalName());

        $response = $this->http()
            ->withHeaders([
                'Content-Type' => $file->getMimeType(),
                'x-upsert' => 'true',
            ])
            ->withBody(file_get_contents($file->getRealPath()), $file->getMimeType())
            ->post("{$baseUrl}/storage/v1/object/{$bucket}/{$filename}");

        if (!$response->successful()) {
            throw new RuntimeException('Gagal upload PDF ke Supabase: ' . $response->body());
        }

        return $filename;
    }

    public function download(string $path): string
    {
        $bucket = config('services.supabase.bucket');
        $baseUrl = rtrim(config('services.supabase.url'), '/');

        $response = $this->http()
            ->get("{$baseUrl}/storage/v1/object/{$bucket}/{$path}");

        if (!$response->successful()) {
            throw new RuntimeException('Gagal download PDF dari Supabase: ' . $response->body());
        }

        return $response->body();
    }

    public function delete(string $path): void
    {
        $bucket = config('services.supabase.bucket');
        $baseUrl = rtrim(config('services.supabase.url'), '/');

        $this->http()
            ->delete("{$baseUrl}/storage/v1/object/{$bucket}/{$path}");
    }

    public function publicUrl(string $path): string
    {
        $bucket = config('services.supabase.bucket');
        $baseUrl = rtrim(config('services.supabase.url'), '/');

        return "{$baseUrl}/storage/v1/object/public/{$bucket}/{$path}";
    }
}