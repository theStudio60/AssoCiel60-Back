<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::create([
            'name' => 'Alprail Admin',
            'address' => '1 Rue Admin',
            'zip_code' => '1000',
            'city' => 'Lausanne',
            'phone' => '+41000000000',
            'email' => 'admin@alprail.ch',
        ]);

        User::create([
            'organization_id' => $org->id,
            'first_name' => 'Admin',
            'last_name' => 'Alprail',
            'email' => 'bouysfi.othman@gmail.com',
            'phone' => '+212637208455',
            'password' => Hash::make('123456789'),
            'role' => 'admin',
            'two_factor_enabled' => true,
        ]);
    }
}