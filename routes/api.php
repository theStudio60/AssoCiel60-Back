<?php

use Illuminate\Support\Facades\Route;

// ============================================================================
// CONTROLLERS - Admin
// ============================================================================
use App\Http\Controllers\Api\Admin\MemberController;
use App\Http\Controllers\Api\Admin\SubscriptionController;
use App\Http\Controllers\Api\Admin\InvoiceController;
use App\Http\Controllers\Api\Admin\PlanController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\Admin\EmailController;
use App\Http\Controllers\Api\Admin\ActivityLogController;

// ============================================================================
// CONTROLLERS - Member
// ============================================================================
use App\Http\Controllers\Api\Member\MemberDashboardController;

// ============================================================================
// CONTROLLERS - General
// ============================================================================
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\ProfileController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| Routes publiques pour login/logout/vérification 2FA
*/
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-2fa', [AuthController::class, 'verify2FA']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']); // ← Nouveau
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

/*
|--------------------------------------------------------------------------
| Membership Routes (Public)
|--------------------------------------------------------------------------
| Routes d'inscription et sélection de plans
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
| Profile Routes (Authenticated - Admin & Member)
|--------------------------------------------------------------------------
| Routes de gestion du profil utilisateur
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);
    Route::put('/profile', [ProfileController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
| Routes réservées aux administrateurs uniquement
*/
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    
    // ========================================================================
    // Dashboard Stats
    // ========================================================================
    Route::get('/settings/stats', [SettingsController::class, 'getStats']);
    
    // ========================================================================
    // Members Management
    // ========================================================================
    Route::prefix('members')->group(function () {
        Route::get('/', [MemberController::class, 'index']);
        Route::get('/stats', [MemberController::class, 'stats']);
        Route::get('/export', [MemberController::class, 'export']);
        Route::get('/{id}', [MemberController::class, 'show']);
        Route::put('/{id}', [MemberController::class, 'update']);
        Route::delete('/{id}', [MemberController::class, 'destroy']);
    });
    
    // ========================================================================
    // Subscriptions Management
    // ========================================================================
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::get('/stats', [SubscriptionController::class, 'stats']);
        Route::get('/export', [SubscriptionController::class, 'export']);
        Route::get('/{id}', [SubscriptionController::class, 'show']);
        Route::put('/{id}/status', [SubscriptionController::class, 'updateStatus']);
        Route::post('/{id}/renew', [SubscriptionController::class, 'renew']);
        Route::post('/{id}/cancel', [SubscriptionController::class, 'cancel']);
    });
    
    // ========================================================================
    // Invoices Management
    // ========================================================================
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::get('/stats', [InvoiceController::class, 'stats']);
        Route::get('/export', [InvoiceController::class, 'export']);
        Route::get('/{id}/download-pdf', [InvoiceController::class, 'downloadPdf']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
        Route::put('/{id}/status', [InvoiceController::class, 'updateStatus']);
        Route::post('/{id}/mark-paid', [InvoiceController::class, 'markAsPaid']);
        Route::post('/{id}/send-reminder', [InvoiceController::class, 'sendReminder']);
    });
    
    // ========================================================================
    // Plans Management
    // ========================================================================
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
    
    // ========================================================================
    // Email Management
    // ========================================================================
    Route::prefix('emails')->group(function () {
        Route::get('/settings', [EmailController::class, 'getSettings']);
        Route::put('/settings', [EmailController::class, 'updateSettings']);
        Route::post('/send-test', [EmailController::class, 'sendTest']);
        Route::get('/logs', [EmailController::class, 'getLogs']);
    });
    
    // ========================================================================
    // Activity Logs
    // ========================================================================
    Route::prefix('activity-logs')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index']);
        Route::get('/stats', [ActivityLogController::class, 'stats']);
        Route::get('/export', [ActivityLogController::class, 'export']);
        Route::delete('/cleanup', [ActivityLogController::class, 'cleanup']);
    });
    
    // ========================================================================
    // Settings
    // ========================================================================
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings', [SettingsController::class, 'update']);

    Route::prefix('reports')->group(function () {
        Route::get('/monthly', [App\Http\Controllers\Api\Admin\ReportController::class, 'getMonthlyData']);
        Route::get('/monthly/pdf', [App\Http\Controllers\Api\Admin\ReportController::class, 'generateMonthlyPdf']);
    });
});

/*
|--------------------------------------------------------------------------
| MEMBER ROUTES
|--------------------------------------------------------------------------
| Routes réservées aux membres uniquement
*/
Route::middleware('auth:sanctum')->prefix('member')->group(function () {
    
    // ========================================================================
    // Subscription
    // ========================================================================
    Route::get('/subscription', [MemberDashboardController::class, 'getSubscription']);
    Route::post('/subscription/renew', [MemberDashboardController::class, 'requestRenewal']);
    
    // ========================================================================
    // Invoices
    // ========================================================================
    Route::get('/invoices', [MemberDashboardController::class, 'getInvoices']);
    Route::get('/invoices/{id}/download', [MemberDashboardController::class, 'downloadInvoice']);
    
    // ========================================================================
    // Settings
    // ========================================================================
    Route::put('/settings', [MemberDashboardController::class, 'updateSettings']);
});

Route::middleware('auth:sanctum')->prefix('member')->group(function () {
    Route::post('/subscription/toggle-auto-renew', [MemberDashboardController::class, 'toggleAutoRenew']);
});

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/search', [App\Http\Controllers\Api\Admin\SearchController::class, 'globalSearch']);
});