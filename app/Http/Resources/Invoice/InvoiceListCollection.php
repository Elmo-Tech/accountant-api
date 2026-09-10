<?php

namespace App\Http\Resources\Invoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class InvoiceListCollection extends ResourceCollection
{
    private $pagination;

    public function __construct($resource, private array $totals)
    {
        $this->pagination = [
            'total'        => $resource->total(),
            'count'        => $resource->count(),
            'per_page'     => $resource->perPage(),
            'current_page' => $resource->currentPage(),
            'total_pages'  => $resource->lastPage(),
        ];

        $resource = $resource->getCollection();

        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'result' => [
                'invoices' => $this->collection->values()->all(),
                'totals' => $this->totals,
            ],
            'pagination' => $this->pagination,
        ];
    }
}
