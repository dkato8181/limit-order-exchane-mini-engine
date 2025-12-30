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
        $jsonPath = base_path('seed_data.json');
        if (!file_exists($jsonPath)) {
            throw new \RuntimeException("Seed data file not found: {$jsonPath}");
        }
        $data = json_decode(file_get_contents($jsonPath), true);
        $assets = $data['assets'] ?? [];

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
