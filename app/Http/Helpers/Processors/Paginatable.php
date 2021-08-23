<?php

namespace App\Http\Helpers\Processors;

trait Paginatable {
    function processPaginatedData(Iterable $data)
    {
        foreach ($data as $page) {
            yield $this->processDataTable($page);
        }
    }
}