# Task Management Module Documentation

## Overview

The Task Management Module handles all operations related to tasks (billable work), including task creation, time tracking, status management, and reporting. A key feature is support for time durations exceeding 24 hours.

## Module Location

```
app/
├── Models/
│   └── Task/
│       ├── Task.php
│       └── TaskTimeLog.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── Private/
│   │           └── Task/
│   │               ├── TaskController.php
│   │               ├── AdminTaskController.php
│   │               ├── ActiveTaskController.php
│   │               ├── TaskTimeLogController.php
│   │               ├── ChangeTaskTimeLogController.php
│   │               └── AdminTaskExportController.php
│   ├── Requests/
│   │   └── Task/
│   │       ├── CreateTaskRequest.php
│   │       ├── UpdateTaskRequest.php
│   │       └── TaskTimeLog/
│   │           ├── CreateTaskTimeLogRequest.php
│   │           └── UpdateTaskTimeLogRequest.php
│   └── Resources/
│       ├── Task/
│       │   ├── TaskResource.php
│       │   ├── AllTaskResource.php
│       │   └── TaskTimeLogResource.php
│       └── AdminTask/
│           ├── AllAdminTaskResource.php
│           └── AllAdminTaskCollection.php
├── Services/
│   └── Task/
│       ├── TaskService.php
│       ├── TaskTimeLogService.php
│       └── ExportTaskService.php
├── Exports/
│   └── TasksExport.php
├── Enums/
│   └── Task/
│       ├── TaskStatus.php
│       ├── TaskTimeLogStatus.php
│       └── TaskTimeLogType.php
└── Filters/
    └── Task/
        ├── FilterTask.php
        ├── FilterTaskDateBetween.php
        └── FilterTaskStartEndDate.php
```

## Database Schema

### tasks Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| number | VARCHAR(255) | Task number (auto-generated: T_00001) |
| title | VARCHAR(255) | Task title |
| description | TEXT | Task description |
| status | TINYINT | Task status (0=TO_WORK, 1=IN_PROGRESS, 2=DONE) |
| client_id | BIGINT | FK to clients |
| user_id | BIGINT | FK to users (assigned to) |
| service_category_id | BIGINT | FK to service_categories |
| invoice_id | BIGINT | FK to invoices (if invoiced) |
| connection_type_id | BIGINT | FK to parameter_values |
| start_date | DATE | Task start date |
| end_date | DATE | Task end date |
| price | DECIMAL(8,2) | Task price |
| price_after_discount | DECIMAL(8,2) | Price after discount |
| is_new | BOOLEAN | Is new task flag |
| created_by | BIGINT | User who created |
| updated_by | BIGINT | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

### task_time_logs Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| task_id | BIGINT | FK to tasks |
| user_id | BIGINT | FK to users |
| start_at | DATETIME | Start timestamp |
| end_at | DATETIME | End timestamp |
| total_time | TIME | Total duration (supports > 24 hours) |
| status | TINYINT | Log status (0=START, 1=PAUSE, 2=STOP) |
| type | TINYINT | Log type (1=TIME_LOG, 2=MANUAL) |
| comment | TEXT | Optional comment |
| created_by | BIGINT | User who created |
| updated_by | BIGINT | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

## Enums

### TaskStatus (app/Enums/Task/TaskStatus.php)

```php
enum TaskStatus: int
{
    case TO_WORK = 0;       // Task created, not started
    case IN_PROGRESS = 1;   // Task has time logs
    case DONE = 2;          // Task completed
}
```

### TaskTimeLogStatus (app/Enums/Task/TaskTimeLogStatus.php)

```php
enum TaskTimeLogStatus: int
{
    case START = 0;  // Timer is running
    case PAUSE = 1;  // Timer paused
    case STOP = 2;   // Timer stopped (task completed)
}
```

### TaskTimeLogType (app/Enums/Task/TaskTimeLogType.php)

```php
enum TaskTimeLogType: int
{
    case TIME_LOG = 1;  // Automatic time tracking
    case MANUAL = 2;    // Manual time entry
}
```

## Controllers

### 1. TaskController (app/Http/Controllers/Api/Private/Task/TaskController.php)

Main controller for task CRUD operations.

#### Methods:

**index(Request $request)**
- Permission: `all_tasks`
- Returns: List of all tasks with total hours and count
- Response: `AllTaskCollection` with metadata

**create(CreateTaskRequest $request)**
- Permission: `create_task`
- Creates new task with status TO_WORK
- Auto-generates task number (T_00001, T_00002, etc.)
- Returns: Success message with TaskResource

**edit(Request $request)**
- Permission: `edit_task`
- Parameters: `taskId`
- Returns: TaskResource with task details

**update(UpdateTaskRequest $request)**
- Permission: `update_task`
- Updates task details
- Cannot change status from DONE to other status
- Returns: Success message

**delete(Request $request)**
- Permission: `delete_task`
- Parameters: `taskId`
- Soft deletes the task
- Returns: Success message

**changeStatus(Request $request)**
- Parameters: `taskId`, `status`
- Changes task status
- Returns: Success message

### 2. AdminTaskController (app/Http/Controllers/Api/Private/Task/AdminTaskController.php)

Admin view of all tasks (similar to TaskController but for admin dashboard).

#### Methods:

**index(Request $request)**
- Returns: All tasks with total hours and count
- Response: `AllAdminTaskCollection`
- No authentication middleware (commented out)

### 3. ActiveTaskController (app/Http/Controllers/Api/Private/Task/ActiveTaskController.php)

