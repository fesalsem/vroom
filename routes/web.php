<?php

use App\Http\Controllers\CustomerRegistrationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| Welcome page and test drive registration — accessible without
| authentication.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('test-drive')->name('test-drive.')->group(function () {
    Route::get('/', [CustomerRegistrationController::class, 'create'])
        ->name('create');

    Route::post('/', [CustomerRegistrationController::class, 'store'])
        ->name('store');

    Route::get('/thank-you', [CustomerRegistrationController::class, 'thankYou'])
        ->name('thank-you');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
|
| Dashboard and profile management — requires authentication.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Agent Routes
|--------------------------------------------------------------------------
|
| Registration management for sales agents — requires authentication
| and email verification.
|
| Resourceful mapping:
|   GET|HEAD   /agent/registrations              → index   (list)
|   GET        /agent/registrations/{registration} → show   (detail)
|
| Custom actions (non-CRUD):
|   PATCH  /agent/registrations/{registration}/status         → updateStatus
|   PATCH  /agent/registrations/{registration}/down-payment   → updateDownPayment
|   POST   /agent/registrations/{registration}/check-promotion → checkPromotion
|   POST   /agent/registrations/{registration}/calculate-loan → calculateLoan
|
*/

Route::middleware(['auth', 'verified'])
    ->prefix('agent')
    ->name('agent.')
    ->group(function () {

    // Resourceful CRUD — only index + show (no create/store/edit/destroy)
    Route::resource('registrations', RegistrationController::class)
        ->only(['index', 'show']);

    // Custom state transition
    Route::patch('registrations/{registration}/status', [
        RegistrationController::class, 'updateStatus',
    ])->name('registrations.update-status');

    // Down payment update
    Route::patch('registrations/{registration}/down-payment', [
        RegistrationController::class, 'updateDownPayment',
    ])->name('registrations.update-down-payment');

    // Promotion eligibility check
    Route::post('registrations/{registration}/check-promotion', [
        RegistrationController::class, 'checkPromotion',
    ])->name('registrations.check-promotion');

    // Loan calculation
    Route::post('registrations/{registration}/calculate-loan', [
        RegistrationController::class, 'calculateLoan',
    ])->name('registrations.calculate-loan');
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Laravel Breeze authentication scaffolding (login, register, password
| reset, email verification).
|
*/

require __DIR__.'/auth.php';
