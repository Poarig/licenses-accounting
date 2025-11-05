<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\PincodeController;
use Illuminate\Support\Facades\Route;

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
});