<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Sales Agent',
            'email' => 'agent@capbayauto.com',
            'password' => bcrypt('password'),
        ]);
    }
}
