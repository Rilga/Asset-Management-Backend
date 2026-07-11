<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controller Public
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\Api\Knowledge\RetrievalLogController;
use App\Http\Controllers\Api\Knowledge\IngestionController;
use App\Http\Controllers\Api\Chat\ChatbotController;
use App\Http\Controllers\Api\Evaluation\RetrievalEvaluationController;

// Controller Mekanik
use App\Http\Controllers\Api\Mekanik\OperatingHourController;
use App\Http\Controllers\Api\Mekanik\MaintenanceReportController;
use App\Http\Controllers\Api\Mekanik\RepairRequestController;
use App\Http\Controllers\Api\Mekanik\RepairReportController;

// Controller Teknik
use App\Http\Controllers\Api\Teknik\MekanikController;
use App\Http\Controllers\Api\Asset\AssetController;
use App\Http\Controllers\Api\Asset\MaintenanceRecommendationController;
use App\Http\Controllers\Api\Teknik\MaintenanceTaskController;
use App\Http\Controllers\Api\Knowledge\KnowledgeDocumentController;

Route::post('/auth/login', [ApiAuthController::class, 'login']);
Route::get('/assets/{asset}/qr-detail', [AssetController::class, 'qrDetail'])->name('assets.qr-detail');

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Endpoint umum (Mekanik & Teknik bisa akses)
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    // Group KHUSUS TEKNIK
    Route::middleware(['role:teknik'])->group(function () {
        // Rute akun mekanik
        Route::get('/mekanik', [MekanikController::class, 'index']);
        Route::post('/mekanik', [MekanikController::class, 'store']);
        Route::get('/mekanik/{id}', [MekanikController::class, 'show']);
        Route::put('/mekanik/{id}', [MekanikController::class, 'update']);
        Route::delete('/mekanik/{id}', [MekanikController::class, 'destroy']);
        // Rute aset
        Route::post('/assets', [AssetController::class, 'store']);
        Route::post('/assets/{asset}/qr-code', [AssetController::class, 'regenerateQrCode']);
        Route::put('/assets/{id}', [AssetController::class, 'update']);
        Route::delete('/assets/{id}', [AssetController::class, 'destroy']);
        // Rute Maintenance
        Route::post('/maintenance-tasks', [MaintenanceTaskController::class, 'store']);
        Route::put('/maintenance-tasks/{id}', [MaintenanceTaskController::class, 'update']);
        Route::delete('/maintenance-tasks/{id}', [MaintenanceTaskController::class, 'destroy']);
        Route::get('/maintenance-due-assets', [MaintenanceTaskController::class, 'dueAssets']);
        // Rute verify pengajuan perbaikan
        Route::patch('/repair-requests/{id}/verify', [RepairRequestController::class, 'verify']);
        // Rute knowledge
        Route::delete('/knowledge-documents/{id}', [KnowledgeDocumentController::class, 'destroy']);
        // Rute Retrieval Log
        Route::delete('/retrieval-logs/{id}', [RetrievalLogController::class, 'destroy']);
        // Rute Ingest
        Route::post('/ingestion/assets/{asset}/profile', [IngestionController::class, 'ingestAssetProfile']);
        Route::post('/ingestion/assets/{asset}/manual-book', [IngestionController::class, 'ingestAssetManualBook']);
        Route::post('/ingestion/maintenance-reports/{id}', [IngestionController::class, 'ingestMaintenanceReport']);
        Route::post('/ingestion/repair-reports/{id}', [IngestionController::class, 'ingestRepairReport']);
        Route::post('/ingestion/upload', [IngestionController::class, 'uploadDocument']);
        // Rute evaluation
        Route::get('/retrieval-evaluations', [RetrievalEvaluationController::class, 'index']);
        Route::get('/retrieval-evaluations/{id}', [RetrievalEvaluationController::class, 'show']);
        Route::post('/retrieval-evaluations/evaluate', [RetrievalEvaluationController::class, 'evaluate']);
        Route::delete('/retrieval-evaluations/{id}', [RetrievalEvaluationController::class, 'destroy']);
        });

    // Group KHUSUS MEKANIK
    Route::middleware(['role:mekanik'])->group(function () {
        // Rute Jam jalan
        Route::post('/operating-hours', [OperatingHourController::class, 'store']);
        Route::put('/operating-hours/{id}', [OperatingHourController::class, 'update']);
        Route::delete('/operating-hours/{id}', [OperatingHourController::class, 'destroy']);
        // Rute Maintenance Report
        Route::post('/maintenance-reports', [MaintenanceReportController::class, 'store']);
        Route::put('/maintenance-reports/{id}', [MaintenanceReportController::class, 'update']);
        Route::delete('/maintenance-reports/{id}', [MaintenanceReportController::class, 'destroy']);
        // Rute RepairRequest
        Route::post('/repair-requests', [RepairRequestController::class, 'store']);
        Route::put('/repair-requests/{id}', [RepairRequestController::class, 'update']);
        Route::delete('/repair-requests/{id}', [RepairRequestController::class, 'destroy']);
        //Rute RepairReport
        Route::post('/repair-reports', [RepairReportController::class, 'store']);
        Route::put('/repair-reports/{id}', [RepairReportController::class, 'update']);
        Route::delete('/repair-reports/{id}', [RepairReportController::class, 'destroy']);
    });

    // Group BERSAMA (Keduanya bisa akses secara eksplisit)
    Route::middleware(['role:teknik,mekanik'])->group(function () {
        Route::get('/assets', [AssetController::class, 'index']);
        Route::get('/assets/{id}', [AssetController::class, 'show']);

        Route::get('/operating-hours', [OperatingHourController::class, 'index']);
        Route::get('/operating-hours/{id}', [OperatingHourController::class, 'show']);

        Route::get('/maintenance-tasks', [MaintenanceTaskController::class, 'index']);
        Route::get('/maintenance-tasks/{id}', [MaintenanceTaskController::class, 'show']);
        Route::patch('/maintenance-tasks/{id}/status', [MaintenanceTaskController::class, 'updateStatus']);

        Route::get('/maintenance-reports',[MaintenanceReportController::class, 'index']);
        Route::get('/maintenance-reports/{id}',[MaintenanceReportController::class, 'show']);

        Route::get('/repair-requests', [RepairRequestController::class, 'index']);
        Route::get('/repair-requests/{id}', [RepairRequestController::class, 'show']);

        Route::get('/repair-reports', [RepairReportController::class, 'index']);
        Route::get('/repair-reports/{id}', [RepairReportController::class, 'show']);

        Route::get('/knowledge-documents', [KnowledgeDocumentController::class, 'index']);
        Route::get('/knowledge-documents/{id}', [KnowledgeDocumentController::class, 'show']);

        Route::post('/chat/ask', [ChatbotController::class, 'ask']);
        Route::get('/chat/sessions', [ChatbotController::class, 'sessions']);
        Route::get('/chat/histories', [ChatbotController::class, 'history']);
        Route::get('/chat/histories/{id}', [ChatbotController::class, 'show']);
        Route::delete('/chat/histories/{id}', [ChatbotController::class, 'destroy']);

        Route::get('/retrieval-logs', [RetrievalLogController::class, 'index']);
        Route::get('/retrieval-logs/{id}', [RetrievalLogController::class, 'show']);

        Route::get('/assets/{id}/knowledge-summary', [AssetController::class, 'knowledgeSummary']);

        Route::get('/assets/{assetId}/recommendation', [MaintenanceRecommendationController::class, 'show']);
    });
});
