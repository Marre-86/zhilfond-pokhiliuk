<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user from environment variables
        $testUserEmail = env('TEST_USER_EMAIL');
        $testUserPassword = env('TEST_USER_PASSWORD');

        if ($testUserEmail && $testUserPassword) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => $testUserEmail,
                'password' => Hash::make($testUserPassword),
            ]);
            
            $this->command->info("Test user created with email: {$testUserEmail}");
        }
    }
}
