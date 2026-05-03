<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'saadat@kidsstore.com'],
            [
                'name' => 'saadat',
                'email' => 'saadat@kidsstore.com',
                'password' => Hash::make('389235'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'ahmad@kidsstore.com'],
            [
                'name' => 'ahmad',
                'email' => 'ahmad@kidsstore.com',
                'password' => Hash::make('986174'),
                'role' => 'staff',
                'is_active' => true,
            ]
        );

        $this->command->info('Users created successfully!');
        $this->command->info('Admin - Email: saadat@kidsstore.com, Password: 389235');
        $this->command->info('Staff - Email: ahmad@kidsstore.com, Password: 123456');
    }
}
