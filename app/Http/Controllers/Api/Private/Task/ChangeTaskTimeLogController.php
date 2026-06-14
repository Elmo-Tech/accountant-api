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


class ChangeTaskTimeLogController extends Controller
{
    protected $taskTimeLogService;

    public function __construct(TaskTimeLogService $taskTimeLogService)
    {
        $this->middleware('auth:api');
        $this->middleware('permission:change_task_time_log', ['only' => ['update']]);
        //$this->middleware('permission:delete_task_time_log', ['only' => ['delete']]);
        $this->taskTimeLogService = $taskTimeLogService;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {

        try {
            DB::beginTransaction();
            $taskTimeLog = TaskTimeLog::find($request->taskTimeLogId);

            if($taskTimeLog->status != TaskTimeLogStatus::STOP) {
                return response()->json([
                    'message' => "you can't change this task time log",
                ]);
            }

            $taskTimeLog->update([
                'total_time' => $request->totalTime,
                'comment' => $request->comment
            ]);

            DB::commit();
            return response()->json([
                 'message' => __('messages.success.updated')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }


}
