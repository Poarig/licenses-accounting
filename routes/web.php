<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\PincodeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ActionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/organizations');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    // Organizations
    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    
    // Licenses
    Route::get('/licenses', [LicenseController::class, 'index'])->name('licenses.index');
    Route::get('/organizations/{organization}/licenses', [LicenseController::class, 'organizationLicenses'])->name('licenses.organization');
    Route::get('/licenses/{license}/download-file', [LicenseController::class, 'downloadFile'])->name('licenses.download-file');
    
    // Pincodes
    Route::get('/licenses/{license}/pincodes', [PincodeController::class, 'index'])->name('pincodes.index');
    Route::get('/actions/{action}/download-file', [PincodeController::class, 'downloadFile'])->name('pincodes.download-file');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    
    // Actions (History)
    Route::get('/actions', [ActionController::class, 'index'])->name('actions.index');

    
    // Users (только для администраторов) - ДОБАВЛЯЕМ MIDDLEWARE admin
    Route::middleware(['admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });
});


Route::middleware(['auth'])->group(function () {
    // Organizations
    Route::post('/organizations', [OrganizationController::class, 'store']);
    Route::post('/organizations/{organization}/update-field', [OrganizationController::class, 'updateField']);
    
    // Licenses
    Route::post('/licenses', [LicenseController::class, 'store']);
    Route::post('/licenses/{license}/update-field', [LicenseController::class, 'updateField']);
    
    // Pincodes
    Route::post('/pincodes', [PincodeController::class, 'store']);
    Route::post('/pincodes/{pincode}/update-status', [PincodeController::class, 'updateStatus']);
    Route::post('/pincodes/change-status-with-comment', [PincodeController::class, 'changeStatusWithComment']);
    
    // Users
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::post('/users/{user}/restore', [UserController::class, 'restore']);
});