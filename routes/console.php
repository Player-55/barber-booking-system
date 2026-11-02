<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Helper commands (short, Windows-friendly)
|   php artisan dummy:clear              -> remove all test bookings (codes DM-xxxx)
|   php artisan admin:remove some@email  -> remove an old admin login account
*/
Artisan::command('dummy:clear', function () {
    $count = \App\Models\Booking::where('booking_code', 'like', 'DM-%')->delete();
    $this->info("Removed {$count} dummy booking(s).");
})->purpose('Remove all dummy test bookings');

Artisan::command('admin:remove {email}', function (string $email) {
    if ($email === config('app.admin.email')) {
        $this->error('That is the current ADMIN_EMAIL in .env - not removing it.');
        return;
    }
    $count = \App\Models\User::where('email', $email)->delete();
    $this->info($count ? "Removed account {$email}." : "No account found for {$email}.");
})->purpose('Remove an old admin login account');
