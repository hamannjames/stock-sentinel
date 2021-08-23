<?php 

namespace App\Http\Helpers\Processors;

use App\Http\Helpers\Processors\Paginatable;
use App\Http\Helpers\Connectors\ApiConnector;

// This implementation of a data processor is specific to apis
abstract class ApiDataProcessor implements DataProcessor
{
    // The paginatable trait ensures api data can be handled
    use Paginatable;
    
    public $connector;

    // an api data processor must have a connector
    public function __construct(ApiConnector $connector)
    {
        $this->connector = $connector;
    }

    abstract public function processDataTable(Iterable $table);
    abstract public function processDataRow(Iterable $row);
}