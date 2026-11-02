<?php

namespace Database\Seeders;

use App\Models\Barber;
use Illuminate\Database\Seeder;

/**
 * Switch the barbers back to the real Pizzi Barbershop team.
 * Run: php artisan db:seed --class=PizziShopSeeder
 * (Bookings are kept - renaming a barber also updates his old bookings.)
 */
class PizziShopSeeder extends Seeder
{
    public function run(): void
    {
        $team = [
            'Barber A' => ['name' => 'Fizi', 'specialty' => 'Senior Stylist',  'phone' => '014-5373901'],
            'Barber B' => ['name' => 'Amir', 'specialty' => 'Fade Specialist', 'phone' => null],
        ];

        foreach ($team as $oldName => $data) {
            $barber = Barber::where('name', $oldName)->first()
                ?? Barber::where('name', $data['name'])->first()
                ?? new Barber();
            $barber->fill($data + ['is_active' => true])->save();
        }

        // The shop has 2 barbers - hide "Barber C" (kept for old demo bookings)
        Barber::where('name', 'Barber C')->update(['is_active' => false]);

        $this->command->info('Barbers restored: Fizi & Amir. Barber C hidden. Add Amir\'s phone on the admin Barbers page.');
    }
}