Manages active tasks for the logged-in user.

#### Methods:

**index(Request $request)**
- Permission: `all_active_tasks`
- Returns: Tasks with status TO_WORK or IN_PROGRESS for current user
- Calculates real-time duration for running tasks
- Response includes:
  - `taskId`, `title`, `status`, `clientId`, `clientName`
  - `totalTime` (in seconds)
  - `time` (formatted HH:MM:SS)
  - `timerStatus` (0=not started, 1=running, 2=paused)
  - `timeLogId` (latest time log ID)

**update(Request $request)**
- Permission: `update_active_task`
- Parameters: `taskTimeLogId`, `endAt`, `taskStatus`
- Stops the timer by setting end_at
- If taskStatus is DONE, marks task as completed
- Returns: Success message


### 4. TaskTimeLogController (app/Http/Controllers/Api/Private/Task/TaskTimeLogController.php)

Manages time tracking for tasks.

#### Methods:

**index(Request $request)**
- Permission: `all_task_time_logs`
- Parameters: `taskId`
- Returns: All time logs for a specific task
- Response: `AllTaskTimeLogResource` collection

**create(CreateTaskTimeLogRequest $request)**
- Permission: `create_task_time_log`
- Creates new time log entry
- **Auto-pauses other running tasks** for the same user
- When starting a task, all other IN_PROGRESS tasks are paused
- Calculates elapsed time for paused tasks
- Sets task status to IN_PROGRESS on first time log
- Sets task status to DONE if status is STOP
- Returns: Success message with taskTimeLogId and taskId

**edit(Request $request)**
- Permission: `edit_task_time_log`
- Parameters: `taskTimeLogId`
- Returns: TaskTimeLogResource

#### Time Calculation Logic (create method):

```php
// 1. Find all other running tasks for this user
$latestPlayedTasks = Task::where('user_id', $userId)
    ->where('status', TaskStatus::IN_PROGRESS)
    ->whereNot('id', $currentTaskId)
    ->with('latestTimeLog')
    ->get();

// 2. For each running task, calculate elapsed time
foreach ($latestPlayedTasks as $latestTask) {
    $latestTimeLog = $latestTask->latestTimeLog;
    
    // Skip if not in START status
    if ($latestTimeLog->status != TaskTimeLogStatus::START) {
        continue;
    }
    
    // Calculate current session duration
    $currentSeconds = Carbon::now()->diffInSeconds($latestTimeLog->created_at);
    
    // Get previous stored time
    $previousTime = $latestTimeLog->total_time;
    
    // Convert previous time to seconds (supports > 24 hours)
    $timeParts = explode(':', $previousTime);
    $previousSeconds = ($timeParts[0] * 3600) + ($timeParts[1] * 60) + $timeParts[2];
    
    // Add them together
    $totalSeconds = $previousSeconds + $currentSeconds;
    
    // Format to HH:MM:SS (supports > 24 hours)
    $hours = floor($totalSeconds / 3600);
    $minutes = floor(($totalSeconds / 60) % 60);
    $seconds = $totalSeconds % 60;
    $totalTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    
    // Create PAUSE log
    TaskTimeLog::create([
        'task_id' => $latestTask->id,
        'user_id' => $userId,
        'status' => TaskTimeLogStatus::PAUSE,
        'type' => TaskTimeLogType::TIME_LOG,
        'total_time' => $totalTime,
    ]);
}
```

### 5. ChangeTaskTimeLogController (app/Http/Controllers/Api/Private/Task/ChangeTaskTimeLogController.php)

Allows editing completed time logs.

#### Methods:

**update(Request $request)**
- Permission: `change_task_time_log`
- Parameters: `taskTimeLogId`, `totalTime`, `comment`
- Only allows editing logs with status STOP
- Updates total_time and comment
- Returns: Success message


### 6. AdminTaskExportController (app/Http/Controllers/Api/Private/Task/AdminTaskExportController.php)

Exports tasks to Excel format.

#### Methods:

**index(Request $request)**
- Exports all tasks to Excel (.xlsx)
- Uses PhpSpreadsheet library
- Columns: Numero ticket, Cliente, Oggetto, Servizio, Utente, Totale ore, Ora inizio, Data creazione, Stato
- Includes sum row for total hours
- Formats time as [h]:mm:ss (supports > 24 hours)
- Auto-sizes columns and adds filters
- Saves to `storage/app/public/tasks_exports/`
- Returns: JSON with file URL

#### Excel Time Conversion:

```php
private function convertToExcelTime($time)
{
    if (preg_match('/^(\d+):(\d{2}):(\d{2})$/', $time, $matches)) {
        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];
        $seconds = (int) $matches[3];
        return ($hours / 24) + ($minutes / 1440) + ($seconds / 86400);
    }
    return null;
}
```

## Services

### 1. TaskService (app/Services/Task/TaskService.php)

Main business logic for task operations.

#### Methods:

**allTasks()**
- Filters: search, userId, status, serviceCategoryId, clientId, startDate, endDate
- Returns: Array with `tasks`, `totalTime`, `total`
- Calculates total time across all tasks
- Supports time > 24 hours

**createTask(array $taskData)**
- Creates new task
- Auto-generates task number on creation
- Sets is_new = 1
- Returns: Task model

**editTask(string $taskId)**
- Loads task with time logs
- Returns: Task model

**updateTask(array $taskData)**
- Updates task details
- Prevents changing status from DONE to other status
- Returns: Task model or error response

**deleteTask(string $taskId)**
- Soft deletes task

**changeStatus(string $taskId, int $status)**
- Updates task status

