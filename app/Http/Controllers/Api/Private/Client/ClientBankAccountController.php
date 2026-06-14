<?php

namespace App\Http\Controllers\Api\Private\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\BankAccount\CreateClientBankAccountRequest;
use App\Http\Requests\Client\BankAccount\UpdateClientBankAccountRequest;
use App\Http\Resources\Client\ClientBankAccount\AllClientBankAccountResource;
use App\Http\Resources\Client\ClientBankAccount\ClientBankAccountResource;
use App\Http\Resources\Client\ClientContact\ClientContactResource;
use App\Services\Client\ClientBankAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ClientBankAccountController extends Controller
{
    protected $clientBankAccountService;

    public function __construct(ClientBankAccountService $clientBankAccountService)
    {
        $this->middleware('auth:api');
        $this->middleware('permission:all_client_bank_accounts', ['only' => ['index']]);
        $this->middleware('permission:create_client_bank_account', ['only' => ['create']]);
        $this->middleware('permission:edit_client_bank_account', ['only' => ['edit']]);
        $this->middleware('permission:update_client_bank_account', ['only' => ['update']]);
        $this->middleware('permission:delete_client_bank_account', ['only' => ['delete']]);
        $this->clientBankAccountService = $clientBankAccountService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allClientBankAccounts = $this->clientBankAccountService->allClientBankAccounts($request->all());

        return AllClientBankAccountResource::collection($allClientBankAccounts);

    }

    /**
     * Show the form for creating a new resource.
     */

    public function create(CreateClientBankAccountRequest $createClientBankAccountRequest)
    {

        try {
            DB::beginTransaction();

            $data = $createClientBankAccountRequest->validated();
            $clientBankAccount = $this->clientBankAccountService->createClientBankAccount($data);


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
        $clientBankAccount  =  $this->clientBankAccountService->editClientBankAccount($request->clientBankAccountId);

        return new ClientBankAccountResource($clientBankAccount);//new ClientContactResource($clientBankAccount)


    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientBankAccountRequest $updateClientBankAccountRequest)
    {

        try {
            DB::beginTransaction();
            $this->clientBankAccountService->updateClientBankAccount($updateClientBankAccountRequest->validated());
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
            $this->clientBankAccountService->deleteClientBankAccount($request->clientBankAccountId);
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
