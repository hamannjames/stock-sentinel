<?php

// This is the "parent" command I used to seed 7 years worth of reported data from my APIs. 
// this command calls other commands in succession.
namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedDatabaseFromApis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:apiData';

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
        // Print out an initial message
        $this->info('Seeding transactors...');
        // First seed all of my senator data from the pro publica api
        $this->call('seed:transactors');
        // then seed all of my transaction data from the efD website
        $this->info('Seeding ptrs...');
        $this->call('seed:ptrs');
        // let the user know we are done
        $this->info('Finished!');
    }
}
