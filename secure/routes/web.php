<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\SettingsController;
use App\Http\Controllers\TrailerReservationController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// --------------------
// Default entry
// --------------------
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login.form');
})->name('root');

// --------------------
// Trailer reservations (alleen ingelogde medewerkers)
// --------------------
Route::middleware(['auth', 'employee'])->group(function () {
    Route::get('/trailers', [TrailerReservationController::class, 'index'])
        ->name('trailers.index');

    Route::get('/trailers/{trailerId}/blocked-dates', [TrailerReservationController::class, 'blockedDates'])
        ->whereNumber('trailerId')
        ->name('trailers.blocked_dates');

    Route::get('/reservations/create', [TrailerReservationController::class, 'create'])
        ->name('reservations.create');

    Route::post('/reservations', [TrailerReservationController::class, 'store'])
        ->name('reservations.store');

    Route::get('/reservations/{id}/edit', [TrailerReservationController::class, 'edit'])
        ->name('reservations.edit');

    Route::put('/reservations/{id}', [TrailerReservationController::class, 'update'])
        ->name('reservations.update');

    Route::delete('/reservations/{id}', [TrailerReservationController::class, 'destroy'])
        ->name('reservations.destroy');
});

// --------------------
// Dashboard (alleen ingelogd)
// --------------------
Route::middleware(['auth', 'employee'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/reservation/{id}', [DashboardController::class, 'showReservation'])->name('dashboard.reservation.show');
    Route::delete('/dashboard/reservation/{id}', [DashboardController::class, 'destroyReservation'])->name('dashboard.reservation.destroy');

    Route::get('/dashboard/calendar-data', [DashboardController::class, 'calendarData'])->name('dashboard.calendar.data');
    Route::get('/dashboard/print/next-week', [DashboardController::class, 'printNextWeek'])->name('dashboard.print.next_week');
    Route::get('/dashboard/print/range', [DashboardController::class, 'printRange'])->name('dashboard.print.range');

    // Gebruikersbeheer (alleen admin)
    Route::middleware('can:admin')->group(function () {
        Route::get('/users', [DashboardController::class, 'users'])->name('users.index');
        Route::delete('/users/{id}', [DashboardController::class, 'destroyUser'])->name('users.destroy');
        Route::get('/register', [DashboardController::class, 'showRegisterForm'])->name('register.form');
        Route::post('/register', [RegisterController::class, 'register'])->name('register');
    });
});

// --------------------
// Login
// --------------------
Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login.form')
    ->middleware('guest');

Route::post('/login', [LoginController::class, 'login'])
    ->name('login')
    ->middleware('guest');

// --------------------
// Logout
// --------------------
Route::post('/logout', [LogoutController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// --------------------
// Register
// --------------------
// Publieke registratie is uitgeschakeld; alleen admins kunnen gebruikers toevoegen.
// (Admin route /register staat bovenin binnen auth+can:admin.)

// --------------------
// Email verification
// --------------------
Route::get('/verify-email/{token}', [EmailVerificationController::class, 'verify'])
    ->name('verification.verify')
    ->middleware('guest');

Route::get('/email/resend', [EmailVerificationController::class, 'showResendForm'])
    ->name('verification.resend.form')
    ->middleware('guest');

Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
    ->name('verification.resend')
    ->middleware('guest');

// --------------------
// Password reset
// --------------------
Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])
    ->name('password.forgot.form')
    ->middleware('guest');

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
    ->name('password.forgot')
    ->middleware('guest');

Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])
    ->name('password.reset.form')
    ->middleware('guest');

Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])
    ->name('password.reset')
    ->middleware('guest');

// --------------------
// User settings
// --------------------
Route::get('/settings', [SettingsController::class, 'showSettings'])
    ->name('settings.show')
    ->middleware('auth');

Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])
    ->name('settings.updateProfile')
    ->middleware('auth');

Route::put('/settings/password', [SettingsController::class, 'updatePassword'])
    ->name('settings.updatePassword')
    ->middleware('auth');
