<?php

namespace App\Http\Controllers\Api\Private\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\CreateTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\Task\AllTaskCollection;
use App\Http\Resources\Task\AllTaskResource;
use App\Http\Resources\Task\TaskResource;
use App\Services\Task\TaskService;
use App\Utils\PaginateCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class TaskController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->middleware('auth:api');
        $this->middleware('permission:all_tasks', ['only' => ['index']]);
        $this->middleware('permission:create_task', ['only' => ['create']]);
        $this->middleware('permission:edit_task', ['only' => ['edit']]);
        $this->middleware('permission:update_task', ['only' => ['update']]);
        $this->middleware('permission:delete_task', ['only' => ['delete']]);
        $this->taskService = $taskService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allTasks = $this->taskService->allTasks();


       return response()->json(
        new AllTaskCollection($allTasks['tasks'], [
            'totalHours' => $allTasks['totalTime'],
            'totalTasks' => $allTasks['total']
        ])
    );
    }
    /**
     * Show the form for creating a new resource.
     */

    public function create(CreateTaskRequest $createTaskRequest)
    {

        try {
            DB::beginTransaction();

            $task = $this->taskService->createTask($createTaskRequest->validated());

            DB::commit();

            return response()->json([
                'message' => __('messages.success.created'),
                'data' => new TaskResource($task)
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
        $task  =  $this->taskService->editTask($request->taskId);

        return new TaskResource($task);


    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $updateTaskRequest)
    {

        try {
            DB::beginTransaction();
            $this->taskService->updateTask($updateTaskRequest->validated());
            DB::commit();
            return response()->json([
                 'message' => __('messages.success.updated')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(Request $request)
    {

        try {
            DB::beginTransaction();
            $this->taskService->deleteTask($request->taskId);
            DB::commit();
            return response()->json([
                'message' => __('messages.success.deleted')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }

    public function changeStatus(Request $request)
    {

        try {
            DB::beginTransaction();
            $this->taskService->changeStatus($request->taskId, $request->status);
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
