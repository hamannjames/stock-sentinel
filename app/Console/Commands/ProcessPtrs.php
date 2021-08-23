<?php

/*
    This is command I wrote initially to massage data from the efD database. I no longer use it, it should be deleted
*/
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Helpers\EfdConnector;

class ProcessPtrs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ProcessPtrs {start} {end}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve and process periodic transaction reports from the eFD application by date.';

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
        $efd = new EfdConnector();

        $efd->ptrIndex($this->argument('start'), $this->argument('end'));

        echo curl_getinfo($efd->session, \CURLINFO_RESPONSE_CODE);
    }
}
