<?php

namespace App\Http\Controllers\Api\Private\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ClientAddress\CreateClientAddressRequest;
use App\Http\Requests\Client\ClientAddress\UpdateClientAddressRequest;
use App\Http\Resources\Client\ClientAddress\AllClientAddressResource;
use App\Http\Resources\Client\ClientAddress\ClientAddressResource;
use App\Services\Client\ClientAddressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ClientAddressController extends Controller
{
    protected $clientAddressService;

    public function __construct(ClientAddressService $clientAddressService)
    {
        $this->middleware('auth:api');
        $this->middleware('permission:all_client_addresses', ['only' => ['index']]);
        $this->middleware('permission:create_client_address', ['only' => ['create']]);
        $this->middleware('permission:edit_client_address', ['only' => ['edit']]);
        $this->middleware('permission:update_client_address', ['only' => ['update']]);
        $this->middleware('permission:delete_client_address', ['only' => ['delete']]);
        $this->clientAddressService = $clientAddressService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allClientAddreses = $this->clientAddressService->allAddresses($request->all());

        return AllClientAddressResource::collection($allClientAddreses);

    }

    /**
     * Show the form for creating a new resource.
     */

    public function create(CreateClientAddressRequest $createClientAddressRequest)
    {

        try {
            DB::beginTransaction();

            $data = $createClientAddressRequest->validated();
            $clientAddress = $this->clientAddressService->createAddress($data);


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
        $clientAddress  =  $this->clientAddressService->editAddress($request->clientAddressId);

        return new ClientAddressResource($clientAddress);//new ClientAddressResource($clientAddress)


    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientAddressRequest $updateClientAddressRequest)
    {

        try {
            DB::beginTransaction();
            $this->clientAddressService->updateAddress($updateClientAddressRequest->validated());
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
            $this->clientAddressService->deleteAddress($request->clientAddressId);
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