### 2. TaskTimeLogService (app/Services/Task/TaskTimeLogService.php)

Business logic for time log operations.

#### Methods:

**allTaskTimeLogs(array $filters)**
- Parameters: `taskId`
- Returns: Collection of TaskTimeLog

**createTaskTimeLog(array $taskTimeLogData)**
- Creates new time log
- Sets task status to IN_PROGRESS on first log
- Sets task status to DONE if status is STOP
- Returns: TaskTimeLog model

**editTaskTimeLog(string $taskTimeLogId)**
- Returns: TaskTimeLog model

**deleteTaskTimeLog(string $taskTimeLogId)**
- Soft deletes time log

### 3. ExportTaskService (app/Services/Task/ExportTaskService.php)

Service for exporting tasks (used by AdminTaskExportController).

#### Methods:

**allTasks()**
- Similar to TaskService::allTasks()
- Optimized for export with date filtering
- Calculates total time with support for > 24 hours
- Returns: Array with `tasks`, `totalTime`, `total`


## Models

### 1. Task (app/Models/Task/Task.php)

Main task model with relationships and computed attributes.

#### Relationships:

```php
client()          // belongsTo Client
user()            // belongsTo User (assigned user)
serviceCategory() // belongsTo ServiceCategory (withTrashed)
timeLogs()        // hasMany TaskTimeLog
latestTimeLog()   // hasOne TaskTimeLog (latest)
invoiceDetails()  // morphMany InvoiceDetail
```

#### Computed Attributes:

**getTotalHoursAttribute()**
- Calculates total time from latest time log
- Supports hours > 24 (e.g., "39:18:23")
- If task is running (START status), adds elapsed time
- Returns: Formatted time string (HH:MM:SS)

```php
public function getTotalHoursAttribute()
{
    $latestTimeLog = $this->timeLogs()
        ->where('type', TaskTimeLogType::TIME_LOG)
        ->latest()
        ->first();

    if ($latestTimeLog == null) {
        return "00:00:00";
    }

    // Convert stored time to seconds (supports > 24 hours)
    $totalSeconds = 0;
    if (!empty($latestTimeLog->total_time)) {
        $parts = explode(':', $latestTimeLog->total_time);
        if (count($parts) === 3) {
            $totalSeconds = ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        }
    }

    // Add elapsed time if running
    if ($latestTimeLog->status == TaskTimeLogStatus::START) {
        $elapsedSeconds = Carbon::now()->diffInSeconds($latestTimeLog->created_at);
        $totalSeconds += $elapsedSeconds;
    }

    // Format to HH:MM:SS (supports > 24 hours)
    $hours = floor($totalSeconds / 3600);
    $minutes = floor(($totalSeconds / 60) % 60);
    $seconds = $totalSeconds % 60;

    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}
```

**getCurrentTimeAttribute()**
- Similar to getTotalHoursAttribute()
- Returns current time for the task
- Used for real-time display

**getTotalPriceAfterDiscountAttribute()**
- Gets client discount for the service category
- Returns: Discount information

**getTimeLogStatusAttribute()**
- Returns latest time log status
- Default: 3 if no logs exist

**getLatestTimeLogIdAttribute()**
- Returns ID of latest START status time log
- Returns: Empty string if none found

#### Boot Method:

```php
static::creating(function($model){
    $model->is_new = 1;
});

static::created(function ($model) {
    $model->number = 'T_' . str_pad($model->id, 5, '0', STR_PAD_LEFT);
    $model->save();
});
```

### 2. TaskTimeLog (app/Models/Task/TaskTimeLog.php)

Time log entries for tasks.

#### Fillable Fields:

```php
'start_at', 'end_at', 'total_time', 'status', 'type', 'comment', 'task_id', 'user_id'
```

#### Casts:

```php
'status' => TaskTimeLogStatus::class,
'type' => TaskTimeLogType::class,
'start_at' => 'datetime',
'end_at' => 'datetime',
```


## API Endpoints

### Task Management

#### GET /api/v1/tasks
Get all tasks with filtering.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
filter[search]           - Search in task title, number, client name
filter[userId]           - Filter by assigned user
filter[status]           - Filter by status (0, 1, 2)
filter[serviceCategoryId] - Filter by service category
filter[clientId]         - Filter by client
filter[startDate]        - Filter by start date (YYYY-MM-DD)
filter[endDate]          - Filter by end date (YYYY-MM-DD)
```

**Response:**
```json
{
  "data": [
    {
      "taskId": 1,
      "number": "T_00001",
      "title": "Website Development",
      "status": 1,
      "clientName": "ABC Company",
      "serviceCategoryName": "Web Development",
      "accountantName": "John Doe",
      "totalHours": "39:18:23",
      "startTime": "15/01/2026 10:30:00",
      "createdAt": "15/01/2026"
    }
  ],
  "meta": {
    "totalHours": "156:45:30",
    "totalTasks": 25
  }
}
```

#### POST /api/v1/tasks/create
Create new task.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "Website Development",
  "description": "Build company website",
  "status": 0,
  "clientId": 5,
  "userId": 3,
  "serviceCategoryId": 2,
  "connectionTypeId": 10,
  "startDate": "2026-03-01",
  "endDate": "2026-03-31"
}
```

**Response:**
```json
{
  "message": "Created successfully",
  "data": {
    "taskId": 123,
    "number": "T_00123",
    "title": "Website Development",
    "status": 0,
    "userId": 3,
    "clientId": 5,
    "serviceCategoryId": 2,
    "description": "Build company website",
    "connectionTypeId": 10
  }
}
```

