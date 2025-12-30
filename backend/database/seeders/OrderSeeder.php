<?php

namespace Database\Seeders;

use App\Models\User;
use App\OrderStatus;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = base_path('seed_data.json');
        if (!file_exists($jsonPath)) {
            throw new \RuntimeException("Seed data file not found: {$jsonPath}");
        }
        $data = json_decode(file_get_contents($jsonPath), true);
        $orders = $data['orders'] ?? [];

        $users = User::take(3)->get();

        foreach ($users as $index => $user) {
            $userOrder = collect($orders[$index]);
            $orderData = $userOrder->map(function ($order) use ($user) {
                $order['user_id'] = $user->id;
                $order['status'] = OrderStatus::OPEN;
                $order['created_at'] = Carbon::now();
                $order['updated_at'] = Carbon::now();
                return $order;
            });
            DB::table('orders')->insert($orderData->toArray());
        }
    }
}
