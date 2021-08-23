<?php

namespace App\Http\Helpers\Processors;

// this trait simply adds another layer on top of processing tables of data. It yields each
// table of data as it is processed. it assumes the iterable is paginated, but does not assume how.
// For efd connector, this would be a generator.
trait Paginatable {
    function processPaginatedData(Iterable $data)
    {
        foreach ($data as $page) {
            yield $this->processDataTable($page);
        }
    }
}