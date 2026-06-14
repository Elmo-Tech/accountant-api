<?php

namespace App\Http\Resources\Task;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $latestLog = $this->timeLogs()->latest()->first();
        return [
            'taskId' => $this->id,
            'title' => $this->title,
            'number' => $this->number,
            'status' => $this->status,
            'userId' => $this->user_id,
            'clientId' => $this->client_id,
            'serviceCategoryId' => $this->service_category_id,
            'description' => $this->description??"",
            'invoiceId' => $this->invoice_id??"",
            'timeLogStatus' => $this->timeLogStatus,
            'currentTime' => $this->current_time,
            'latestTimeLogId' => $latestLog?->id??"",
            'connectionTypeId' => $this->connection_type_id,
            //'startDate' => $this->start_date??"",
            //'endDate' => $this->end_date??"",

            // "Price"  => $this->price ??"",
            // "priceAfterDiscount" => $this->price_after_discount ??"",
            //'startTime' => count($this->timeLogs)?Carbon::parse($this->timeLogs()->first()->start_at)->format(format: 'd/m/Y H:i:s'):Carbon::now()->format('d/m/Y H:i:s'),
            // 'endTime' => count($this->timeLogs)?Carbon::parse($this->timeLogs()->latest()->first()->end_at)->format(format:'d/m/Y H:i:s'):Carbon::now()->format('d/m/Y H:i:s'),
            //'endTime' => $latestLog && $latestLog->end_at ? Carbon::parse($latestLog->end_at)->format('d/m/Y H:i:s') : "",

        ];

    }
}
