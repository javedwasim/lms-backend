<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create(); // Comment this line

        $this->call(RoleSeeder::class);

        $admin = \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com'
        ]);

        $admin->assignRole('admin');

        $user = \App\Models\User::factory()->create([
            'name' => 'User',
            'email' => 'user@example.com',
        ]);

        $user->assignRole('user');
    }
}
