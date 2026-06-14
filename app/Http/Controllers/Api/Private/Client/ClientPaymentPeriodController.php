<?php

namespace App\Http\Controllers\Api\Private\Client;

use App\Http\Controllers\Controller;

use App\Models\Client\Client;
use App\Models\Parameter\ParameterValue;
use Carbon\Carbon;
use Illuminate\Http\Request;


class ClientPaymentPeriodController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        //$this->middleware('permission:client_payment_period', ['only' => ['index']]);
        // $this->middleware('permission:create_client', ['only' => ['create']]);
        // $this->middleware('permission:edit_client', ['only' => ['edit']]);
        // $this->middleware('permission:update_client', ['only' => ['update']]);
        // $this->middleware('permission:delete_client', ['only' => ['delete']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientsPaymentPeriod = ParameterValue::find($request->paymentPeriodId);

        $paymentDate = Carbon::now(); // Set to the current date

        $client = Client::find($request->clientId);

        $allowedDaysToPay = $client?->allowed_days_to_pay ?? 0; // Fetch from the client table

        $startAt = $request->startAt ?? Carbon::now();



        if ($clientsPaymentPeriod && (int) $clientsPaymentPeriod->description > 0) {

            $numberOfMonthsToAdd = ceil((int) $clientsPaymentPeriod->description / 30);

            // Calculate end date: add months then go back one month and get last day
            // Example: 30 days (1 month): start 01/01 + 1 month - 1 month = 01/01, endOfMonth = 31/01
            // Example: 60 days (2 months): start 01/01 + 2 months - 1 month = 01/02, endOfMonth = 28/02
            $paymentDate = Carbon::parse($startAt)->copy()
                ->addMonths($numberOfMonthsToAdd)
                ->subMonth()
                ->endOfMonth();

            $isSpecialMonthEnd = in_array($paymentDate->format('m-d'), ['08-31', '12-31']);

            if ($isSpecialMonthEnd) {
                $paymentDate->addDays(10);
            } else {
                $paymentDate->addDays($allowedDaysToPay);
            }
        }

        return response()->json([
            'data' => [
                'paymentDate' => $paymentDate->format('Y-m-d')
            ]
        ]);
    }

}
