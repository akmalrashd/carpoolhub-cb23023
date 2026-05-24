<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleUserSeeder extends Seeder
{
    /**
     * Seed role-based users (admin, driver, passenger).
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Carpool Admin',
                'email' => 'admin@carpoolhub.test',
                'role' => 'admin',
                'phone' => '01110000001',
            ],
            [
                'name' => 'Driver One',
                'email' => 'driver1@carpoolhub.test',
                'role' => 'driver',
                'phone' => '01110000011',
                'vehicle_model' => 'Perodua Bezza',
                'vehicle_plate' => 'WMK 4821',
            ],
            [
                'name' => 'Driver Two',
                'email' => 'driver2@carpoolhub.test',
                'role' => 'driver',
                'phone' => '01110000012',
                'vehicle_model' => 'Proton Saga',
                'vehicle_plate' => 'VHY 2048',
            ],
            [
                'name' => 'Passenger One',
                'email' => 'passenger1@carpoolhub.test',
                'role' => 'passenger',
                'phone' => '01110000021',
            ],
            [
                'name' => 'Passenger Two',
                'email' => 'passenger2@carpoolhub.test',
                'role' => 'passenger',
                'phone' => '01110000022',
            ],
            [
                'name' => 'Passenger Three',
                'email' => 'passenger3@carpoolhub.test',
                'role' => 'passenger',
                'phone' => '01110000023',
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'phone' => $user['phone'],
                    'vehicle_model' => $user['vehicle_model'] ?? null,
                    'vehicle_plate' => $user['vehicle_plate'] ?? null,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}