#### GET /api/v1/tasks/edit
Get task details for editing.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
taskId - Task ID (required)
```

**Response:**
```json
{
  "taskId": 123,
  "number": "T_00123",
  "title": "Website Development",
  "status": 1,
  "userId": 3,
  "clientId": 5,
  "serviceCategoryId": 2,
  "description": "Build company website",
  "currentTime": "12:30:45",
  "latestTimeLogId": 456
}
```

#### PUT /api/v1/tasks/update
Update task details.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "taskId": 123,
  "title": "Website Development - Updated",
  "description": "Build company website with CMS",
  "status": 1,
  "clientId": 5,
  "userId": 3,
  "serviceCategoryId": 2,
  "connectionTypeId": 10,
  "startDate": "2026-03-01",
  "endDate": "2026-04-15"
}
```

**Response:**
```json
{
  "message": "Updated successfully"
}
```

#### DELETE /api/v1/tasks/delete
Delete task (soft delete).

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "taskId": 123
}
```

**Response:**
```json
{
  "message": "Deleted successfully"
}
```

#### PUT /api/v1/tasks/change-status
Change task status.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "taskId": 123,
  "status": 2
}
```

**Response:**
```json
{
  "message": "Updated successfully"
}
```


### Active Tasks

#### GET /api/v1/user-active-tasks
Get active tasks for logged-in user.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
[
  {
    "taskId": 123,
    "title": "Website Development",
    "status": 1,
    "clientId": 5,
    "clientName": "ABC Company",
    "totalTime": 45023,
    "time": "12:30:23",
    "timerStatus": 1,
    "timeLogId": 456
  },
  {
    "taskId": 124,
    "title": "Mobile App",
    "status": 0,
    "clientId": 6,
    "clientName": "XYZ Corp",
    "totalTime": 0,
    "time": "00:00:00",
    "timerStatus": 0
  }
]
```

**Timer Status Values:**
- 0: Not started
- 1: Running
- 2: Paused

#### PUT /api/v1/user-active-tasks/update
Stop timer and optionally complete task.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "taskTimeLogId": 456,
  "endAt": "2026-03-05 15:30:00",
  "taskStatus": 2
}
```

**Response:**
```json
{
  "message": "Updated successfully"
}
```

### Admin Tasks

#### GET /api/v1/admin-tasks
Get all tasks (admin view).

**Response:**
```json
{
  "data": [
    {
      "taskId": 1,
      "number": "T_00001",
      "title": "Website Development",
      "status": 1,
      "clientName": "ABC Company",
      "serviceCategoryName": "Web Development",
      "accountantName": "John Doe",
      "totalHours": "39:18:23",
      "startTime": "15/01/2026 10:30:00",
      "createdAt": "15/01/2026"
    }
  ],
  "meta": {
    "totalHours": "156:45:30",
    "totalTasks": 25
  }
}
```

#### GET /api/v1/admin-ticket-export
Export tasks to Excel.

**Response:**
```json
{
  "path": "https://accountant-api.testingelmo.com/storage/tasks_exports/tasks_2026_03_05_14_30_45.xlsx"
}
```

### Time Logs

#### GET /api/v1/task-time-logs
Get all time logs for a task.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
taskId - Task ID (required)
```

**Response:**
```json
[
  {
    "taskTimeLogId": 456,
    "startAt": "05/03/2026 09:00",
    "endAt": "05/03/2026 12:30",
    "taskId": 123,
    "userId": 3,
    "type": 1,
    "comment": "",
    "status": 2
  },
  {
    "taskTimeLogId": 457,
    "startAt": "05/03/2026 14:00",
    "endAt": "",
    "taskId": 123,
    "userId": 3,
    "type": 1,
    "comment": "",
    "status": 0
  }
]
```

#### POST /api/v1/task-time-logs/create
Create new time log (start/pause/stop timer).

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "taskId": 123,
  "userId": 3,
  "type": 1,
  "status": 0,
  "currentTime": "12:30:45",
  "comment": "Working on homepage"
}
```

**Response:**
```json
{
  "message": "Created successfully",
  "data": {
    "taskTimeLogId": 458,
    "taskId": 123
  }
}
```

**Important:** When creating a START log, all other running tasks for the same user are automatically paused.

#### GET /api/v1/task-time-logs/edit
Get time log details.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
taskTimeLogId - Time log ID (required)
```

**Response:**
```json
{
  "taskTimeLogId": 456,
  "startAt": "05/03/2026 09:00",
  "endAt": "05/03/2026 12:30",
  "taskId": 123,
  "userId": 3,
  "type": 1,
  "comment": "Working on homepage",
  "status": 2
}
```

#### PUT /api/v1/task-time-logs/change-time
Update completed time log.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "taskTimeLogId": 456,
  "totalTime": "03:45:30",
  "comment": "Updated comment"
}
```

**Response:**
```json
{
  "message": "Updated successfully"
}
```

**Note:** Only time logs with status STOP (2) can be edited.


## Business Logic

### Task Creation Flow

1. User submits task creation request
2. System validates request data
3. Task is created with status TO_WORK (0)
4. Task number is auto-generated: T_00001, T_00002, etc.
5. is_new flag is set to 1
6. Task is returned to user

### Time Tracking Flow

#### Starting a Task

1. User clicks "Start" on a task
2. System creates time log with:
   - status: START (0)
   - type: TIME_LOG (1)
   - currentTime: Previous accumulated time or "00:00:00"
