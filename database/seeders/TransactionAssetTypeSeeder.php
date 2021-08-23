<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TransactionAssetType;

class TransactionAssetTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TransactionAssetType::factory()->create(['name' => 'stock']);
        TransactionAssetType::factory()->create(['name' => 'stock option']);
    }
}
