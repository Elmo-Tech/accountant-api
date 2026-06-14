<?php

namespace App\Exports;

use App\Services\Task\TaskService;
use Maatwebsite\Excel\Concerns\FromCollection;

class TasksExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $tasksCollection;


    public function __construct($tasksCollection)
    {
        $this->tasksCollection = $tasksCollection;
    }


    public function collection()
    {
        return $this->tasksCollection;
    }
}
