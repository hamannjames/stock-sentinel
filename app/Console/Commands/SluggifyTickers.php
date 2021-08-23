<?php

namespace App\Console\Commands;

use App\Models\Ticker;
use Illuminate\Console\Command;

// this simple command serves one purpose. re-save all the current tickers to give them a slug
class SluggifyTickers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slug:tickers';

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
        Ticker::all()->each(function($ticker){
            $ticker->save();
        });
    }
}
