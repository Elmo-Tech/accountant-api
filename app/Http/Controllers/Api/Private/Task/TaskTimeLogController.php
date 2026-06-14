<?php

namespace App\Http\Controllers\Api\Private\Task;

use App\Enums\Task\TaskStatus;
use App\Enums\Task\TaskTimeLogStatus;
use App\Enums\Task\TaskTimeLogType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\TaskTimeLog\UpdateTaskTimeLogRequest;
use App\Http\Requests\Task\TaskTimeLog\CreateTaskTimeLogRequest;
use App\Http\Resources\Task\TaskTimeLog\AllTaskTimeLogResource;
use App\Http\Resources\Task\TaskTimeLog\TaskTimeLogResource;
use App\Models\Task\Task;
use App\Models\Task\TaskTimeLog;
use App\Services\Task\TaskTimeLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class TaskTimeLogController extends Controller
{
    protected $taskTimeLogService;

    public function __construct(TaskTimeLogService $taskTimeLogService)
    {
        $this->middleware('auth:api');
        $this->middleware('permission:all_task_time_logs', ['only' => ['index']]);
        $this->middleware('permission:create_task_time_log', ['only' => ['create']]);
        $this->middleware('permission:edit_task_time_log', ['only' => ['edit']]);
        $this->middleware('permission:update_task_time_log', ['only' => ['update']]);
        //$this->middleware('permission:delete_task_time_log', ['only' => ['delete']]);
        $this->taskTimeLogService = $taskTimeLogService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allTimeLogs = $this->taskTimeLogService->allTaskTimeLogs($request->all());

        return AllTaskTimeLogResource::collection($allTimeLogs);
    }

    /**
     * Show the form for creating a new resource.
     */

    public function create(CreateTaskTimeLogRequest $createTaskTimeLogRequest)
    {

        try {
            DB::beginTransaction();

            $validatedData = $createTaskTimeLogRequest->validated();

            $taskTimeLog = $this->taskTimeLogService->createTaskTimeLog($validatedData);

            $task = Task::find($validatedData['taskId']);

            $latestPlayedTasks = Task::where('user_id', $validatedData['userId'])
                ->where('status', TaskStatus::IN_PROGRESS->value)
                ->whereNot('id', $task->id)
                ->with('latestTimeLog')
                ->get();

            foreach ($latestPlayedTasks as $latestTask) {
                // Get the latest time log's created_at timestamp
                $latestTimeLog = $latestTask->latestTimeLog;

                if (!$latestTimeLog) {
                    continue;
                }

                if($latestTimeLog->status != TaskTimeLogStatus::START){
                   continue;
                }


                if ($latestTimeLog) {

                    // Current session duration in seconds
                    $currentSeconds = Carbon::now()->diffInSeconds($latestTimeLog->created_at);

                    // Previous stored time
                    $previousTime = $latestTimeLog->total_time;

                    // If no previous time exists
                    if (empty($previousTime) || $previousTime === '00:00:00' || $previousTime == 0) {

                        $totalSeconds = $currentSeconds;

                    } else {

                        // Convert previous time to seconds manually to support hours > 24
                        $timeParts = explode(':', $previousTime);
                        $previousSeconds = ($timeParts[0] * 3600) + ($timeParts[1] * 60) + $timeParts[2];

                        // Add them together
                        $totalSeconds = $previousSeconds + $currentSeconds;
                    }

                    // Final format - support more than 24 hours
                    $hours = floor($totalSeconds / 3600);
                    $minutes = floor(($totalSeconds / 60) % 60);
                    $seconds = $totalSeconds % 60;
                    $totalTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

                } else {
                    $totalTime = '00:00:00';
                }
                // Create the new TaskTimeLog record
                TaskTimeLog::create([
                    'start_at'   => null,
                    'end_at'     => null,
                    'type'       => TaskTimeLogType::TIME_LOG->value,
                    'comment'    => null,
                    'task_id'    => $latestTask->id,  // Assign the task ID
                    'user_id'    => $validatedData['userId'],
                    'status'     => TaskTimeLogStatus::PAUSE->value,
                    'total_time' => $totalTime,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => __('messages.success.created'),
                'data' => [
                    'taskTimeLogId' => $taskTimeLog->id,
                    'taskId' => $taskTimeLog->task_id,
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }

    /**
     * Show the form for editing the specified resource.
     */

    public function edit(Request $request)
    {
        $taskTimeLog  =  $this->taskTimeLogService->editTaskTimeLog($request->taskTimeLogId);

        return new TaskTimeLogResource($taskTimeLog);


    }

}
