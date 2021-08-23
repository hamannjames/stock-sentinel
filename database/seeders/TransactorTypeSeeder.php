<?php

namespace Database\Seeders;

use App\Models\TransactorType;
use Illuminate\Database\Seeder;

class TransactorTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TransactorType::factory()->create(['name' => 'senator']);
    }
}
