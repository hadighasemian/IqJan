<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test user for the bot system
        User::create([
            'external_id' => '123456789',
            'provider' => 'telegram',
            'username' => 'test_user',
            'first_name' => 'Test',
            'last_name' => 'User',
            'language_code' => 'en',
            'is_bot' => false,
            'is_group' => false,
        ]);

        // Seed AI services, models, and API keys
        $this->call([
            AiServiceSeeder::class,
        ]);
    }
}
