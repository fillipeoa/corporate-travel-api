<?php

namespace Database\Seeders;

use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Database\Seeder;

class TravelOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
        ]);

        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        TravelOrder::factory()->count(3)->for($user)->create();
        TravelOrder::factory()->approved()->count(2)->for($user)->create();
        TravelOrder::factory()->cancelled()->for($user)->create();

        TravelOrder::factory()->count(2)->for($admin)->create();
    }
}
