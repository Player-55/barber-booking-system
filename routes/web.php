<?php

use App\Http\Controllers\Admin\BarberController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\WalkInController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookingController;
use App\Models\Barber;
use App\Models\OpeningHour;
use App\Models\Service;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages (anyone can open)
|--------------------------------------------------------------------------
*/

// Homepage + booking form
Route::get('/', function () {
    $services = Service::where('is_active', true)->get();
    $barbers  = Barber::where('is_active', true)->get();
    $hours    = OpeningHour::weekMondayFirst();

    return view('welcome', compact('services', 'barbers', 'hours'));
})->name('home');

// Free time slots for the chosen barber/service/date (used by the booking form)
Route::get('/booking/slots', [BookingController::class, 'slots'])
    ->middleware('throttle:60,1')
    ->name('booking.slots');

// Save a booking (max 10 tries per minute per visitor, to stop spam)
Route::post('/booking/store', [BookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('booking.store');

/*
|--------------------------------------------------------------------------
| Admin login (only for people who are NOT logged in)
|--------------------------------------------------------------------------
*/

Route::redirect('/admin', '/admin/dashboard');

Route::middleware('guest')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminController::class, 'showLogin'])->name('login');

    // Max 5 login attempts per minute, to stop password guessing
    Route::post('/login', [AdminController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');
});

/*
|--------------------------------------------------------------------------
| Admin area (ONLY for the logged-in shop owner)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::patch('/bookings/{booking}/status', [AdminController::class, 'updateStatus'])->name('bookings.updateStatus');
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');

    // Owner manages services/prices and barbers
    Route::resource('services', ServiceController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('barbers', BarberController::class)->only(['index', 'store', 'update', 'destroy']);

    // Opening hours + barber days off
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::put('/schedule/hours', [ScheduleController::class, 'updateHours'])->name('schedule.hours');
    Route::post('/schedule/days-off', [ScheduleController::class, 'storeDayOff'])->name('schedule.daysoff.store');
    Route::delete('/schedule/days-off/{dayOff}', [ScheduleController::class, 'destroyDayOff'])->name('schedule.daysoff.destroy');

    // Walk-in / phone bookings added by the owner
    Route::get('/walk-in', [WalkInController::class, 'create'])->name('walkin.create');
    Route::get('/walk-in/slots', [WalkInController::class, 'slots'])->name('walkin.slots');
    Route::post('/walk-in', [WalkInController::class, 'store'])->name('walkin.store');

    // Barber commission
    Route::get('/commission', [CommissionController::class, 'index'])->name('commission.index');
    Route::put('/commission/rate', [CommissionController::class, 'updateRate'])->name('commission.rate');
});
