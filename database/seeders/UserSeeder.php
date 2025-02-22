<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat 1 user khusus untuk testing
        User::factory()->create([
            'fullname' => 'Test User',
            'email' => 'test@gmail.com',
            'username' => 'testuser',
            'phone' => '08123456789',
            'salary' => 5000000,
            'password' => bcrypt('password'),
        ]);

        // Buat 10 user random
        User::factory(10)->create();
    }
}
