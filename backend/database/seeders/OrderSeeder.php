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
        $orders = [
            [
                [
                    'symbol' => 'BTC',
                    'side' => 'buy',
                    'amount' => 0.5,
                    'price' => 30000.0,
                    'status' => OrderStatus::OPEN,
                ],
                [
                    'symbol' => 'ETH',
                    'side' => 'sell',
                    'amount' => 2.0,
                    'price' => 2000.0,
                    'status' => OrderStatus::OPEN,
                ]
                ],
                [
                    [
                        'symbol' => 'MTH',
                        'side' => 'buy',
                        'amount' => 1.5,
                        'price' => 10.0,
                        'status' => OrderStatus::OPEN,
                    ],
                    [
                        'symbol' => 'LTC',
                        'side' => 'sell',
                        'amount' => 5.0,
                        'price' => 150.0,
                        'status' => OrderStatus::OPEN,
                    ]
                ],
                [
                    [
                        'symbol' => 'XRP',
                        'side' => 'buy',
                        'amount' => 50.0,
                        'price' => 0.5,
                        'status' => OrderStatus::OPEN,
                    ],
                    [
                        'symbol' => 'BTC',
                        'side' => 'sell',
                        'amount' => 0.2,
                        'price' => 32000.0,
                        'status' => OrderStatus::OPEN,
                    ]
                ]
        ];

        $users = User::take(3)->get();

        foreach ($users as $index => $user) {
            $userOrder = collect($orders[$index]);
            $orderData = $userOrder->map(function ($order) use ($user) {
                $order['user_id'] = $user->id;
                $order['created_at'] = Carbon::now();
                $order['updated_at'] = Carbon::now();
                return $order;
            });
            DB::table('orders')->insert($orderData->toArray());
        }
    }
}
