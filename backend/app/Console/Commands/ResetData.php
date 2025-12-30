<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Schema::disableForeignKeyConstraints();

        try {
            DB::table('trades')->truncate();
            DB::table('orders')->truncate();
            DB::table('assets')->truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->call('db:seed', ['--class' => 'Database\\Seeders\\AssetSeeder']);
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\OrderSeeder']);

        $jsonPath = base_path('seed_data.json');

        if (!file_exists($jsonPath)) {
            throw new \RuntimeException("Seed data file not found: {$jsonPath}");
        }

        $data = json_decode(file_get_contents($jsonPath), true);

        if (!is_array($data) || !isset($data['users'])) {
            throw new \RuntimeException("Invalid seed data in {$jsonPath}");
        }

        $users = $data['users'];

        $this->info('Updating user balances...');

        foreach (array_slice($users, 0, 3) as $userData) {
            $user = User::where('email', $userData['email'])->first();
            if ($user) {
                $user->balance = $userData['balance'];
                $user->save();
            }
        }

    }
}
