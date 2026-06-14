<?php

namespace App\Http\Controllers\Api\Private\Client;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Parameter\ParameterValue;
use Illuminate\Http\Request;


class ClientPayInstallmentDividerController extends Controller
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
    // public function index(Request $request)
    // {
    //     $installmentNumbers = ParameterValue::where('id', $request->payStepsId)->pluck('description')->first();

    //     $client = Client::find($request->clientId);

    //     $clientEndDataAdd = 0;

    //     $clientEndDataAddMonth = 0;

    //     $installmentAmount = 0;

    //     $allowedDaysToPay = 0; // Fetch from the client table



    //     if ($client) {

    //         $client->price = $request->price;

    //         $client->payment_type_id = $request->paymentTypeId??null;

    //         $client->save();

    //         $clientEndDataAdd = ParameterValue::where('id', $client->payment_type_id)->first();

    //         $clientEndDataAddMonth = ceil($clientEndDataAdd->description / 30);

    //         $installmentAmount = $client->price / $installmentNumbers;

    //         $allowedDaysToPay = $client->allowed_days_to_pay ?? 0; // Fetch from the client table

    //     } else {


    //         $clientEndDataAdd = ParameterValue::where('id', $request->paymentTypeId)->first();

    //         $clientEndDataAddMonth = ceil($clientEndDataAdd->description / 30);

    //         $installmentAmount = $request->price / $installmentNumbers;


    //     }



    //     $installmentsData = [];
    //     $currentDate = now()->startOfMonth(); // First day of the current month


    //     foreach ( range(1, $installmentNumbers) as $installmentNumber ) {

    //         $endDate = $currentDate->copy()->addMonths($clientEndDataAddMonth)->subDays(1);

    //         $isSpecialMonthEnd = in_array($endDate->format('m-d'), ['08-31', '12-31']);

    //         if ($isSpecialMonthEnd) {
    //             $endDate->addDays(10);
    //         } else {
    //             $endDate->addDays($allowedDaysToPay);
    //         }

    //         $installmentsData[] = [
    //             'startAt' => $currentDate->format('Y-m-d'),
    //             'endAt' => $endDate->format('Y-m-d'),
    //             'parameterValueName' => '',
    //             'amount' => round($installmentAmount, 2),
    //             'paymentTypeId' => $client?->payment_type_id ?? $request->paymentTypeId ?? "",
    //             'payInstallmentSubData' => []
    //         ];

    //         $currentDate->addMonth(); // Move to the next month
    //     }

    //     return response()->json([
    //         'data' => [
    //             'payInstallments' => $installmentsData
    //         ]
    //     ]);

    // }

    public function index(Request $request)
{
    // عدد الأقساط
    $installmentNumbers = ParameterValue::where('id', $request->payStepsId)
        ->pluck('description')
        ->first();

    // جلب العميل إذا موجود
    $client = Client::find($request->clientId);

    $clientEndDataAddMonth = 0;
    $installmentAmount = 0;
    $allowedDaysToPay = 0;

    // إذا العميل موجود
    if ($client) {
        $client->price = $request->price;
        $client->payment_type_id = $request->paymentTypeId ?? null;
        $client->save();

        $clientEndDataAdd = ParameterValue::where('id', $client->payment_type_id)->first();
        $clientEndDataAddMonth = ceil($clientEndDataAdd->description / 30);

        $installmentAmount = $client->price / $installmentNumbers;
        $allowedDaysToPay = $client->allowed_days_to_pay ?? 0;

    } else {
        $clientEndDataAdd = ParameterValue::where('id', $request->paymentTypeId)->first();
        $clientEndDataAddMonth = ceil($clientEndDataAdd->description / 30);

        $installmentAmount = $request->price / $installmentNumbers;
    }

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
                $year = \Carbon\Carbon::now()->year;
                return "{$year}-{$month}-{$day}";
            }
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        })
        ->toArray();

    $installmentsData = [];

    // Calculate months between installments
    $monthsBetweenInstallments = 12 / $installmentNumbers;

    // Calculate first installment month (counting backwards from December)
    // For 2 installments: 12 - (1 * 6) = 6 (June)
    // For 3 installments: 12 - (2 * 4) = 4 (April)
    $firstInstallmentMonth = 12 - (($installmentNumbers - 1) * $monthsBetweenInstallments);

    for ($i = 0; $i < $installmentNumbers; $i++) {

        // Calculate which month this installment should start
        $monthsToAdd = $firstInstallmentMonth - 1 + ($i * $monthsBetweenInstallments);

        $startDate = \Carbon\Carbon::now()->startOfYear()->addMonths($monthsToAdd)->day(1);

        // Adjust start date if it falls on weekend or holiday
        $startDate = $this->adjustForWeekendsAndHolidays($startDate, $holidays);

        // Calculate end date: add months then go back one month and get last day
        // Example: 30 days (1 month): start 01/01 + 1 month - 1 month = 01/01, endOfMonth = 31/01
        // Example: 60 days (2 months): start 01/01 + 2 months - 1 month = 01/02, endOfMonth = 28/02
        $endDate = $startDate->copy()
            ->addMonths($clientEndDataAddMonth)
            ->subMonth()
            ->endOfMonth();

        // معالجة شهور خاصة
        $isSpecialMonthEnd = in_array($endDate->format('m-d'), ['08-31', '12-31']);
        if ($isSpecialMonthEnd) {
            $endDate->addDays(10);
        } else {
            $endDate->addDays($allowedDaysToPay);
        }

        $installmentsData[] = [
            'startAt' => $startDate->format('Y-m-d'),
            'endAt' => $endDate->format('Y-m-d'),
            'parameterValueName' => '',
            'amount' => round($installmentAmount, 2),
            'paymentTypeId' => $client?->payment_type_id ?? $request->paymentTypeId ?? "",
            'payInstallmentSubData' => []
        ];
    }

    return response()->json([
        'data' => [
            'payInstallments' => $installmentsData
        ]
    ]);
}

    /**
     * Adjust date if it falls on weekend (Saturday/Sunday) or holiday
     * Move to next Monday or next working day
     */
    private function adjustForWeekendsAndHolidays(\Carbon\Carbon $date, array $holidays): \Carbon\Carbon
    {
        $adjustedDate = $date->copy();

        // Keep adjusting until we find a working day
        while (true) {
            $dayOfWeek = $adjustedDate->dayOfWeek;
            $dateString = $adjustedDate->format('Y-m-d');

            // Check if Saturday (6) or Sunday (0)
            if ($dayOfWeek == \Carbon\Carbon::SATURDAY || $dayOfWeek == \Carbon\Carbon::SUNDAY) {
                // Move to next Monday
                $adjustedDate->next(\Carbon\Carbon::MONDAY);
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
