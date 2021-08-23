<?php

namespace App\Http\Helpers\Processors;

use App\Models\TransactorType;
use App\Http\Helpers\Processors\Paginatable;
use App\Http\Helpers\Connectors\ProPublicaConnector;

class ProPublicaDataProcessor extends ApiDataProcessor
{
    protected $transactorTypes;
    protected $titleMap = [
        'Sen.' => 'senator'
    ];

    public function __construct(ProPublicaConnector $connector)
    {
        parent::__construct($connector);
        $this->transactorTypes = TransactorType::all();
    }

    public function processDataTable(Iterable $table)
    {
        $transactors = collect($table);

        $processedTransactors = $transactors->map(function($transactor){
            return $this->processDataRow((array) $transactor);
        });

        return $processedTransactors;
    }

    public function processDataRow(Iterable $row)
    {
        return [
            'pro_publica_id' => $row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'middle_name' => $row['middle_name'],
            'party' => $row['party'],
            'gender' => $row['gender'],
            'in_office' => $row['in_office'],
            'state' => $row['state'],
            'transactor_type_id' => $this->transactorTypes->where('name', $this->titleMap[$row['short_title']])->first()->id
        ];
    }
}