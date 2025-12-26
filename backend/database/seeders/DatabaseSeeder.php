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
        $data = [
            [
                'name' => 'Alice Naki',
                'email' => 'alice@gmail.com',
                'password' => bcrypt('password'),
                'balance' => 10000,
            ],
            [
                'name' => 'David Kato',
                'email' => 'david@gmail.com',
                'password' => bcrypt('password'),
                'balance' => 15000,
            ],
            [
                'name' => 'Charlie Stevens',
                'email' => 'charlie@gmail.com',
                'password' => bcrypt('password'),
                'balance' => 5000,
            ]
        ];

        $assets = [
            [
                [
                    'symbol'=>'BTC',
                    'amount'=>2.5,
                ],
                [
                    'symbol'=>'ETH',
                    'amount'=>10,
                ],
                [
                    'symbol'=>'LTC',
                    'amount'=>20,
                ],
                [
                    'symbol'=>'XRP',
                    'amount'=>500,
                ]
            ],
            [
                [
                    'symbol'=>'ATC',
                    'amount'=>1.0,
                ],
                [
                    'symbol'=>'MTH',
                    'amount'=>5,
                ],
                [
                    'symbol'=>'LTC',
                    'amount'=>15,
                ],
                [
                    'symbol'=>'XRP',
                    'amount'=>300,
                ]
            ],
            [
                [
                    'symbol'=>'BTC',
                    'amount'=>0.5,
                ],
                [
                    'symbol'=>'MSH',
                    'amount'=>2,
                ],
                [
                    'symbol'=>'XRP',
                    'amount'=>100,
                ]
            ]
        ];
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

        foreach ($data as $index => $userData) {
            $user = User::factory()->create($userData);
            foreach ($assets[$index] as $assetData) {
                $user->assets()->create($assetData);
            }
            # create orders for user
            foreach ($orders[$index] as $orderData) {
                $orderData['user_id'] = $user->id;
                Order::create($orderData);
            }
        }
    }
}
