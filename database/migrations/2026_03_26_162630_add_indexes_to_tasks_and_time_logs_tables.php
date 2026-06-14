<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('is_new',              'idx_tasks_is_new');
            $table->index('client_id',           'idx_tasks_client_id');
            $table->index('user_id',             'idx_tasks_user_id');
            $table->index('status',              'idx_tasks_status');
            $table->index('service_category_id', 'idx_tasks_service_category_id');
            $table->index('created_at',          'idx_tasks_created_at');
            // composite: most common query pattern
            $table->index(['is_new', 'created_at'], 'idx_tasks_is_new_created_at');
        });

        Schema::table('task_time_logs', function (Blueprint $table) {
            $table->index('task_id',             'idx_ttl_task_id');
            $table->index('created_at',          'idx_ttl_created_at');
            // composite for the subquery GROUP BY task_id + MAX(created_at)
            $table->index(['task_id', 'created_at'], 'idx_ttl_task_id_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_is_new');
            $table->dropIndex('idx_tasks_client_id');
            $table->dropIndex('idx_tasks_user_id');
            $table->dropIndex('idx_tasks_status');
            $table->dropIndex('idx_tasks_service_category_id');
            $table->dropIndex('idx_tasks_created_at');
            $table->dropIndex('idx_tasks_is_new_created_at');
        });

        Schema::table('task_time_logs', function (Blueprint $table) {
            $table->dropIndex('idx_ttl_task_id');
            $table->dropIndex('idx_ttl_created_at');
            $table->dropIndex('idx_ttl_task_id_created_at');
        });
    }
};
