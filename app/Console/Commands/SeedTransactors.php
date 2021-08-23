<?php

namespace App\Console\Commands;

use App\Models\Transactor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use App\Http\Helpers\Connectors\ProPublicaConnector;
use App\Http\Helpers\Processors\ProPublicaDataProcessor;

// This class seeds transactors from the pro publica api. Right now only seeds senators
class SeedTransactors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:transactors';

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
    public function handle(ProPublicaConnector $connector)
    {
        // I chose this arbitary range starting at 113 because that is when good stock data shows
        // up in the efd database. The end of the range depends on the year, since every 2 years we elect
        // a new congress! I add the current year minus 2013, the year conress 113, divided by 2 to 113
        // and take the floor, so that we get the correct number from 113.
        $congressRange = range(113, (int)(113 + floor(((int) date('Y') - 2013) / 2)));
        // instantiate a propublica data processor from the service container
        $processor = App::make(ProPublicaDataProcessor::class);
        
        // for each congress, use the processor's connector to fetch the data. Only retrieve senate data
        foreach ($congressRange as $congress) {
            $this->info("Fetching congress {$congress}...");
            $data = $processor->connector->index([
                'congress' => $congress,
                'chamber' => 'senate'
            ])->current();

            // final massage of data to add the congress number, which isn't returned by propublica
            $processedTransactors = $processor->processDataTable($data)->map(function($transactor) use ($congress){
                $transactor['congress'] = $congress;
                return $transactor;
            });

            // upsert congress members in one batch
            $this->line('Inserting congress members');
            Transactor::upsert($processedTransactors->all(), ['pro_publica_id'], ['state', 'gender', 'last_name', 'middle_name', 'party', 'congress', 'in_office']);
        }
    }
}
