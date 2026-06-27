<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'id' => 1,
            'email' => 'john.doe@example.com',
            'password' => bcrypt('password'), // default password, should be changed in production
            'email_verified_at' => now(),
            'type' => 'company',
            'is_active' => true,
        ]);
    }
}
