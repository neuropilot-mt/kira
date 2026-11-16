<?php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\PortfolioController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortfolioController::class, 'home']);
Route::get('/photos/photos.json', [PortfolioController::class, 'manifest']);
Route::get('/photos/{directory}/{filename}', [PortfolioController::class, 'file'])
    ->whereIn('directory', ['originals', 'previews'])
    ->where('filename', '[A-Za-z0-9][A-Za-z0-9._-]*');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [LoginController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [LoginController::class, 'store'])
        ->middleware('throttle:admin-login')
        ->name('admin.login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/admin/logout', [LoginController::class, 'destroy'])->name('admin.logout');
    Route::get('/admin', [PhotoController::class, 'index'])->name('admin.photos.index');
    Route::post('/admin/photos', [PhotoController::class, 'store'])->name('admin.photos.store');
    Route::post('/admin/photos/{photo}/hero', [PhotoController::class, 'hero'])->name('admin.photos.hero');
    Route::post('/admin/photos/{photo}/move/{direction}', [PhotoController::class, 'move'])
        ->whereIn('direction', ['up', 'down'])
        ->name('admin.photos.move');
    Route::delete('/admin/photos/{photo}', [PhotoController::class, 'destroy'])->name('admin.photos.destroy');
});
