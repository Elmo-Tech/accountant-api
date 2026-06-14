<?php

namespace App\Services\Task;

use App\Enums\Task\TaskStatus;
use App\Enums\Task\TaskTimeLogStatus;
use App\Enums\Task\TaskTimeLogType;
use App\Filters\TaskTimeLog\FilterTaskTimeLog;
use App\Models\Task\Task;
use App\Models\Task\TaskTimeLog;
use Carbon\Carbon;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TaskTimeLogService{

    public function allTaskTimeLogs(array $filters){

        $taskTimeLogs = QueryBuilder::for(TaskTimeLog::class)
        ->allowedFilters([
            //AllowedFilter::custom('search', new FilterTaskTimeLog()), // Add a custom search filter
        ])
        ->where('task_id', $filters['taskId'])
        ->get();
        return $taskTimeLogs;

    }

    public function createTaskTimeLog(array $taskTimeLogData){

        $task = Task::find($taskTimeLogData['taskId']);
        /*if($task->timeLogs()->count() > 0) {
            $latestTaskTimeLog = $task->timeLogs()->latest()->first();
            if($latestTaskTimeLog->type == TaskTimeLogType::TIME_LOG->value && $latestTaskTimeLog->status == TaskTimeLogStatus::START->value) {
                $totalTime = $taskTimeLogData['startAt']->diffInMinutes($latestTaskTimeLog->start_at);
                $latestTaskTimeLog->update([
                    'status' => TaskTimeLogStatus::PAUSE->value,
                    'end_at' => $taskTimeLogData['startAt'],
                    'total_time' => $totalTime
                ]);
            }
        }*/

        $taskTimeLog = TaskTimeLog::create([
            'start_at' => $taskTimeLogData['startAt']??null,
            'end_at' => $taskTimeLogData['endAt']??null,
            'type' => TaskTimeLogType::from($taskTimeLogData['type'])->value,
            'comment' => $taskTimeLogData['comment']??null,
            'task_id' => $taskTimeLogData['taskId'],
            'user_id' => $taskTimeLogData['userId'],
            'status' => TaskTimeLogStatus::from($taskTimeLogData['status'])->value,
            'total_time' => $taskTimeLogData['currentTime']??"00:00:00"
        ]);

        if($task->timeLogs()->count() == 1) {
            $task->update([
                'status' => TaskStatus::IN_PROGRESS->value
            ]);
        }

        if($taskTimeLogData['status'] == TaskTimeLogStatus::STOP->value) {
            $task->status = TaskStatus::DONE->value;
            $task->save();
        }

        return $taskTimeLog;

    }

    public function editTaskTimeLog(string $taskTimeLogId){
        $taskTimeLog = TaskTimeLog::find($taskTimeLogId);

        return $taskTimeLog;

    }

    /*public function updateTaskTimeLog(array $taskTimeLogData){

        $taskTimeLog = TaskTimeLog::find($taskTimeLogData['taskTimeLogId']);

        $totalTime = 0;

        if(isset($taskTimeLogData['endAt'])) {
            $totalTime = Carbon::parse($taskTimeLogData['endAt'])->diffInMinutes($taskTimeLog->start_at);
        }

        $startDate = $taskTimeLog->start_at;

        $status = TaskTimeLogStatus::from($taskTimeLogData['status'])->value;

        $closdAt = $taskTimeLogData['endAt']??null;
        if($taskTimeLog->status == TaskTimeLogStatus::PAUSE && $taskTimeLogData['status'] == TaskTimeLogStatus::STOP->value) {
            $status = $taskTimeLog->status;
            $taskTimeLogData['endAt'] = $taskTimeLog->end_at;
        }

        $taskTimeLog->start_at = $taskTimeLogData['startAt'];
        $taskTimeLog->end_at = $taskTimeLogData['endAt']??null;
        $taskTimeLog->type = TaskTimeLogType::from($taskTimeLogData['type'])->value;
        $taskTimeLog->comment = $taskTimeLogData['comment']??null;
        $taskTimeLog->task_id = $taskTimeLogData['taskId'];
        $taskTimeLog->user_id = $taskTimeLogData['userId'];
        $taskTimeLog->status = $status;
        $taskTimeLog->total_time = $totalTime;

        $taskTimeLog->save();

        if($taskTimeLogData['status'] == TaskTimeLogStatus::STOP->value) {
            $task = Task::find($taskTimeLog->task_id);
            $task->status = TaskStatus::DONE->value;
            $task->save();
        }

        return $taskTimeLog;

    }*/

    public function deleteTaskTimeLog(string $taskTimeLogId){
        $taskTimeLog = TaskTimeLog::find($taskTimeLogId);
        $taskTimeLog->delete();
    }

}
