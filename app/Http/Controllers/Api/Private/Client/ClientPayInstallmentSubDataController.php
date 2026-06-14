<?php

namespace App\Http\Controllers\Api\Private\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\PayInstallment\AllPayInstallmentSubDataResource;
use App\Http\Resources\Client\PayInstallment\PayInstallmentSubDataResource;
use App\Models\Client\ClientPayInstallmentSubData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ClientPayInstallmentSubDataController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
        // $this->middleware('permission:all_client_pay_installment_sub_data', ['only' => ['index']]);
        // $this->middleware('permission:create_client_pay_installment_sub_data', ['only' => ['create']]);
        // $this->middleware('permission:edit_client_pay_installment_sub_data', ['only' => ['edit']]);
        // $this->middleware('permission:update_client_pay_installment_sub_data', ['only' => ['update']]);
        // $this->middleware('permission:delete_client_pay_installment_sub_data', ['only' => ['delete']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allClientPayInstallments = ClientPayInstallmentSubData::where('client_pay_installment_id', $request->payInstallmentId)->get();

        return AllPayInstallmentSubDataResource::collection($allClientPayInstallments);

    }

    /**
     * Show the form for creating a new resource.
     */

    public function create(Request $request)
    {

        try {
            DB::beginTransaction();

            ClientPayInstallmentSubData::create([
                'client_pay_installment_id' => $request->payInstallmentId,
                'price' => $request->price,
                'parameter_value_id' => $request->parameterValueId??null,
                'payment_type_id' => $request->paymentTypeId??null
            ]);


            DB::commit();

            return response()->json([
                'message' => __('messages.success.created')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }

    /**
     * Show the form for editing the specified resource.
     */

    public function edit(Request $request)
    {
        $payInstallmentSubData  =  ClientPayInstallmentSubData::find($request->payInstallmentSubDataId);

        return new PayInstallmentSubDataResource($payInstallmentSubData);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {

        try {
            DB::beginTransaction();
            $payInstallmentSubData = ClientPayInstallmentSubData::find($request->payInstallmentSubDataId);
            $payInstallmentSubData->update([
                'price' => $request->price,
                'parameter_value_id' => $request->parameterValueId??null
            ]);
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
            $payInstallmentSubData = ClientPayInstallmentSubData::find($request->payInstallmentSubDataId);
            $payInstallmentSubData->delete();
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
