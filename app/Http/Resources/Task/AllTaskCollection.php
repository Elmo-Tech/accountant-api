<?php

namespace App\Http\Resources\Task;

use App\Models\Task\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\ResourceCollection;

// class AllTaskCollection extends ResourceCollection
// {
//     /**
//      * Transform the resource into an array.
//      *
//      * @return array<string, mixed>
//      */

//     private $pagination;
//     private $totalTime; // Store total time

//     public function __construct($resource, $totalTime)
//     {
//         $this->pagination = [
//             'total' => Task::count(), //$resource->total(),
//             'count' => $resource->count(),
//             'per_page' => $resource->perPage(),
//             'current_page' => $resource->currentPage(),
//             'total_pages' => $resource->lastPage()
//         ];

//         $this->totalTime = $totalTime; // Store total time for response
//         $resource = $resource->getCollection();

//         parent::__construct($resource);
//     }

//     public function toArray(Request $request): array
//     {
//         return [
//             "result" => [
//                 'tasks' => AllTaskResource::collection(($this->collection)->values()->all()),
//                 "totalHours" => $this->totalTime // Include total time in response
//             ],
//             'pagination' => $this->pagination
//         ];
//     }
// }

class AllTaskCollection extends ResourceCollection
{
    private $extraData;

    public function __construct($resource, $extraData = [])
    {
        $this->extraData = $extraData;
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            "result" => [
                'tasks' => AllTaskResource::collection($this->collection),
                "totalHours" => $this->extraData['totalHours'] ?? "0:00"
            ],
            'pagination' => [
                'total' => $this->resource->total(),
                'count' => $this->resource->count(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'total_pages' => $this->resource->lastPage(),
            ]
        ];
    }
}

