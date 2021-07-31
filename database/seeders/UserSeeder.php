<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory()
            ->create([
                'name' => 'James Hamann',
                'email' => 'hamannjames87@gmail.com'
            ]);

        User::factory(10)->create();
    }
}
