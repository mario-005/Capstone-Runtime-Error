<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MenuController;
use App\RoleCode;
use Illuminate\Support\Facades\Route;

$authenticatedRoles = implode(',', array_map(
    static fn (RoleCode $role): string => $role->value,
    RoleCode::cases(),
));

Route::prefix('v1')->group(function () use ($authenticatedRoles): void {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('api.v1.auth.login');

    Route::middleware(['auth:sanctum', 'role:'.$authenticatedRoles])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->name('api.v1.auth.logout');
        Route::get('/me', [AuthController::class, 'me'])
            ->name('api.v1.me');
        Route::apiResource('menus', MenuController::class)->only(['index', 'store', 'show', 'update']);
    });
});
