<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ForgotPasswordController;
use App\Http\Controllers\Api\V1\GoalController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ResetPasswordController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\RecurringTransactionController;
use App\Http\Controllers\Api\V1\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Auth routes (public) with strict rate limiting
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:register');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

        // Password reset — public, no auth required
        Route::post('password/forgot', [ForgotPasswordController::class, 'sendResetLink']);
        Route::post('password/reset', [ResetPasswordController::class, 'reset']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('profile', [AuthController::class, 'profile']);
            Route::patch('profile', [AuthController::class, 'updateProfile']);

            // Account deletion — requires current_password confirmation
            Route::delete('account', [AuthController::class, 'deleteAccount']);
        });
    });

    // Protected routes with general API rate limiting
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::apiResource('accounts', AccountController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('transactions', TransactionController::class);
        Route::apiResource('budgets', BudgetController::class);
        Route::apiResource('tags', TagController::class);

        Route::apiResource('goals', GoalController::class);
        Route::post('goals/{goal}/deposit', [GoalController::class, 'deposit']);

        Route::post('attachments', [AttachmentController::class, 'store']);
        Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy']);

        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::apiResource('recurring-transactions', RecurringTransactionController::class);

        Route::get('reports', [ReportController::class, 'index']);
        Route::get('reports/export', [ReportController::class, 'export'])->middleware('throttle:5,1');
    });
});
