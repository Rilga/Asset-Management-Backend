<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Definisi dasar dokumentasi OpenAPI/Swagger untuk AssetMind Backend API.
 *
 * Memakai PHP 8 Attributes (didukung swagger-php v6).
 * Anotasi path tersebar di controller masing-masing.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'AssetMind Backend API',
    description: 'REST API untuk Sistem Pendukung Keputusan Perawatan Aset Industri Perkebunan Kelapa Sawit. '
        . 'Backend Laravel terintegrasi dengan AI Engine (FastAPI) untuk embedding, chatbot RAG, dan rekomendasi pemeliharaan.',
    contact: new OA\Contact(name: 'AssetMind', email: 'firasyan.daffa123@gmail.com')
)]
#[OA\Server(url: 'http://localhost:8000', description: 'Server lokal (Laravel artisan serve)')]
#[OA\SecurityScheme(
    securityScheme: 'bearerSanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: "Masukkan token Sanctum dari endpoint /auth/login (tanpa kata 'Bearer')."
)]
#[OA\Tag(name: 'Auth', description: 'Autentikasi & token')]
#[OA\Tag(name: 'Assets', description: 'Manajemen aset (teknik) & pembacaan (bersama)')]
#[OA\Tag(name: 'Chatbot', description: 'Chatbot RAG terintegrasi AI Engine')]
#[OA\Tag(name: 'Recommendation', description: 'Rekomendasi pemeliharaan dari AI Engine')]
#[OA\Tag(name: 'Mekanik - Akun', description: 'CRUD akun mekanik (khusus teknik)')]
#[OA\Tag(name: 'Maintenance Task', description: 'Tugas pemeliharaan')]
#[OA\Tag(name: 'Operating Hour', description: 'Pencatatan jam jalan aset')]
#[OA\Tag(name: 'Maintenance Report', description: 'Laporan pemeliharaan')]
#[OA\Tag(name: 'Repair Request', description: 'Pengajuan perbaikan')]
#[OA\Tag(name: 'Repair Report', description: 'Catatan perbaikan')]
#[OA\Tag(name: 'Knowledge', description: 'Knowledge document & ingestion RAG')]
#[OA\Tag(name: 'Evaluation', description: 'Evaluasi retrieval (precision/recall)')]
#[OA\Schema(
    schema: 'MessageResponse',
    type: 'object',
    properties: [new OA\Property(property: 'message', type: 'string', example: 'Operasi berhasil')]
)]
#[OA\Schema(
    schema: 'ValidationError',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(property: 'errors', type: 'object'),
    ]
)]
#[OA\Schema(
    schema: 'ForbiddenError',
    type: 'object',
    properties: [new OA\Property(property: 'message', type: 'string', example: 'Forbidden. Data ini bukan milik Anda.')]
)]
class OpenApiSpec
{
}
