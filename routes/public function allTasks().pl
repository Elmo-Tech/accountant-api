public function allTasks()
    {
        $filters = request()->input('filter', []);
        $startDate = $filters['startDate'] ?? null;
        $endDate = $filters['endDate'] ?? null;

        //dd($startDate, $endDate);

        $tasks = QueryBuilder::for(Task::class)
            ->allowedFilters([
                AllowedFilter::custom('search', new FilterTask()), // Custom search filter
                AllowedFilter::exact('userId', 'user_id'),
                AllowedFilter::exact('status', 'status'),
                AllowedFilter::exact('serviceCategoryId', 'service_category_id'),
                AllowedFilter::exact('clientId', 'client_id'),
                AllowedFilter::exact('userId', 'user_id'),
            ])
            ->when(
                !empty($startDate) && !empty($endDate),
                function ($query) use ($startDate, $endDate) {
                    $query->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate);
                }
            )
            ->when(
                !empty($endDate) && empty($startDate),
                function ($query) use ($endDate) {

                    $query->whereDate('created_at', '<=', $endDate);
                }
            )
            ->when(
                empty($endDate) && !empty($startDate),
                function ($query) use ($startDate) {

                    $query->whereDate('created_at', '=', $startDate);
                }
            )
            ->where('is_new', 1)
            ->orderByDesc('id')
            ->get();

                    // Get IDs of filtered tasks
        $taskIds = $tasks->pluck('id');

        // Calculate total time for filtered tasks
        $totalTime = DB::table('task_time_logs')
            ->whereIn('task_id', $taskIds)
            ->sum('total_time');

        // Get latest time logs for each task
        $latestLogs = DB::table('task_time_logs as ttl')
            ->join(
                DB::raw('(SELECT task_id, MAX(created_at) as latest FROM task_time_logs GROUP BY task_id) as latest_logs'),
                function ($join) {
                    $join->on('ttl.task_id', '=', 'latest_logs.task_id')
                         ->on('ttl.created_at', '=', 'latest_logs.latest');
                }
            )
        ->whereIn('ttl.task_id', $taskIds)
        ->get();


        $sumTotalTime = 0;

        foreach ($latestLogs as $index => $logs) {
            $latestLog = $logs; // Get the latest log for the task
            if ($latestLog->status == 0) { // Task is in play status
                $createdAt = Carbon::parse($latestLog->created_at);
                $now = Carbon::now();
                $elapsedTime = $now->diffInSeconds($createdAt);

                if ($latestLog->total_time == '00:00:00') {
                    $sumTotalTime += $elapsedTime;
                } else {
                    $sumTotalTime += $elapsedTime + Carbon::parse($latestLog->total_time)->diffInSeconds(Carbon::parse('00:00:00'));
                }
            } else {


                $sumTotalTime += Carbon::parse($latestLog->total_time)->diffInSeconds(Carbon::parse('00:00:00'));

                // if($index == 13){
                //     dd($sumTotalTime);
                // }
                //                                     //dd($sumTotalTime);

            }
        }

        // Convert sumTotalTime from seconds to H:i:s format
$totalHours = floor($sumTotalTime / 3600);
$minutes = floor(($sumTotalTime % 3600) / 60);
$remainingSeconds = $sumTotalTime % 60;

// Format as "H:i:s"
$formattedTotalTime = sprintf('%d:%02d:%02d', $totalHours, $minutes, $remainingSeconds);



        return [
            'tasks' => $tasks,
            'totalTime' => $formattedTotalTime
        ];
    }
