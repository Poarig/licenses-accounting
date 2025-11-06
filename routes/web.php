<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
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
    // Products
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/deleted', [ProductController::class, 'getDeleted'])->name('products.deleted');

    // Organizations
    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/deleted', [OrganizationController::class, 'getDeleted'])->name('organizations.deleted');
    
    // Licenses
    Route::get('/licenses', [LicenseController::class, 'index'])->name('licenses.index');
    Route::get('/organizations/{organization}/licenses', [LicenseController::class, 'organizationLicenses'])->name('licenses.organization');
    Route::get('/licenses/{license}/download-file', [LicenseController::class, 'downloadFile'])->name('licenses.download-file');
    Route::get('/licenses/deleted', [LicenseController::class, 'getDeleted'])->name('licenses.deleted');
    Route::get('/organizations/{organization}/licenses/deleted', [LicenseController::class, 'getOrganizationDeleted'])->name('licenses.organization.deleted');
    
    // Pincodes
    Route::get('/licenses/{license}/pincodes', [PincodeController::class, 'index'])->name('pincodes.index');
    Route::get('/actions/{action}/download-file', [PincodeController::class, 'downloadFile'])->name('pincodes.download-file');
    Route::get('/licenses/{license}/pincodes/deleted', [PincodeController::class, 'getDeleted'])->name('pincodes.deleted');
    
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
    // Products
    Route::post('/products', [ProductController::class, 'store']);
    Route::post('/products/{product}/update-field', [ProductController::class, 'updateField']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/products/{product}/restore', [ProductController::class, 'restore']);

    // Organizations
    Route::post('/organizations', [OrganizationController::class, 'store']);
    Route::post('/organizations/{organization}/update-field', [OrganizationController::class, 'updateField']);
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);
    Route::post('/organizations/{organization}/restore', [OrganizationController::class, 'restore']);
    
    // Licenses
    Route::post('/licenses', [LicenseController::class, 'store']);
    Route::post('/licenses/{license}/update-field', [LicenseController::class, 'updateField']);
    Route::delete('/licenses/{license}', [LicenseController::class, 'destroy']);
    Route::post('/licenses/{license}/restore', [LicenseController::class, 'restore']);
    
    // Pincodes
    Route::post('/pincodes', [PincodeController::class, 'store']);
    Route::post('/pincodes/{pincode}/update-status', [PincodeController::class, 'updateStatus']);
    Route::post('/pincodes/change-status-with-comment', [PincodeController::class, 'changeStatusWithComment']);
    Route::delete('/pincodes/{pincode}', [PincodeController::class, 'destroy']);
    Route::post('/pincodes/{pincode}/restore', [PincodeController::class, 'restore']);
    
    // Users
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::post('/users/{user}/restore', [UserController::class, 'restore']);
});