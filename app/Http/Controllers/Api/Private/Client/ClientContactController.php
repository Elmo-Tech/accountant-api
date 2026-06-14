<?php

namespace App\Http\Controllers\Api\Private\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ClientContact\CreateClientContactRequest;
use App\Http\Requests\Client\ClientContact\UpdateClientContactRequest;
use App\Http\Resources\Client\ClientContact\AllClientContactResource;
use App\Http\Resources\Client\ClientContact\ClientContactResource;
use App\Services\Client\ClientContactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ClientContactController extends Controller
{
    protected $clientContactService;

    public function __construct(ClientContactService $clientContactService)
    {
        $this->middleware('auth:api');
        $this->middleware('permission:all_client_contacts', ['only' => ['index']]);
        $this->middleware('permission:create_client_contact', ['only' => ['create']]);
        $this->middleware('permission:edit_client_contact', ['only' => ['edit']]);
        $this->middleware('permission:update_client_contact', ['only' => ['update']]);
        $this->middleware('permission:delete_client_contact', ['only' => ['delete']]);
        $this->clientContactService = $clientContactService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allClientContacts = $this->clientContactService->allContacts($request->all());

        return AllClientContactResource::collection($allClientContacts);

    }

    /**
     * Show the form for creating a new resource.
     */

    public function create(CreateClientContactRequest $createClientContactRequest)
    {

        try {
            DB::beginTransaction();

            $data = $createClientContactRequest->validated();
            $clientContact = $this->clientContactService->createContact($data);


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
        $clientContact  =  $this->clientContactService->editContact($request->clientContactId);

        return new ClientContactResource($clientContact);//new ClientContactResource($clientContact)


    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientContactRequest $updateClientContactRequest)
    {

        try {
            DB::beginTransaction();
            $this->clientContactService->updateContact($updateClientContactRequest->validated());
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
            $this->clientContactService->deleteContact($request->clientContactId);
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
