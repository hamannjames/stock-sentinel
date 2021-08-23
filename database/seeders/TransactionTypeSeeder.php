<?php

namespace Database\Seeders;

use App\Models\TransactionType;
use Illuminate\Database\Seeder;

class TransactionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TransactionType::factory()->create(['name' => 'purchase']);
        TransactionType::factory()->create(['name' => 'exchange']);
        TransactionType::factory()->create(['name' => 'sale (partial)']);
        TransactionType::factory()->create(['name' => 'sale (full)']);
    }
}
