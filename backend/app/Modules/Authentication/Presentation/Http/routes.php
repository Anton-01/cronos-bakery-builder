<?php

declare(strict_types=1);

use App\Modules\Authentication\Presentation\Http\Controllers\AuthController;
use App\Modules\Authentication\Presentation\Http\Controllers\EmailVerificationController;
use App\Modules\Authentication\Presentation\Http\Controllers\PasswordResetController;
use App\Modules\Authentication\Presentation\Http\Controllers\ProfileController;
use App\Modules\Authentication\Presentation\Http\Controllers\RegisterController;
use App\Modules\Authentication\Presentation\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Authentication API routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function (): void {
    // --- Guest endpoints --------------------------------------------------
    Route::post('register', RegisterController::class)->middleware('throttle:6,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    Route::post('password/forgot', [PasswordResetController::class, 'forgot'])->middleware('throttle:6,1');
    Route::post('password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1');

    // --- Social login -----------------------------------------------------
    Route::get('social/{provider}/redirect', [SocialAuthController::class, 'redirect']);
    Route::get('social/{provider}/callback', [SocialAuthController::class, 'callback']);

    // --- Email verification ----------------------------------------------
    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');

    // --- Authenticated customer endpoints --------------------------------
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,1');

        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::put('profile/password', [ProfileController::class, 'changePassword']);
    });
});
