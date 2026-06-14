<?php

namespace App\Http\Controllers\Api\Private\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ClinetServiceDiscount\CreateClientServiceDiscountRequest;
use App\Http\Requests\Client\ClinetServiceDiscount\UpdateClientServiceDiscountRequest;
use App\Http\Resources\Client\ClientServiceDiscount\AllClientServiceDiscountResource;
use App\Http\Resources\Client\ClientServiceDiscount\ClientServiceDiscountResource;
use App\Services\Client\ClientServiceDiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ClientServiceCategoryDiscountController extends Controller
{
    protected $clientServiceDiscountService;

    public function __construct(ClientServiceDiscountService $clientServiceDiscountService)
    {
        $this->middleware('auth:api');
        $this->middleware('permission:all_client_service_discounts', ['only' => ['index']]);
        $this->middleware('permission:create_client_service_discount', ['only' => ['create']]);
        $this->middleware('permission:edit_client_service_discount', ['only' => ['edit']]);
        $this->middleware('permission:update_client_service_discount', ['only' => ['update']]);
        $this->middleware('permission:delete_client_service_discount', ['only' => ['delete']]);
        $this->clientServiceDiscountService = $clientServiceDiscountService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allClientServiceDiscounts = $this->clientServiceDiscountService->allClientServiceDiscounts($request->all());

        return AllClientServiceDiscountResource::collection($allClientServiceDiscounts);

    }

    /**
     * Show the form for creating a new resource.
     */

    public function create(CreateClientServiceDiscountRequest $createClientServiceDiscountRequest)
    {

        try {
            DB::beginTransaction();

            $data = $createClientServiceDiscountRequest->validated();
            $clientContact = $this->clientServiceDiscountService->createClientServiceDiscount($data);
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
        $clientContact  =  $this->clientServiceDiscountService->editClientServiceDiscount($request->clientServiceDiscountId);

        return new ClientServiceDiscountResource($clientContact);//new ClientServiceDiscountResource($clientContact)


    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientServiceDiscountRequest $updateClientServiceDiscountRequest)
    {

        try {
            DB::beginTransaction();
            $this->clientServiceDiscountService->updateClientServiceDiscount($updateClientServiceDiscountRequest->validated());
            DB::commit();
            return response()->json([
                 'message' => __('messages.success.updated')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(Request $request)
    {

        try {
            DB::beginTransaction();
            $this->clientServiceDiscountService->deleteClientServiceDiscount($request->clientServiceDiscountId);
            DB::commit();
            return response()->json([
                'message' => __('messages.success.deleted')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }
    public function changeShow(Request $request)
    {
        try {
            DB::beginTransaction();
            //($request->ClientDiscountId, $request->isShow)
            $this->clientServiceDiscountService->changeShow($request->ClientDiscountId, $request->isShow);
            DB::commit();
            return response()->json([
                'message' => 'تم تغيير حالة المستخدم!'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    }

}
