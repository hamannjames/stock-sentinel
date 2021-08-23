<?php

namespace App\Http\Helpers\Processors;

interface DataProcessor
{
    public function processDataTable(Iterable $table);
    public function processDataRow(Iterable $row);
}