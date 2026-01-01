<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\Admin\MemberController;
use App\Http\Controllers\Api\Admin\SubscriptionController;
use App\Http\Controllers\Api\Admin\InvoiceController;
use App\Http\Controllers\Api\Admin\PlanController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\Admin\EmailController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-2fa', [AuthController::class, 'verify2FA']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

/*
|--------------------------------------------------------------------------
| Membership Routes (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('membership')->group(function () {
    Route::get('/plans', [MembershipController::class, 'getPlans']);
    Route::post('/register', [MembershipController::class, 'register']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/payment/confirm', [MembershipController::class, 'confirmPayment']);
    });
});

/*
|--------------------------------------------------------------------------
| Profile Routes (Authenticated)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);
    Route::put('/profile', [ProfileController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    
    // Dashboard Stats
    Route::get('/settings/stats', [SettingsController::class, 'getStats']);
    
    // Members Management
    Route::prefix('members')->group(function () {
        Route::get('/', [MemberController::class, 'index']);
        Route::get('/stats', [MemberController::class, 'stats']);
        Route::get('/export', [MemberController::class, 'export']);
        Route::get('/{id}', [MemberController::class, 'show']);
        Route::put('/{id}', [MemberController::class, 'update']);
        Route::delete('/{id}', [MemberController::class, 'destroy']);
    });
    
    // Subscriptions Management
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::get('/stats', [SubscriptionController::class, 'stats']);
        Route::get('/export', [SubscriptionController::class, 'export']);
        Route::get('/{id}', [SubscriptionController::class, 'show']);
        Route::put('/{id}/status', [SubscriptionController::class, 'updateStatus']);
        Route::post('/{id}/renew', [SubscriptionController::class, 'renew']);
        Route::post('/{id}/cancel', [SubscriptionController::class, 'cancel']);
    });
    
    // Invoices Management
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::get('/stats', [InvoiceController::class, 'stats']);
        Route::get('/{id}/download-pdf', [InvoiceController::class, 'downloadPdf']);
        Route::get('/export', [InvoiceController::class, 'export']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
        Route::put('/{id}/status', [InvoiceController::class, 'updateStatus']);
        Route::post('/{id}/mark-paid', [InvoiceController::class, 'markAsPaid']);
        Route::post('/{id}/send-reminder', [InvoiceController::class, 'sendReminder']);
    });
    
    // Plans Management
    Route::prefix('plans')->group(function () {
        Route::get('/', [PlanController::class, 'index']);
        Route::get('/stats', [PlanController::class, 'stats']);
        Route::get('/export', [PlanController::class, 'export']);
        Route::post('/', [PlanController::class, 'store']);
        Route::get('/{id}', [PlanController::class, 'show']);
        Route::put('/{id}', [PlanController::class, 'update']);
        Route::delete('/{id}', [PlanController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [PlanController::class, 'toggleStatus']);
    });
    
    // Email Management
    Route::prefix('emails')->group(function () {
        Route::get('/settings', [EmailController::class, 'getSettings']);
        Route::put('/settings', [EmailController::class, 'updateSettings']);
        Route::post('/send-test', [EmailController::class, 'sendTest']);
        Route::get('/logs', [EmailController::class, 'getLogs']);
    });
    
    // Settings
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings', [SettingsController::class, 'update']);
});


use App\Http\Controllers\Api\Admin\ActivityLogController;

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    // Activity Logs
    Route::prefix('activity-logs')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index']);
        Route::get('/stats', [ActivityLogController::class, 'stats']);
        Route::get('/export', [ActivityLogController::class, 'export']);
        Route::delete('/cleanup', [ActivityLogController::class, 'cleanup']);
    });
});