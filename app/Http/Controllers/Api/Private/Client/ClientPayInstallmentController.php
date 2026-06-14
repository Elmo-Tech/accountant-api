<?php

namespace App\Http\Controllers\Api\Private\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\PayInstallment\PayInstallmentResource;
use App\Http\Resources\Client\PayInstallment\AllPayInstallmentResource;
use App\Models\Client\ClientPayInstallment;
use App\Models\Client\ClientPayInstallmentSubData;
use App\Models\Invoice\InvoiceDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ClientPayInstallmentController extends Controller
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
        $allClientPayInstallments = ClientPayInstallment::with('payInstallmentSubData')->where('client_id', $request->clientId)->get();

        return AllPayInstallmentResource::collection($allClientPayInstallments);

    }

    /**
     * Show the form for editing the specified resource.
     */

    public function edit(Request $request)
    {
        $payInstallment  =  ClientPayInstallment::find($request->payInstallmentId);

        return new PayInstallmentResource($payInstallment);


    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {

        try {
            DB::beginTransaction();

            $payInstallmentSubData = $request->all()['payInstallmentSubData'];
            $payInstallment = ClientPayInstallment::with('payInstallmentSubData')->find($request->payInstallmentId);
            $payInstallment->update([
                'client_id' => $request->clientId,
                'start_at' => $request->startAt,
                'end_at' => $request->endAt,
                'amount' => $request->amount,
                'parameter_value_id' => $request->parameterValueId,
                'payment_type_id' => $request->paymentTypeId??null
            ]);

            // $invoiceDetail = InvoiceDetail::where('invoiceable_id', $payInstallment->id)->where('invoiceable_type', ClientPayInstallment::class)->first();

            // if($invoiceDetail){
            //     $invoiceDetail->update([
            //         'price' => $payInstallment->amount,
            //         'price_after_discount' => $payInstallment->amount,
            //         'extra_price' => 0,
            //         'start_at' => $request->startAt,
            //         'end_at' => $request->endAt,
            //         'description' => $payInstallment->parameterValue->description
            //      ]);
            // }

            $payInstallment->payInstallmentSubData()->forceDelete();

            foreach($payInstallmentSubData as $payInstallmentSubDataItem){
                $item = ClientPayInstallmentSubData::create([
                    'client_pay_installment_id' => $payInstallment->id,
                    'price' => $payInstallmentSubDataItem['price'],
                    'parameter_value_id' => $payInstallmentSubDataItem['parameterValueId']??null,
                ]);

                // $invoiceDetail = InvoiceDetail::create([
                //     'invoiceable_id' => $item->id,
                //     'invoiceable_type' => ClientPayInstallmentSubData::class,
                //     'price' => $payInstallmentSubDataItem['price'],
                //     'price_after_discount' => $payInstallmentSubDataItem['price'],
                //     'extra_price' => 0,
                //     'start_at' => $request->startAt,
                //     'end_at' => $request->endAt,
                //     'description' => $payInstallment->parameterValue->description
                // ]);
            }


            DB::commit();
            return response()->json([
                 'message' => __('messages.success.updated')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }
    
        public function delete(Request $request)
    {

        try {
            DB::beginTransaction();
            $clientPayInstallment = ClientPayInstallment::find($request->payInstallmentId);
            $clientPayInstallment->payInstallmentSubData()->delete();
            $clientPayInstallment->delete();
            DB::commit();
            return response()->json([
                'message' => __('messages.success.deleted')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


}
