<?php

namespace Database\Seeders;

use App\Models\User;
use App\OrderStatus;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(OrderService $orderService): void
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
                return $order;
            });
            foreach ($orderData as $data) {
                try {
                    $orderService->canPlaceOrder($data);
                    $orderService->createOrder($data, false);
                } catch (\Exception $e) {
                    Log::error('Cannot place order for user '.$user->id.': '.$e->getMessage());
                    continue;
                }
            }
        }
    }
}
