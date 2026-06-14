<?php

namespace App\Http\Controllers\Api\Private\Client;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Parameter\ParameterValue;
use Carbon\Carbon;
use Illuminate\Http\Request;


class ClientPayInstallmentEndDateController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
        // $this->middleware('permission:all_client_pay_installments', ['only' => ['index']]);
        // $this->middleware('permission:create_client_pay_installment', ['only' => ['create']]);
        // $this->middleware('permission:edit_client_pay_installment', ['only' => ['edit']]);
        // $this->middleware('permission:update_client_pay_installment', ['only' => ['update']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $client = Client::find($request->clientId);

        $startAt = Carbon::parse($request->startAt);

        // Get holidays from parameter_values where parameter_order = 11
        // Holidays are stored as d/m format (e.g., "1/3" for March 1st)
        $holidays = ParameterValue::where('parameter_order', 11)
            ->pluck('parameter_value')
            ->map(function($date) {
                // Convert d/m format to Y-m-d format with current year
                $parts = explode('/', $date);
                if (count($parts) == 2) {
                    $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    $year = Carbon::now()->year;
                    return "{$year}-{$month}-{$day}";
                }
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        // Adjust start date if it falls on weekend or holiday
        $startAt = $this->adjustForWeekendsAndHolidays($startAt, $holidays);

        $allowedDaysToPay = $client->allowed_days_to_pay ?? 0;

        $installmentEndDataAdd = ParameterValue::where('id', $request->paymentTypeId)->first();

        $installmentEndDataAddMonth = ceil($installmentEndDataAdd->description / 30);

        // Calculate end date: add months then go back one month and get last day
        // Example: 30 days (1 month): start 01/01 + 1 month - 1 month = 01/01, endOfMonth = 31/01
        // Example: 60 days (2 months): start 01/01 + 2 months - 1 month = 01/02, endOfMonth = 28/02
        $endDate = $startAt->copy()
            ->addMonths($installmentEndDataAddMonth)
            ->subMonth()
            ->endOfMonth();

        $isSpecialMonthEnd = in_array($endDate->format('m-d'), ['08-31', '12-31']);

        if ($isSpecialMonthEnd) {
            $endDate->addDays(10);
        } else {
            $endDate->addDays($allowedDaysToPay);
        }

        return response()->json([
            'startAt' => $startAt->format('Y-m-d'),
            'endAt' => $endDate->format('Y-m-d'),
        ]);

    }

    /**
     * Adjust date if it falls on weekend (Saturday/Sunday) or holiday
     * Move to next Monday or next working day
     */
    private function adjustForWeekendsAndHolidays(Carbon $date, array $holidays): Carbon
    {
        $adjustedDate = $date->copy();

        // Keep adjusting until we find a working day
        while (true) {
            $dayOfWeek = $adjustedDate->dayOfWeek;
            $dateString = $adjustedDate->format('Y-m-d');

            // Check if Saturday (6) or Sunday (0)
            if ($dayOfWeek == Carbon::SATURDAY || $dayOfWeek == Carbon::SUNDAY) {
                // Move to next Monday
                $adjustedDate->next(Carbon::MONDAY);
                continue;
            }

            // Check if it's a holiday
            if (in_array($dateString, $holidays)) {
                $adjustedDate->addDay();
                continue;
            }

            // It's a working day
            break;
        }

        return $adjustedDate;
    }


}
