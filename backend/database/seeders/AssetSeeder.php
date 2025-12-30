<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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

        $users = User::take(3)->get();
        foreach ($users as $index => $user) {
            $userAsset = collect($assets[$index]);
            $assetData = $userAsset->map(function ($asset) use ($user) {
                $asset['user_id'] = $user->id;
                $asset['created_at'] = Carbon::now();
                $asset['updated_at'] = Carbon::now();
                return $asset;
            });
            DB::table('assets')->insert($assetData->toArray());
        }
    }
}
