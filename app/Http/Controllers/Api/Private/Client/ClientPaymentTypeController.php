<?php

namespace App\Http\Controllers\Api\Private\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\CreateClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\Client\AllClientCollection;
use App\Http\Resources\Client\ClientResource;
use App\Models\Client\Client;
use App\Models\Parameter\ParameterValue;
use App\Utils\PaginateCollection;
use App\Services\Client\ClientService;
use App\Services\Client\ClientAddressService;
use App\Services\Client\ClientContactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ClientPaymentTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        //$this->middleware('permission:client_payment_type', ['only' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientsPaymentTypeData = Client::whereIn('id', $request->clientIds)->select('id', 'payment_type_two_id', 'ragione_sociale')->get();
        $clientsPaymentType = [];
        foreach ($clientsPaymentTypeData as $clientPaymentTypeData) {
            $paymentDescription = ParameterValue::find($clientPaymentTypeData->payment_type_two_id);
            $clientsPaymentType[] = [
                'clientId' => $clientPaymentTypeData->id,
                'paymentTypeTwoId' => $clientPaymentTypeData->payment_type_two_id??"",
                'ragioneSociale' => $clientPaymentTypeData->ragione_sociale,
                'paymentDescription' => $paymentDescription->description??""
            ];
        }
        return response()->json([
            'data' => [
                'clientsPaymentType' => $clientsPaymentType
                ]
            ]);
    }

}
