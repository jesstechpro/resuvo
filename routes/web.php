<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/job-description', [DashboardController::class, 'storeJobDescription'])->name('dashboard.job-description.store');
    Route::post('/dashboard/resume', [DashboardController::class, 'uploadResume'])->name('dashboard.resume.upload');
    Route::post('/dashboard/generate', [DashboardController::class, 'generate'])->name('dashboard.generate');
    Route::get('/dashboard/resume/{resume}/download', [DashboardController::class, 'download'])->name('dashboard.resume.download');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
