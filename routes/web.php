<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\CalibrationController;
use App\Http\Controllers\MaintenanceController;

/*
|--------------------------------------------------------------------------
| Web Routes (PASIEN JOURNEY RS Universitas Indonesia)
|--------------------------------------------------------------------------
*/

// 1. Rute Autentikasi (Publik)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/refresh-captcha', [AuthController::class, 'refreshCaptcha'])->name('captcha.refresh');

// Rute Publik (Hasil Scan QR Code) — Tanpa Login
Route::match(['get', 'post'], '/pasien/qr/{serial_number}', [MaintenanceController::class, 'publicHistory'])->name('alat.public');

// 2. Rute yang butuh LOGIN
Route::middleware(['auth'])->group(function () {

    // --- DASHBOARD ---
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // --- RUTE HANYA BACA (Semua role boleh akses) ---
    Route::get('/pasien', [EquipmentController::class, 'index'])->name('equipments.index');
    Route::get('/pasien/export', [EquipmentController::class, 'exportCsv'])->name('equipments.export');
    Route::get('/kontrol-pasien', [CalibrationController::class, 'index'])->name('calibrations.index');
    Route::get('/kontrol-pasien/export', [CalibrationController::class, 'exportCsv'])->name('calibrations.export');
    Route::get('/rekam-medis', [MaintenanceController::class, 'index'])->name('maintenances.index');
    Route::get('/rekam-medis/export', [MaintenanceController::class, 'exportCSV'])->name('maintenances.export');
    Route::get('/pasien-pulang', [MaintenanceController::class, 'pulang'])->name('maintenances.pulang');
    Route::get('/beds', [\App\Http\Controllers\BedController::class, 'index'])->name('beds.index');
    Route::get('/rekam-medis/{serial_number}', [MaintenanceController::class, 'history'])->name('maintenances.history');
    Route::get('/rekam-medis/{serial_number}/detail', [MaintenanceController::class, 'patientDetail'])->name('maintenances.patient_detail');
    Route::put('/rekam-medis/{serial_number}/detail', [MaintenanceController::class, 'updatePatientDetail'])->name('maintenances.update_patient_detail');
    Route::get('/rekam-medis/{serial_number}/qr', [MaintenanceController::class, 'printQr'])->name('maintenances.qr');
    Route::post('/beds/sync', [\App\Http\Controllers\BedController::class, 'sync'])->name('beds.sync');
    Route::post('/beds/nurses/{equipment}', [\App\Http\Controllers\BedController::class, 'updateNurses'])->name('beds.update_nurses');
    Route::post('/beds/ews/{equipment}', [\App\Http\Controllers\BedController::class, 'updateEws'])->name('beds.update_ews');

    // Mutu Dashboards
    Route::prefix('mutu')->name('mutu.')->group(function() {
        Route::get('/kepatuhan-visit', [\App\Http\Controllers\MutuController::class, 'kepatuhanVisit'])->name('kepatuhan-visit');
        Route::get('/respon-konsul', [\App\Http\Controllers\MutuController::class, 'responKonsul'])->name('respon-konsul');
        Route::get('/distribusi-dpjp', [\App\Http\Controllers\MutuController::class, 'distribusiDpjp'])->name('distribusi-dpjp');
        Route::get('/jadwal-ners', [\App\Http\Controllers\MutuController::class, 'jadwalNers'])->name('jadwal-ners');
    });

    // --- RUTE EDIT/HAPUS/TAMBAH (Admin & User biasa) ---
    Route::middleware(['role:admin,user'])->group(function () {
        // Pasien (Alat)
        Route::post('/pasien', [EquipmentController::class, 'store'])->name('equipments.store');
        Route::put('/pasien/{equipment}', [EquipmentController::class, 'update'])->name('equipments.update');
        Route::delete('/pasien/{equipment}', [EquipmentController::class, 'destroy'])->name('equipments.destroy');
        Route::post('/pasien/import', [EquipmentController::class, 'importCsv'])->name('equipments.import');

        // Kontrol Pasien (Kalibrasi)
        Route::post('/kontrol-pasien', [CalibrationController::class, 'store'])->name('calibrations.store');
        Route::put('/kontrol-pasien/{calibration}', [CalibrationController::class, 'update'])->name('calibrations.update');
        Route::delete('/kontrol-pasien/{calibration}', [CalibrationController::class, 'destroy'])->name('calibrations.destroy');
        Route::post('/kontrol-pasien/import', [CalibrationController::class, 'importCsv'])->name('calibrations.import');

        // Rekam Medis (Pemeliharaan)
        Route::post('/rekam-medis', [MaintenanceController::class, 'store'])->name('maintenances.store');
        Route::put('/rekam-medis/{maintenance}', [MaintenanceController::class, 'update'])->name('maintenances.update');
        Route::delete('/rekam-medis/{maintenance}', [MaintenanceController::class, 'destroy'])->name('maintenances.destroy');
    });

    // --- MANAJEMEN AKUN: Akses Bersama (Admin & Editor) ---
    Route::middleware(['role:admin,user'])->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        // Editor hanya boleh promote viewer -> editor
        Route::put('/{id}/promote', [UserController::class, 'editorPromote'])->name('promote');
    });

    // --- MANAJEMEN AKUN: Hanya Admin ---
    Route::middleware(['role:admin'])->prefix('users')->name('users.')->group(function () {
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{id}', [UserController::class, 'updateRole'])->name('updateRole');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });

    // --- MANAJEMEN DATA NERS: Admin & Editor (user) ---
    Route::middleware(['role:admin,user'])->prefix('nurses')->name('nurses.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NurseController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\NurseController::class, 'store'])->name('store');
        Route::put('/{nurse}', [\App\Http\Controllers\NurseController::class, 'update'])->name('update');
        Route::delete('/{nurse}', [\App\Http\Controllers\NurseController::class, 'destroy'])->name('destroy');
    });
});