<?php

namespace App\Http\Resources\Task;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class AllTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

         // Get the first and latest time logs for start_at and end_at
        // $firstLog = $this->timeLogs()->first();
        // $lastLog = $this->timeLogs()->latest()->first();

        // // Handle cases where logs might not exist
        // $startTime = $firstLog ? Carbon::parse($firstLog->start_at)->format('d/m/Y H:i:s') : '';
        // $endTime = $lastLog ? Carbon::parse($lastLog->end_at)->format('d/m/Y H:i:s') : '';

        // // Calculate the total hours in 'HH:MM' format
        // $totalHours = '';
        // if ($firstLog && $lastLog) {
        //     // If both start_at and end_at exist, calculate the total time
        //     $start = Carbon::parse($firstLog->start_at);
        //     $end = Carbon::parse($lastLog->end_at ?? now()); // Use 'now()' if end_at is null

        //     // Calculate the difference in minutes
        //     $totalMinutes = $start->diffInMinutes($end);

        //     // Convert minutes to hours and minutes
        //     $hours = floor($totalMinutes / 60);
        //     $minutes = $totalMinutes % 60;

        //     // Format as 'HH:MM'
        //     $totalHours = sprintf('%d:%02d', $hours, $minutes);
        // }

        $endTime = $this->timeLogs()->latest()->take(2)->get();

        $formattedEndTime = "";

        if(count($endTime) == 1){
            if($endTime[0]->status->value != 0){
                $formattedEndTime = Carbon::parse($endTime[0]->created_at)->format('d/m/Y H:i:s');
            }
        }else if(count($endTime) == 2){
            $formattedEndTime = Carbon::parse($endTime[0]->created_at)->format('d/m/Y H:i:s');
            if(($endTime[0]->status->value == 2 && $endTime[1]->status->value == 1) &&  $endTime[0]->total_time == $endTime[1]->total_time){
                $formattedEndTime = Carbon::parse($endTime[1]->created_at)->format('d/m/Y H:i:s');
            }
        }

                $client = DB::table('clients')->where('id', $this->client_id)->first();


        return [
            'taskId' => $this->id,
            'title' => $this->title,
            'number' => $this->number,
            'status' => $this->status,
            'accountantName' => $this->user->full_name,
            'clientName' => $client->ragione_sociale,
            'serviceCategoryName' => $this->serviceCategory->name,
            'totalHours' => $this->total_hours,
            'createdAt' => Carbon::parse($this->created_at)->format('d/m/Y'),
            'startDate' => $this->start_date?Carbon::parse($this->start_at)->format('d/m/Y'):"",
            'endDate' => $this->end_date?Carbon::parse($this->end_date)->format('d/m/Y'):"",
            "startTime"=>$this->timeLogs()->first()?Carbon::parse($this->timeLogs()->first()->created_at)->format('d/m/Y H:i:s') : "",
            "endTime"=> $formattedEndTime
        ];
    }
}
