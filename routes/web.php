<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// CSV Upload Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/uploads', function () {
        return Inertia::render('Uploads');
    })->name('uploads');

    Route::get('/api/uploads', [FileController::class, 'index'])->name('api.uploads.index');
    Route::post('/api/uploads', [FileController::class, 'upload'])->name('api.uploads.store');
    Route::get('/api/uploads/{id}', [FileController::class, 'show'])->name('api.uploads.show');
});

require __DIR__.'/settings.php';
