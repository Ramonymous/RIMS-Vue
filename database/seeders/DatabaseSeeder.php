<?php

namespace Database\Seeders;

use App\Models\Parts;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Ramonymous',
            'email' => 'me@r-dev.asia',
            'password' => Hash::make('IPkmqb1V'),
            'permissions' => ['admin'],
        ]);

        // Create 10 parts
        Parts::factory(10)->create();
    }
}
