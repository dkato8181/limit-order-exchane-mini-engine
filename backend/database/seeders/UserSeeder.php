<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
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
        if (!is_array($data) || !isset($data['users'])) {
            throw new \RuntimeException("Invalid seed data in {$jsonPath}");
        }
        $users = $data['users'];

        $userData = collect($users)->map(function ($user) {
            $user['password'] = bcrypt($user['password']);
            $user['created_at'] = now();
            $user['updated_at'] = now();
            return $user;
        });

        DB::table('users')->insert($userData->toArray());
    }
}
