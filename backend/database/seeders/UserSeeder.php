<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
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

        $userData = collect($users)->map(function ($user) {
            $user['created_at'] = now();
            $user['updated_at'] = now();
            return $user;
        });

        DB::table('users')->insert($userData->toArray());
    }
}
