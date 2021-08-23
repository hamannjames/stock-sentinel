<?php

namespace App\Http\Helpers\Processors;

// This interface states that a data processor should at least process tables and rows of data
interface DataProcessor
{
    public function processDataTable(Iterable $table);
    public function processDataRow(Iterable $row);
}