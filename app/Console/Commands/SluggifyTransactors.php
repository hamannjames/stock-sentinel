<?php

namespace App\Console\Commands;

use App\Models\Transactor;
use Illuminate\Console\Command;

// this simple command serves one purpose. Resave all the transactors to give them a slug
class SluggifyTransactors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slug:transactors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Transactor::all()->each(function($transactor){
            $transactor->save();
        });
    }
}