3. System finds all other IN_PROGRESS tasks for this user
4. For each running task:
   - Calculate elapsed time since last START
   - Add to previous total_time
   - Create PAUSE log with updated total_time
5. Task status changes to IN_PROGRESS (1)
6. Timer starts running

#### Pausing a Task

1. User clicks "Pause" on running task
2. System creates time log with:
   - status: PAUSE (1)
   - Calculate elapsed time since START
   - Add to previous total_time
3. Timer stops but task remains IN_PROGRESS

#### Resuming a Task

1. User clicks "Resume" on paused task
2. Same flow as "Starting a Task"
3. Other running tasks are paused
4. This task starts running again

#### Stopping a Task (Completing)

1. User clicks "Stop" or "Complete" on task
2. System creates time log with:
   - status: STOP (2)
   - Calculate final elapsed time
   - Add to previous total_time
3. Task status changes to DONE (2)
4. Task is completed and cannot be restarted

### Time Calculation Logic (Supporting > 24 Hours)

The system uses manual time parsing instead of Carbon::parse() to support durations exceeding 24 hours.

#### Converting Time String to Seconds

```php
function timeToSeconds($timeString) {
    // Input: "39:18:23"
    $parts = explode(':', $timeString);
    
    // Extract hours, minutes, seconds
    $hours = (int) $parts[0];    // 39
    $minutes = (int) $parts[1];  // 18
    $seconds = (int) $parts[2];  // 23
    
    // Convert to total seconds
    $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;
    // Result: 141503 seconds
    
    return $totalSeconds;
}
```

#### Converting Seconds to Time String

```php
function secondsToTime($totalSeconds) {
    // Input: 141503 seconds
    
    // Calculate hours (can be > 24)
    $hours = floor($totalSeconds / 3600);  // 39
    
    // Calculate remaining minutes
    $minutes = floor(($totalSeconds / 60) % 60);  // 18
    
    // Calculate remaining seconds
    $seconds = $totalSeconds % 60;  // 23
    
    // Format with leading zeros
    $timeString = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    // Result: "39:18:23"
    
    return $timeString;
}
```

#### Real-Time Time Calculation

```php
// Get latest time log
$latestTimeLog = $task->latestTimeLog;

// Get stored time
$storedTime = $latestTimeLog->total_time;  // "12:30:00"

// Convert to seconds
$storedSeconds = timeToSeconds($storedTime);  // 45000

// If task is running, add elapsed time
if ($latestTimeLog->status == TaskTimeLogStatus::START) {
    $elapsedSeconds = Carbon::now()->diffInSeconds($latestTimeLog->created_at);
    $totalSeconds = $storedSeconds + $elapsedSeconds;
} else {
    $totalSeconds = $storedSeconds;
}

// Convert back to time string
$currentTime = secondsToTime($totalSeconds);  // "12:45:30"
```

### Auto-Pause Logic

When a user starts working on a task, all other running tasks for that user are automatically paused. This ensures accurate time tracking and prevents multiple tasks from running simultaneously.

```php
// Find all other running tasks
$runningTasks = Task::where('user_id', $userId)
    ->where('status', TaskStatus::IN_PROGRESS)
    ->whereNot('id', $currentTaskId)
    ->with('latestTimeLog')
    ->get();

foreach ($runningTasks as $task) {
    $latestLog = $task->latestTimeLog;
    
    // Skip if not running
    if ($latestLog->status != TaskTimeLogStatus::START) {
        continue;
    }
    
    // Calculate elapsed time
    $elapsedSeconds = Carbon::now()->diffInSeconds($latestLog->created_at);
    
    // Get previous time
    $previousSeconds = timeToSeconds($latestLog->total_time);
    
    // Calculate new total
    $totalSeconds = $previousSeconds + $elapsedSeconds;
    $totalTime = secondsToTime($totalSeconds);
    
    // Create PAUSE log
    TaskTimeLog::create([
        'task_id' => $task->id,
        'user_id' => $userId,
        'status' => TaskTimeLogStatus::PAUSE,
        'type' => TaskTimeLogType::TIME_LOG,
        'total_time' => $totalTime,
    ]);
}
```


### Task Status Transitions

```
TO_WORK (0)
    ↓ (First time log created)
IN_PROGRESS (1)
    ↓ (Time log with STOP status)
DONE (2)
```

**Rules:**
- Task starts as TO_WORK when created
- First time log changes status to IN_PROGRESS
- STOP time log changes status to DONE
- Cannot change from DONE to other status (task is completed)
- Can manually change status via changeStatus endpoint

### Validation Rules

#### CreateTaskRequest

```php
'title' => 'nullable',
'description' => 'nullable',
'status' => 'required|enum:TaskStatus',
'clientId' => 'required',
'userId' => 'required',
'serviceCategoryId' => 'required',
'connectionTypeId' => 'nullable',
'startDate' => 'nullable',
'endDate' => 'nullable'
```

#### UpdateTaskRequest

```php
'taskId' => 'required',
'title' => 'nullable',
'description' => 'nullable',
'status' => 'required|enum:TaskStatus',
'clientId' => 'required',
'userId' => 'required',
'serviceCategoryId' => 'required',
'connectionTypeId' => 'nullable',
'startDate' => 'nullable',
'endDate' => 'nullable'
```

#### CreateTaskTimeLogRequest

```php
'type' => 'required|enum:TaskTimeLogType',
'comment' => 'nullable',
'status' => 'required|enum:TaskTimeLogStatus',
'taskId' => 'required',
'userId' => 'required',
'currentTime' => 'required'
```

