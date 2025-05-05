<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BeritaController;
use App\Http\Controllers\API\PengumumanController;
use App\Http\Controllers\API\ProdiController;
use App\Http\Controllers\API\AgendaController;
use App\Http\Controllers\API\KaryaMahasiswaController;
use App\Http\Controllers\API\KerjasamaController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\MahasiswaController;
use App\Http\Controllers\API\MediaController;
use App\Http\Controllers\API\DashboardController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Profile kampus
Route::get('/profile', [ProfileController::class, 'index']);

// Berita
Route::get('/berita', [BeritaController::class, 'index']);
Route::get('/berita/latest/{limit?}', [BeritaController::class, 'getLatest']);
Route::get('/berita/{slug}', [BeritaController::class, 'show']);

// Pengumuman
Route::get('/pengumuman', [PengumumanController::class, 'index']);
Route::get('/pengumuman/latest/{limit?}', [PengumumanController::class, 'getLatest']);
Route::get('/pengumuman/{slug}', [PengumumanController::class, 'show']);

// Prodi
Route::get('/prodi', [ProdiController::class, 'index']);
Route::get('/prodi/{slug}', [ProdiController::class, 'show']);

// Agenda
Route::get('/agenda', [AgendaController::class, 'index']);
Route::get('/agenda/upcoming/{limit?}', [AgendaController::class, 'getUpcoming']);
Route::get('/agenda/{slug}', [AgendaController::class, 'show']);

// Kerjasama
Route::get('/kerjasama', [KerjasamaController::class, 'index']);
Route::get('/kerjasama/{slug}', [KerjasamaController::class, 'show']);

// Karya Mahasiswa
Route::get('/karya-mahasiswa', [KaryaMahasiswaController::class, 'index']);
Route::get('/karya-mahasiswa/{slug}', [KaryaMahasiswaController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // User with role mahasiswa
    Route::middleware(['role:mahasiswa'])->group(function () {
        // Karya Mahasiswa
        Route::post('/karya-mahasiswa', [KaryaMahasiswaController::class, 'store']);
        Route::put('/karya-mahasiswa/{id}', [KaryaMahasiswaController::class, 'update']);
        Route::delete('/karya-mahasiswa/{id}', [KaryaMahasiswaController::class, 'destroy']);
    });

    // User with role admin
    Route::middleware(['auth', 'role:admin'])->group(function () {
        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

        // Profile
        Route::put('/profile', [ProfileController::class, 'update']);

        // Berita
        Route::post('/berita', [BeritaController::class, 'store']);
        Route::put('/berita/{id}', [BeritaController::class, 'update']);
        Route::delete('/berita/{id}', [BeritaController::class, 'destroy']);

        // Pengumuman
        Route::post('/pengumuman', [PengumumanController::class, 'store']);
        Route::put('/pengumuman/{id}', [PengumumanController::class, 'update']);
        Route::delete('/pengumuman/{id}', [PengumumanController::class, 'destroy']);

        // Prodi
        Route::post('/prodi', [ProdiController::class, 'store']);
        Route::put('/prodi/{id}', [ProdiController::class, 'update']);
        Route::delete('/prodi/{id}', [ProdiController::class, 'destroy']);

        // Agenda
        Route::post('/agenda', [AgendaController::class, 'store']);
        Route::put('/agenda/{id}', [AgendaController::class, 'update']);
        Route::delete('/agenda/{id}', [AgendaController::class, 'destroy']);

        // Kerjasama
        Route::post('/kerjasama', [KerjasamaController::class, 'store']);
        Route::put('/kerjasama/{id}', [KerjasamaController::class, 'update']);
        Route::delete('/kerjasama/{id}', [KerjasamaController::class, 'destroy']);

        // Mahasiswa
        Route::get('/mahasiswa', [MahasiswaController::class, 'index']);
        Route::post('/mahasiswa', [MahasiswaController::class, 'store']);
        Route::get('/mahasiswa/{id}', [MahasiswaController::class, 'show']);
        Route::put('/mahasiswa/{id}', [MahasiswaController::class, 'update']);
        Route::delete('/mahasiswa/{id}', [MahasiswaController::class, 'destroy']);
        Route::post('/mahasiswa/import', [MahasiswaController::class, 'import']);

        // Media
        Route::post('/upload', [MediaController::class, 'upload']);
        Route::delete('/media/{id}', [MediaController::class, 'destroy']);

        // Karya Mahasiswa Approval
        Route::put('/karya-mahasiswa/{id}/approve', [KaryaMahasiswaController::class, 'approve']);
        Route::put('/karya-mahasiswa/{id}/reject', [KaryaMahasiswaController::class, 'reject']);
    });
});
