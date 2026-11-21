<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach ([
            ['name' => 'Demo User One', 'email' => 'user1@example.com'],
            ['name' => 'Demo User Two', 'email' => 'user2@example.com'],
        ] as $demoUser) {
            User::updateOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => 'Password123!',
                ],
            );
        }
    }
}
