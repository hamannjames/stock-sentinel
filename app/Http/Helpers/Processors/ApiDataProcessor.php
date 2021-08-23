<?php 

namespace App\Http\Helpers\Processors;

use App\Http\Helpers\Processors\Paginatable;
use App\Http\Helpers\Connectors\ApiConnector;

abstract class ApiDataProcessor implements DataProcessor
{
    use Paginatable;
    
    public $connector;

    public function __construct(ApiConnector $connector)
    {
        $this->connector = $connector;
    }

    abstract public function processDataTable(Iterable $table);
    abstract public function processDataRow(Iterable $row);
}