<?php

use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest/Public Routes
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

// Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard Route
    Route::get('/dashboard', [LeaveController::class, 'index'])
        ->name('dashboard');

    // Leave Management Routes
    Route::prefix('leave-requests')->name('leave-requests.')->group(function () {
        Route::get('/', [LeaveController::class, 'index'])
            ->name('index');
        Route::post('/', [LeaveController::class, 'store'])
            ->name('store');
        Route::get('/create', [LeaveController::class, 'create'])
            ->name('create');
        Route::delete('/{id}', [LeaveController::class, 'destroy'])
            ->name('destroy');

        Route::middleware(['role:admin'])->group(function () {
            Route::put('/{id}/status', [LeaveController::class, 'updateStatus'])
                ->name('update-status');
        });
    });

    // Profile Routes
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
