<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\OrderStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AssetSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
