<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'AryaXyz',
            'username' => 'admin',
            'email' => 'adminarya@gmail.com',
            'role' => 'admin',
            'password' => bcrypt('admin123'),
        ]);
    }
}
