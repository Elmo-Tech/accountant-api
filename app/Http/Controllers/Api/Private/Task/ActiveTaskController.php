<?php

namespace App\Http\Controllers\Api\Private\Task;

use App\Enums\Task\TaskStatus;
use App\Enums\Task\TaskTimeLogType;
use App\Http\Controllers\Controller;
use App\Models\Task\Task;
use App\Models\Task\TaskTimeLog;
use App\Utils\PaginateCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ActiveTaskController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:all_active_tasks', ['only' => ['index']]);
        $this->middleware('permission:update_active_task', ['only' => ['create']]);
        // $this->middleware('permission:edit_task', ['only' => ['edit']]);
        // $this->middleware('permission:update_task', ['only' => ['update']]);
        // $this->middleware('permission:delete_task', ['only' => ['delete']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();
        $tasks = DB::table('tasks')
        ->join('clients', 'tasks.client_id', '=', 'clients.id')
        ->where('tasks.user_id', $user->id)
        ->whereIn('tasks.status', [
            TaskStatus::TO_WORK->value,
            TaskStatus::IN_PROGRESS->value
        ])
        ->select([
            'tasks.id as taskId',
            'tasks.title',
            'tasks.status',
            'clients.id as clientId',
            'clients.ragione_sociale as clientName',
        ])
        ->whereNull('tasks.deleted_at')
        ->get();

        foreach ($tasks as $index => $task) {
            if($task->status == TaskStatus::TO_WORK->value) {
                $tasks[$index]->totalTime = 0;
                $tasks[$index]->time = '00:00:00';
                $tasks[$index]->timerStatus = 0;
            }

            if ($task->status == TaskStatus::IN_PROGRESS->value) {
                $taskTimeLogs = DB::table('task_time_logs')
                    ->where('task_id', $task->taskId)
                    ->select([
                        'task_time_logs.id as taskTimeLogId',
                        'task_time_logs.start_at as startAt',
                        'task_time_logs.end_at as endAt',
                        DB::raw('TIME_TO_SEC(TIMEDIFF(IFNULL(end_at, NOW()), start_at)) as durationInSeconds')
                    ])
                    ->where('type', TaskTimeLogType::TIME_LOG->value)
                    ->get();


                // Calculate total time in seconds
                $totalSeconds = $taskTimeLogs->sum('durationInSeconds');

                // Format total seconds into H:i:s
                $hours = floor($totalSeconds / 3600);
                $minutes = floor(($totalSeconds % 3600) / 60);
                $seconds = $totalSeconds % 60;

                $tasks[$index]->totalTime = $totalSeconds;
                $tasks[$index]->time = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                if($taskTimeLogs->last()->endAt == null) {
                    $tasks[$index]->timerStatus = 1;
                }

                if($taskTimeLogs->last()->endAt != null) {
                    $tasks[$index]->timerStatus = 2;
                }

                $tasks[$index]->timeLogId = $taskTimeLogs->last()->taskTimeLogId;
            }

        }
        return response()->json(
            $tasks
        );
    }

    // /**
    //  * Show the form for creating a new resource.
    //  */

    // public function create(CreateTaskRequest $createTaskRequest)
    // {

    //     try {
    //         DB::beginTransaction();

    //         $this->taskService->createTask($createTaskRequest->validated());

    //         DB::commit();

    //         return response()->json([
    //             'message' => __('messages.success.created')
    //         ], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }


    // }

    // /**
    //  * Show the form for editing the specified resource.
    //  */

    // public function edit(Request $request)
    // {
    //     $task  =  $this->taskService->editTask($request->taskId);

    //     return new TaskResource($task);


    // }

    // /**
    //  * Update the specified resource in storage.
    //  */
    public function update(Request $request)
    {

        try {

            DB::beginTransaction();
            $taskTimeLog = TaskTimeLog::find($request->taskTimeLogId);
            $taskTimeLog->end_at = $request->endAt;
            $taskTimeLog->save();

            if($request->taskStatus == TaskStatus::DONE->value) {
                $task = Task::find($taskTimeLog->task_id);
                $task->status = TaskStatus::DONE->value;
                $task->save();
            }

            DB::commit();
            return response()->json([
                 'message' => __('messages.success.updated')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }

    // /**
    //  * Remove the specified resource from storage.
    //  */
    // public function delete(Request $request)
    // {

    //     try {
    //         DB::beginTransaction();
    //         $this->taskService->deleteTask($request->taskId);
    //         DB::commit();
    //         return response()->json([
    //             'message' => __('messages.success.deleted')
    //         ], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }


    // }

}
