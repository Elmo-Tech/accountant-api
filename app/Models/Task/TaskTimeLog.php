<?php

namespace App\Models\Task;

use App\Enums\Task\TaskTimeLogStatus;
use App\Enums\Task\TaskTimeLogType;
use App\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskTimeLog extends Model
{
    use HasFactory, SoftDeletes, CreatedUpdatedBy;

    protected $fillable = [
        'start_at',
        'end_at',
        'total_time',
        'status',
        'type',
        'comment',
        'task_id',
        'user_id'
    ];

    protected $casts = [
        'status' => TaskTimeLogStatus::class,
        'type' => TaskTimeLogType::class,
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

}
