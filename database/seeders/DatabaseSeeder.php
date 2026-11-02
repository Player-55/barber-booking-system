<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\Barber;
use App\Models\User;
use App\Models\OpeningHour;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with initial Barbers and Services.
     */
    public function run(): void
    {
        // 0. Shop owner login account (email & password come from .env)
        $admin = config('app.admin');
        if (empty($admin['email']) || empty($admin['password'])) {
            throw new \RuntimeException('Set ADMIN_EMAIL and ADMIN_PASSWORD in .env before seeding.');
        }
        User::updateOrCreate(
            ['email' => $admin['email']],
            ['name' => $admin['name'], 'password' => $admin['password']] // auto-hashed by the User model
        );

        // 0b. Default opening hours (only if not set yet - the owner edits them in the admin)
        //     Monday closed; Tue-Sun 10am-10pm with a 7pm-8pm break
        foreach (range(0, 6) as $day) {
            OpeningHour::firstOrCreate(['day_of_week' => $day], [
                'is_closed'   => $day === 1,
                'open_time'   => '10:00',
                'close_time'  => '22:00',
                'break_start' => '19:00',
                'break_end'   => '20:00',
            ]);
        }

        // 1. Seed Master Barbers
        $barbers = [
            ['name' => 'Barber A', 'is_active' => true],
            ['name' => 'Barber B', 'is_active' => true],
            ['name' => 'Barber C', 'is_active' => true],
        ];

        foreach ($barbers as $barber) {
            Barber::firstOrCreate(['name' => $barber['name']], $barber); // only adds if missing, never overwrites owner's edits
        }

        // 2. Seed Services Offered
        $services = [
            [
                'name' => 'Gentlemen Haircut',
                'price' => 25.00,
                'duration_minutes' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Hot Towel Beard Shave',
                'price' => 15.00,
                'duration_minutes' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Signature Combo (Cut + Beard Trim)',
                'price' => 35.00,
                'duration_minutes' => 45,
                'is_active' => true,
            ],
            [
                'name' => 'Kids Haircut (Under 12)',
                'price' => 20.00,
                'duration_minutes' => 25,
                'is_active' => true,
            ],
            [
                'name' => 'Hair Wash & Styling',
                'price' => 12.00,
                'duration_minutes' => 15,
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(['name' => $service['name']], $service); // only adds if missing, never overwrites owner's edits
        }
    }
}