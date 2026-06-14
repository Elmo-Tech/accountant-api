<?php

namespace App\Services\Task;

use App\Models\Task\Task;
use App\Enums\Task\TaskStatus;
use App\Enums\Task\TaskTimeLogStatus;
use App\Enums\Task\TaskTimeLogType;
use App\Filters\Task\FilterTask;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Filters\Task\FilterTaskDateBetween;
use App\Filters\Task\FilterTaskStartEndDate;
use App\Models\Client\ClientServiceDiscount;
use App\Models\ServiceCategory\ServiceCategory;
use App\Models\Task\TaskTimeLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExportTaskService{

    public function allTasks()
{
    $filters = request()->input('filter', []);
    $startDate = $filters['startDate'] ?? null;
    $endDate = $filters['endDate'] ?? null;

    // Build Query with Filtering
    $query = QueryBuilder::for(Task::class)
        ->allowedFilters([
            AllowedFilter::custom('search', new FilterTask()),
            AllowedFilter::exact('userId', 'user_id'),
            AllowedFilter::exact('status', 'status'),
            AllowedFilter::exact('serviceCategoryId', 'service_category_id'),
            AllowedFilter::exact('clientId', 'client_id'),
        ])
        ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
        ->when($endDate && !$startDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
        ->when($startDate && !$endDate, fn($q) => $q->whereDate('created_at', '=', $startDate))
        ->where('is_new', 1)
        ->orderByDesc('id');

    // Get Paginated Data
    $tasks = $query->get();
    $taskIds = $tasks->pluck('id');

    // Fetch Latest Logs for Each Task
    $latestLogs = DB::table('task_time_logs as ttl')
        ->join(
            DB::raw('(SELECT task_id, MAX(created_at) as latest FROM task_time_logs GROUP BY task_id) as latest_logs'),
            fn($join) => $join->on('ttl.task_id', '=', 'latest_logs.task_id')
                              ->on('ttl.created_at', '=', 'latest_logs.latest')
        )
        ->whereIn('ttl.task_id', $taskIds)
        ->get();

    // Compute Total Time
    $sumTotalTime = 0;
    foreach ($latestLogs as $log) {
        // Only calculate elapsed time for active tasks
        $elapsedTime = 0;
        if ($log->status == 0) { // Task is active
            $elapsedTime = Carbon::now()->diffInSeconds(Carbon::parse($log->created_at));
        }

        if ($log->status == 0) { // Task is active
            if ($log->total_time === '00:00:00') {
                $sumTotalTime += $elapsedTime;
            } else {
                // Convert time to seconds manually to support hours > 24
                $timeParts = explode(':', $log->total_time);
                $timeSeconds = ($timeParts[0] * 3600) + ($timeParts[1] * 60) + $timeParts[2];
                $sumTotalTime += $elapsedTime + $timeSeconds;
            }
        } else {
            // Convert time to seconds manually to support hours > 24
            $timeParts = explode(':', $log->total_time);
            $timeSeconds = ($timeParts[0] * 3600) + ($timeParts[1] * 60) + $timeParts[2];
            $sumTotalTime += $timeSeconds;
        }
    }

    // Convert to "H:i:s" format with total hours continuing beyond 24
    $formattedTotalTime = sprintf('%d:%02d:%02d', floor($sumTotalTime / 3600), ($sumTotalTime % 3600) / 60, $sumTotalTime % 60);

    return [
        'tasks' => $tasks,
        'totalTime' => $formattedTotalTime,
        'total' => $tasks->count()
    ];
}


    public function createTask(array $taskData){
        $task = Task::create([
            'title' => $taskData['title']??"",
            'description' => $taskData['description']??"",
            'client_id' => $taskData['clientId'],
            'user_id' => $taskData['userId'],
            'service_category_id' => $taskData['serviceCategoryId'],
            'invoice_id' => $taskData['invoiceId']??null,
            'status' => TaskStatus::from($taskData['status'])->value,
            'connection_type_id' => $taskData['connectionTypeId']??null,
            'start_date' => $taskData['startDate']??null,
            'end_date' => $taskData['endDate']??null,
        ]);

        return $task;

    }

    public function editTask(string $taskId){
        $task = Task::with('timeLogs')->find($taskId);
        // $startTask= $task->timeLogs->start_at ;
        // $endTask=$task->timeLogs->end_at;
        return $task;

    }

    public function updateTask(array $taskData){

        $task = Task::find($taskData['taskId']);

        /*if($task->status == TaskStatus::DONE) {
            return response()->json([
                'message' => 'Task is already done',
            ], 401);
        }*/

        // $task->fill([
        //     'title' => $taskData['title']??"",
        //     'description' => $taskData['description']??"",
        //     'client_id' => $taskData['clientId'],
        //     'user_id' => $taskData['userId'],
        //     'service_category_id' => $taskData['serviceCategoryId'],
        //     'invoice_id' => $taskData['invoiceId']??null,
        //     'status' => TaskStatus::from($taskData['status'])->value,
        //     'connection_type_id' => $taskData['connectionTypeId']??null,
        //     'start_date' => $taskData['startDate']??null,
        //     'end_date' => $taskData['endDate']??null
        // ]);

        $task->title = $taskData['title']??"";
        $task->description = $taskData['description']??"";
        $task->client_id = $taskData['clientId'];
        $task->user_id = $taskData['userId'];
        $task->service_category_id = $taskData['serviceCategoryId'];
        $task->invoice_id = $taskData['invoiceId']??null;
        $task->connection_type_id = $taskData['connectionTypeId']??null;
        $task->start_date = $taskData['startDate']??null;
        $task->end_date = $taskData['endDate']??null;

        if($task->status != TaskStatus::DONE){
            $task->status = TaskStatus::from($taskData['status'])->value;
        }else if($task->status == TaskStatus::DONE && $taskData['status'] != TaskStatus::DONE->value){
            return response()->json([
                'message' => 'Task is already done',
            ], 401);
        }

        $task->save();

        return $task;

    }

    public function deleteTask(string $taskId){
        $task = Task::find($taskId);
        $task->delete();
    }
    public function changeStatus(string $taskId, int $status){
        $task = Task::find($taskId);
        $task->update([
            'status' => TaskStatus::from($status)->value
        ]);
    }

}
