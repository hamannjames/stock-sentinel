<?php

namespace App\Console\Commands;

use App\Models\Transactor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use App\Http\Helpers\Connectors\ProPublicaConnector;
use App\Http\Helpers\Processors\ProPublicaDataProcessor;

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
        $congressRange = range(113, (int)(113 + floor(((int) date('Y') - 2013) / 2)));
        $processor = App::make(ProPublicaDataProcessor::class);
        
        foreach ($congressRange as $congress) {
            $this->info("Fetching congress {$congress}...");
            $data = $processor->connector->index([
                'congress' => $congress,
                'chamber' => 'senate'
            ])->current();

            $processedTransactors = $processor->processDataTable($data)->map(function($transactor) use ($congress){
                $transactor['congress'] = $congress;
                return $transactor;
            });

            $this->line('Inserting congress members');
            Transactor::upsert($processedTransactors->all(), ['pro_publica_id'], ['state', 'gender', 'last_name', 'middle_name', 'party', 'congress', 'in_office']);
        }
    }
}