## Usage Examples

### JavaScript/Frontend Integration

#### Fetching All Tasks

```javascript
async function fetchTasks(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.search) params.append('filter[search]', filters.search);
  if (filters.userId) params.append('filter[userId]', filters.userId);
  if (filters.status !== undefined) params.append('filter[status]', filters.status);
  if (filters.clientId) params.append('filter[clientId]', filters.clientId);
  if (filters.startDate) params.append('filter[startDate]', filters.startDate);
  if (filters.endDate) params.append('filter[endDate]', filters.endDate);
  
  const response = await fetch(`/api/v1/tasks?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data;
}

// Usage
const tasks = await fetchTasks({
  status: 1,
  userId: 3,
  startDate: '2026-03-01',
  endDate: '2026-03-31'
});

console.log(`Total tasks: ${tasks.meta.totalTasks}`);
console.log(`Total hours: ${tasks.meta.totalHours}`);
```

#### Creating a Task

```javascript
async function createTask(taskData) {
  const response = await fetch('/api/v1/tasks/create', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      title: taskData.title,
      description: taskData.description,
      status: 0, // TO_WORK
      clientId: taskData.clientId,
      userId: taskData.userId,
      serviceCategoryId: taskData.serviceCategoryId,
      connectionTypeId: taskData.connectionTypeId,
      startDate: taskData.startDate,
      endDate: taskData.endDate
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage
const newTask = await createTask({
  title: 'Website Development',
  description: 'Build company website',
  clientId: 5,
  userId: 3,
  serviceCategoryId: 2,
  connectionTypeId: 10,
  startDate: '2026-03-01',
  endDate: '2026-03-31'
});

console.log(`Task created: ${newTask.data.number}`);
```

#### Starting a Task Timer

```javascript
async function startTask(taskId, userId, currentTime = '00:00:00') {
  const response = await fetch('/api/v1/task-time-logs/create', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      taskId: taskId,
      userId: userId,
      type: 1, // TIME_LOG
      status: 0, // START
      currentTime: currentTime,
      comment: ''
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage
const timeLog = await startTask(123, 3, '12:30:45');
console.log(`Timer started. Time log ID: ${timeLog.data.taskTimeLogId}`);
```

#### Pausing a Task Timer

```javascript
async function pauseTask(taskId, userId, currentTime) {
  const response = await fetch('/api/v1/task-time-logs/create', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      taskId: taskId,
      userId: userId,
      type: 1, // TIME_LOG
      status: 1, // PAUSE
      currentTime: currentTime,
      comment: ''
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage
const timeLog = await pauseTask(123, 3, '15:45:30');
console.log('Timer paused');
```


#### Stopping a Task (Completing)

```javascript
async function stopTask(taskId, userId, currentTime) {
  const response = await fetch('/api/v1/task-time-logs/create', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      taskId: taskId,
      userId: userId,
      type: 1, // TIME_LOG
      status: 2, // STOP
      currentTime: currentTime,
      comment: 'Task completed'
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage
const timeLog = await stopTask(123, 3, '39:18:23');
console.log('Task completed');
```

#### Real-Time Timer Display

```javascript
class TaskTimer {
  constructor(taskId, initialTime = '00:00:00') {
    this.taskId = taskId;
    this.startTime = Date.now();
    this.initialSeconds = this.timeToSeconds(initialTime);
    this.intervalId = null;
  }
  
  timeToSeconds(timeString) {
    const parts = timeString.split(':');
    return (parseInt(parts[0]) * 3600) + (parseInt(parts[1]) * 60) + parseInt(parts[2]);
  }
  
  secondsToTime(totalSeconds) {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds / 60) % 60);
    const seconds = totalSeconds % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
  }
  
  start(displayElement) {
    this.intervalId = setInterval(() => {
      const elapsedSeconds = Math.floor((Date.now() - this.startTime) / 1000);
      const totalSeconds = this.initialSeconds + elapsedSeconds;
      const timeString = this.secondsToTime(totalSeconds);
      displayElement.textContent = timeString;
    }, 1000);
  }
  
  stop() {
    if (this.intervalId) {
      clearInterval(this.intervalId);
      this.intervalId = null;
    }
  }
  
  getCurrentTime() {
    const elapsedSeconds = Math.floor((Date.now() - this.startTime) / 1000);
    const totalSeconds = this.initialSeconds + elapsedSeconds;
    return this.secondsToTime(totalSeconds);
  }
}

// Usage
const timer = new TaskTimer(123, '12:30:45');
const displayElement = document.getElementById('timer-display');
timer.start(displayElement);

// When pausing
const currentTime = timer.getCurrentTime();
timer.stop();
await pauseTask(123, 3, currentTime);

// When resuming
const newTimer = new TaskTimer(123, currentTime);
newTimer.start(displayElement);
await startTask(123, 3, currentTime);
```

#### Fetching Active Tasks

```javascript
async function fetchActiveTasks() {
  const response = await fetch('/api/v1/user-active-tasks', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const tasks = await response.json();
  return tasks;
}

// Usage
const activeTasks = await fetchActiveTasks();

activeTasks.forEach(task => {
  console.log(`Task: ${task.title}`);
  console.log(`Client: ${task.clientName}`);
  console.log(`Time: ${task.time}`);
  console.log(`Status: ${task.timerStatus === 1 ? 'Running' : task.timerStatus === 2 ? 'Paused' : 'Not Started'}`);
});
```

#### Updating Task

```javascript
async function updateTask(taskData) {
  const response = await fetch('/api/v1/tasks/update', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      taskId: taskData.taskId,
      title: taskData.title,
      description: taskData.description,
      status: taskData.status,
      clientId: taskData.clientId,
      userId: taskData.userId,
      serviceCategoryId: taskData.serviceCategoryId,
      connectionTypeId: taskData.connectionTypeId,
      startDate: taskData.startDate,
      endDate: taskData.endDate
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage
await updateTask({
  taskId: 123,
  title: 'Website Development - Updated',
  description: 'Build company website with CMS',
  status: 1,
  clientId: 5,
  userId: 3,
  serviceCategoryId: 2,
  connectionTypeId: 10,
  startDate: '2026-03-01',
  endDate: '2026-04-15'
});
```

#### Exporting Tasks to Excel

```javascript
async function exportTasks() {
  const response = await fetch('/api/v1/admin-ticket-export', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const result = await response.json();
  
  // Download the file
  window.open(result.path, '_blank');
}

// Usage
await exportTasks();
```


## Permissions

The following permissions control access to task management features:

| Permission | Description |
|-----------|-------------|
| all_tasks | View all tasks list |
| create_task | Create new tasks |
| edit_task | View task details for editing |
| update_task | Update task information |
| delete_task | Delete tasks |
| all_active_tasks | View active tasks for current user |
| update_active_task | Update active task timer |
| all_task_time_logs | View time logs for a task |
| create_task_time_log | Create time log entries |
| edit_task_time_log | View time log details |
| change_task_time_log | Edit completed time logs |

## Testing

### Manual Testing Checklist

#### Task CRUD Operations

- [ ] Create task with all fields
- [ ] Create task with minimal fields
- [ ] View task list with filters
- [ ] Edit task details
- [ ] Update task successfully
- [ ] Delete task
- [ ] Verify task number auto-generation (T_00001, T_00002, etc.)

#### Time Tracking

- [ ] Start timer on new task (status changes to IN_PROGRESS)
- [ ] Verify other running tasks are paused
- [ ] Pause timer
- [ ] Resume timer
- [ ] Stop timer (task status changes to DONE)
- [ ] Verify time calculation with > 24 hours (e.g., 39:18:23)
- [ ] Test real-time timer display
- [ ] Verify elapsed time calculation

#### Time Log Management

- [ ] View time logs for a task
- [ ] Create manual time log
- [ ] Edit completed time log (STOP status)
- [ ] Verify cannot edit running time log
- [ ] Test auto-pause when starting another task

#### Active Tasks

- [ ] View active tasks for current user
- [ ] Verify timer status (running/paused/not started)
- [ ] Update active task timer
- [ ] Complete task from active tasks view

#### Export

- [ ] Export tasks to Excel
- [ ] Verify Excel formatting
- [ ] Verify time format [h]:mm:ss
- [ ] Verify sum row calculation
- [ ] Download exported file

#### Edge Cases

- [ ] Task with 0 time logs
- [ ] Task with > 24 hours (e.g., 50:30:15)
- [ ] Multiple users working on different tasks
- [ ] User switching between multiple tasks rapidly
- [ ] Completing task that is not running
- [ ] Updating completed task (should fail)

### API Testing with cURL

#### Create Task

```bash
curl -X POST https://accountant-api.testingelmo.com/api/v1/tasks/create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Task",
    "description": "Testing task creation",
    "status": 0,
    "clientId": 5,
    "userId": 3,
    "serviceCategoryId": 2
  }'
```

#### Start Timer

```bash
curl -X POST https://accountant-api.testingelmo.com/api/v1/task-time-logs/create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "taskId": 123,
    "userId": 3,
    "type": 1,
    "status": 0,
    "currentTime": "00:00:00"
  }'
```

#### Get Active Tasks

```bash
curl -X GET https://accountant-api.testingelmo.com/api/v1/user-active-tasks \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Pause Timer

```bash
curl -X POST https://accountant-api.testingelmo.com/api/v1/task-time-logs/create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "taskId": 123,
    "userId": 3,
    "type": 1,
    "status": 1,
    "currentTime": "02:30:45"
  }'
```

#### Complete Task

```bash
curl -X POST https://accountant-api.testingelmo.com/api/v1/task-time-logs/create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "taskId": 123,
    "userId": 3,
    "type": 1,
    "status": 2,
    "currentTime": "39:18:23"
  }'
```


## Troubleshooting

### Common Issues

#### Issue: Time shows as "00:00:00" for running task

**Cause:** Task has no time logs or latest time log has empty total_time.

**Solution:**
1. Check if task has time logs: `SELECT * FROM task_time_logs WHERE task_id = ?`
2. Verify latest time log has total_time value
3. Ensure time log status is correct (0=START, 1=PAUSE, 2=STOP)

#### Issue: Carbon::parse error with time > 24 hours

**Cause:** Using Carbon::parse() on time duration strings like "39:18:23".

**Solution:**
- Never use `Carbon::parse()` on time duration strings
- Use manual parsing: `explode(':', $time)` and calculate seconds
- Convert back using `sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds)`

**Example Fix:**
```php
// WRONG
$time = Carbon::parse('39:18:23'); // Error!

// CORRECT
$parts = explode(':', '39:18:23');
$seconds = ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
```

#### Issue: Multiple tasks running simultaneously for same user

**Cause:** Auto-pause logic not working or bypassed.

**Solution:**
1. Verify TaskTimeLogController::create() is being used
2. Check that auto-pause logic is not commented out
3. Ensure transaction is committed properly

#### Issue: Task status not changing to IN_PROGRESS

**Cause:** First time log not created properly.

**Solution:**
1. Check TaskTimeLogService::createTaskTimeLog()
2. Verify condition: `if($task->timeLogs()->count() == 1)`
3. Ensure task status update is saved

#### Issue: Cannot edit completed task

**Cause:** Task has status DONE (2) and system prevents status change.

**Solution:**
- This is by design to prevent accidental changes
- If needed, manually update in database or add admin override

#### Issue: Excel export shows incorrect time format

**Cause:** Time not converted to Excel decimal format.

**Solution:**
1. Use `convertToExcelTime()` method
2. Apply number format: `[h]:mm:ss`
3. Verify sum formula uses same format

#### Issue: Timer not updating in real-time

**Cause:** Frontend not calculating elapsed time.

**Solution:**
1. Implement JavaScript timer using setInterval
2. Calculate: `initialSeconds + elapsedSeconds`
3. Update display every second

#### Issue: Time log creation fails with validation error

**Cause:** Missing required fields in request.

**Solution:**
- Ensure all required fields are present:
  - taskId (required)
  - userId (required)
  - type (required, 1 or 2)
  - status (required, 0, 1, or 2)
  - currentTime (required, format: HH:MM:SS)

### Database Queries for Debugging

#### Check task time logs

```sql
SELECT 
    ttl.id,
    ttl.task_id,
    ttl.status,
    ttl.type,
    ttl.total_time,
    ttl.created_at,
    t.title,
    t.status as task_status
FROM task_time_logs ttl
JOIN tasks t ON ttl.task_id = t.id
WHERE ttl.task_id = 123
ORDER BY ttl.created_at DESC;
```

#### Find running tasks for user

```sql
SELECT 
    t.id,
    t.number,
    t.title,
    t.status,
    ttl.status as log_status,
    ttl.total_time,
    ttl.created_at
FROM tasks t
JOIN task_time_logs ttl ON t.id = ttl.task_id
WHERE t.user_id = 3
AND t.status = 1
AND ttl.id = (
    SELECT id FROM task_time_logs 
    WHERE task_id = t.id 
    ORDER BY created_at DESC 
    LIMIT 1
);
```

#### Calculate total time for all tasks

```sql
SELECT 
    t.id,
    t.number,
    t.title,
    ttl.total_time,
    ttl.status,
    CASE 
        WHEN ttl.status = 0 THEN 
            TIME_FORMAT(
                SEC_TO_TIME(
                    TIME_TO_SEC(ttl.total_time) + 
                    TIMESTAMPDIFF(SECOND, ttl.created_at, NOW())
                ),
                '%H:%i:%s'
            )
        ELSE ttl.total_time
    END as current_time
FROM tasks t
LEFT JOIN task_time_logs ttl ON t.id = ttl.task_id
WHERE ttl.id = (
    SELECT id FROM task_time_logs 
    WHERE task_id = t.id 
    AND type = 1
    ORDER BY created_at DESC 
    LIMIT 1
)
ORDER BY t.id DESC;
```

#### Find tasks with time > 24 hours

```sql
SELECT 
    t.id,
    t.number,
    t.title,
    ttl.total_time,
    TIME_TO_SEC(ttl.total_time) / 3600 as hours
FROM tasks t
JOIN task_time_logs ttl ON t.id = ttl.task_id
WHERE ttl.id = (
    SELECT id FROM task_time_logs 
    WHERE task_id = t.id 
    ORDER BY created_at DESC 
    LIMIT 1
)
AND TIME_TO_SEC(ttl.total_time) > 86400
ORDER BY TIME_TO_SEC(ttl.total_time) DESC;
```

### Performance Optimization

#### Indexing Recommendations

```sql
-- Index for finding latest time logs
CREATE INDEX idx_task_time_logs_task_created 
ON task_time_logs(task_id, created_at DESC);

-- Index for user's active tasks
CREATE INDEX idx_tasks_user_status 
ON tasks(user_id, status);

-- Index for filtering tasks
CREATE INDEX idx_tasks_filters 
ON tasks(client_id, service_category_id, status, created_at);
```

#### Query Optimization

- Use `with('latestTimeLog')` to eager load latest time log
- Avoid N+1 queries when loading task lists
- Use database transactions for time log creation
- Cache total time calculations for completed tasks

### Logging

Enable detailed logging for debugging:

```php
// In TaskTimeLogController::create()
Log::info('Creating time log', [
    'taskId' => $validatedData['taskId'],
    'userId' => $validatedData['userId'],
    'status' => $validatedData['status'],
    'currentTime' => $validatedData['currentTime']
]);

// Log auto-pause actions
Log::info('Auto-pausing task', [
    'taskId' => $latestTask->id,
    'previousTime' => $previousTime,
    'elapsedSeconds' => $currentSeconds,
    'newTotalTime' => $totalTime
]);
```

## Related Modules

- **Client Management**: Tasks are linked to clients
- **Service Category**: Tasks are categorized by service type
- **User Management**: Tasks are assigned to users
- **Invoice Management**: Completed tasks can be invoiced
- **Reporting**: Task data is used in various reports

## Future Enhancements

- Task templates for recurring work
- Task dependencies and subtasks
- Bulk task operations
- Advanced filtering and search
- Task comments and attachments
- Email notifications for task assignments
- Mobile app for time tracking
- Offline time tracking with sync
- Task budgets and estimates
- Gantt chart view for project planning

---

**Last Updated:** March 5, 2026  
**Module Version:** 1.0  
**Documentation Status:** Complete
