<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;

// Seeders are meant to seed dummy data into your DB for testing. However I used them to seed real data
// You can look at all the seeder classes called by this to look at what data I seed
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call([
            UserSeeder::class,
            TransactorTypeSeeder::class,
            TransactionTypeSeeder::class,
            TransactionAssetTypeSeeder::class
        ]);
    }
}
