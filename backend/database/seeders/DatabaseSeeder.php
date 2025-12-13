<?php

namespace Database\Seeders;

use App\Models\User;
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

        foreach ($data as $index => $userData) {
            $user = User::factory()->create($userData);
            foreach ($assets[$index] as $assetData) {
                $user->assets()->create($assetData);
            }
        }
    }
}
