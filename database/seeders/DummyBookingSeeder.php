<?php

namespace Database\Seeders;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\Service;
use App\Support\SlotFinder;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * DEMO DATA - turns the site into a generic example booking system.
 *  - Barbers become "Barber A", "Barber B", "Barber C" (no personal details)
 *  - Random bookings from 1 Nov to 15 Nov 2026, following the real booking rules
 *
 * Run:    php artisan db:seed --class=DummyBookingSeeder
 * Remove: php artisan dummy:clear
 */
class DummyBookingSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(2026);
        srand(2026);

        // ---------- 1. Generic barbers ----------
        $wanted  = ['Barber A', 'Barber B', 'Barber C'];
        $existing = Barber::orderBy('id')->get();

        foreach ($wanted as $i => $name) {
            $barber = $existing[$i] ?? new Barber();
            $barber->fill(['name' => $name, 'specialty' => null, 'phone' => null, 'is_active' => true])->save();
        }
        // Any extra barbers are hidden (not deleted, so old bookings stay correct)
        Barber::whereNotIn('name', $wanted)->update(['is_active' => false]);

        // ---------- 2. Fresh demo bookings ----------
        Booking::where('booking_code', 'like', 'DM-%')->delete();

        $first = ['Ahmad', 'Muhammad', 'Aiman', 'Danial', 'Syafiq', 'Irfan', 'Hafiz', 'Amirul', 'Luqman', 'Zikri',
                  'Nabil', 'Firdaus', 'Adam', 'Harith', 'Khairul', 'Izzat', 'Afiq', 'Rizal', 'Arif', 'Hakim',
                  'Shahrul', 'Azri', 'Imran', 'Taufiq', 'Haikal', 'Aidil', 'Faris', 'Akmal', 'Asyraf', 'Ammar'];
        $last  = ['Faiz', 'Haziq', 'Hakimi', 'Iskandar', 'Rahman', 'Zulkifli', 'Azman', 'Hadi', 'Ismail', 'Asyraf',
                  'Kamal', 'Mikail', 'Danish', 'Anwar', 'Hazman', 'Naim', 'Shah', 'Budiman', 'Roslan', 'Nizam',
                  'Yusof', 'Hidayat', 'Rusli', 'Fitri', 'Iqbal', 'Hafizi', 'Ridzuan', 'Zahin', 'Idris', 'Osman'];

        $services = Service::where('is_active', true)->get();
        if ($services->isEmpty()) {
            $this->command->error('Add at least one active service first.');
            return;
        }

        $finder = new SlotFinder();
        $total  = 0;

        for ($day = Carbon::create(2026, 11, 1); $day->lte(Carbon::create(2026, 11, 15)); $day->addDay()) {
            $date   = $day->toDateString();
            $target = mt_rand(8, 14); // bookings wanted this day

            for ($n = 0, $tries = 0; $n < $target && $tries < 60; $tries++) {
                $service = $services->random();

                $free = array_values(array_filter($finder->daySlots($service, $date), fn ($s) => $s['available']));
                if (empty($free)) {
                    break; // closed day or full
                }
                $start = $free[array_rand($free)]['value'];

                $barberIds = $finder->freeBarberIds($service, $date, $start);
                if (empty($barberIds)) {
                    continue;
                }

                // Status mix: 35% pending, 35% confirmed, 20% completed, 10% cancelled
                $r = mt_rand(1, 100);
                $status = $r <= 35 ? 'pending' : ($r <= 70 ? 'confirmed' : ($r <= 90 ? 'completed' : 'cancelled'));

                $total++;
                Booking::create([
                    'booking_code'   => 'DM-' . str_pad($total, 4, '0', STR_PAD_LEFT),
                    'customer_name'  => $first[array_rand($first)] . ' ' . $last[array_rand($last)],
                    'customer_phone' => '01' . mt_rand(0, 9) . str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT),
                    'service_id'     => $service->id,
                    'barber_id'      => $barberIds[array_rand($barberIds)],
                    'booking_date'   => $date,
                    'booking_time'   => $start,
                    'end_time'       => Carbon::createFromFormat('H:i', $start)->addMinutes($service->duration_minutes)->format('H:i'),
                    'total_price'    => $service->price,
                    'deposit_amount' => config('app.booking_deposit'),
                    'status'         => $status,
                    'payment_status' => 'unpaid',
                ]);
                $n++;
            }
        }

        $this->command->info("Barbers renamed to Barber A, B, C. Created {$total} demo bookings (1-15 Nov 2026).");
    }
}
