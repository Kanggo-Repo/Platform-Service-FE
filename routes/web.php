<?php

use App\Http\Controllers\Auth\PlatformOidcController;
use App\Http\Controllers\PlatformAccessController;
use App\Http\Controllers\PlatformAdminController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\PlatformWorkspaceController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/workspace');

Route::get('/login', [PlatformOidcController::class, 'showLogin'])->name('login');
Route::get('/auth/redirect', [PlatformOidcController::class, 'redirect'])->name('auth.redirect');
Route::get('/auth/callback', [PlatformOidcController::class, 'callback'])->name('auth.callback');
Route::post('/logout', [PlatformOidcController::class, 'logout'])->name('auth.logout');

Route::middleware('platform.auth')->group(function () {
    Route::get('/access-pending', [PlatformAccessController::class, 'pending'])->name('platform.access.pending');
    Route::get('/workspace', [PlatformWorkspaceController::class, 'index'])->name('workspace.index');
    Route::get('/admin/roles', [PlatformAdminController::class, 'roles'])->name('admin.roles.index');
    Route::get('/admin/registration', [PlatformAdminController::class, 'registration'])->name('admin.registration.edit');
    Route::put('/admin/registration', [PlatformAdminController::class, 'updateRegistration'])->name('admin.registration.update');

    Route::prefix('settings/roles')
        ->name('settings.roles.')
        ->middleware('platform.permission:roles.view')
        ->group(function () {
            Route::get('/', [RoleManagementController::class, 'index'])->name('index');
            Route::post('/', [RoleManagementController::class, 'store'])->middleware('platform.permission:roles.create')->name('store');
            Route::put('/{role}', [RoleManagementController::class, 'update'])->middleware('platform.permission:roles.update')->name('update');
            Route::delete('/{role}', [RoleManagementController::class, 'destroy'])->middleware('platform.permission:roles.delete')->name('destroy');
        });

    Route::prefix('settings/users')
        ->name('settings.users.')
        ->middleware('platform.permission:users.view')
        ->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::post('/', [UserManagementController::class, 'store'])->middleware('platform.permission:users.create')->name('store');
            Route::put('/{user}', [UserManagementController::class, 'update'])->middleware('platform.permission:users.update')->name('update');
            Route::delete('/{user}', [UserManagementController::class, 'destroy'])->middleware('platform.permission:users.delete')->name('destroy');
            Route::post('/registration', [UserManagementController::class, 'updateRegistration'])->middleware('platform.permission:users.update')->name('registration.update');
        });
});
