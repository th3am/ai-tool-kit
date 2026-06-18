<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin already exists to prevent duplicate entry error
        if (User::where('role', 'admin')->exists()) {
            return;
        }

        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@admin.com',
            'whatsapp_number' => 'admin', // Placeholder, verify if strictly numeric/phone validation exists in DB (it's string)
            'password' => Hash::make('admin123'), // DO NOT use default in production, but okay for dev
            'is_verified' => true,
            'role' => 'admin',
            'country_code' => 'EG',
            'country_name' => 'Egypt',
        ]);
        
        $this->command->info('Default Admin user created successfully.');
    }
}
