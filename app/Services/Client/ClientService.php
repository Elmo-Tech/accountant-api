<?php

namespace App\Services\Client;

use App\Enums\Client\AddableToBulck;
use App\Enums\Client\AddableToBulk;
use App\Filters\Client\FilterClient;
use App\Models\Client\Client;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ClientService{

    public function allClients(){

        $clients = QueryBuilder::for(Client::class)
        ->allowedFilters([
            AllowedFilter::exact('clientId', 'id'), // Add a custom search filter
            AllowedFilter::custom('search', new FilterClient()), // Add a custom search filter
        ])
        ->get();
        return $clients;

    }

    public function createClient(array $clientData){

        $client = Client::create([
            'iva' => $clientData['iva']??null,
            'ragione_sociale' => $clientData['ragioneSociale']??null,
            'cf' => $clientData['cf']??null,
            'note' => $clientData['note'],
            'phone' => $clientData['phone']??"",
            'email' => $clientData['email']??"",
            'email_f24' => $clientData['emailF24']??"",
            'hours_per_month' => $clientData['hoursPerMonth']??0,
            'price' => $clientData['price']??0,
            'monthly_price' => $clientData['monthlyPrice']??0,
            'payment_type_id'=>$clientData['paymentTypeId']??null,
            'pay_steps_id'=>$clientData['payStepsId']??null,
            'payment_type_two_id'=>$clientData['paymentTypeTwoId']??null,
            'iban'=>$clientData['iban'],
            'abi'=>$clientData['abi'],
            'cab'=>$clientData['cab'],
            'addable_to_bulk_invoice'=>AddableToBulk::from($clientData['addableToBulkInvoice'])->value,
            'allowed_days_to_pay'=>$clientData['allowedDaysToPay']??0,
            'is_company'=>$clientData['isCompany']??0,
            'total_tax' => isset($clientData['totalTax'])
                ? (float) str_replace(',', '.', $clientData['totalTax'])
                : 0,
            'total_tax_description'=>$clientData['totalTaxDescription']??"",
            'sdi' =>$clientData['sdi']??"",
            'proforma' =>$clientData['proforma']??false,
            'limit_decreto' => $clientData['limitDecreto']??0,
        ]);

        return $client;

    }

    public function editClient(string $clientId){
        $client = Client::with(['addresses', 'contacts', 'payInstallments', 'payInstallments.payInstallmentSubData' , 'payInstallments.payInstallmentSubData.parameterValue', 'payInstallments.parameterValue'])->find($clientId);

        return $client;

    }

    public function updateClient(array $clientData){

        $client = Client::find($clientData['clientId']);

        $client->fill([
            'iva' => $clientData['iva'],
            'ragione_sociale' => $clientData['ragioneSociale'],
            'cf' => $clientData['cf'],
            'note' => $clientData['note'],
            'phone' => $clientData['phone']??"",
            'email' => $clientData['email']??"",
            'email_f24' => $clientData['emailF24']??"",
            'hours_per_month' => $clientData['hoursPerMonth']??0,
            'price' => $clientData['price']??0,
            'monthly_price' => $clientData['monthlyPrice']??0,
            'payment_type_id'=>$clientData['paymentTypeId']??null,
            'pay_steps_id'=>$clientData['payStepsId']??null,
            'payment_type_two_id'=>$clientData['paymentTypeTwoId']??null,
            'iban'=>$clientData['iban']??"",
            'abi'=>$clientData['abi']??"",
            'cab'=>$clientData['cab']??"",
            'addable_to_bulk_invoice'=>AddableToBulk::from($clientData['addableToBulkInvoice'])->value,
            'allowed_days_to_pay'=>$clientData['allowedDaysToPay']??0,
            'is_company'=>$clientData['isCompany']??0,
            'total_tax' => isset($clientData['totalTax'])
                ? (float) str_replace(',', '.', $clientData['totalTax'])
                : 0,
            'total_tax_description'=>$clientData['totalTaxDescription']??"",
            'sdi' =>$clientData['sdi']??"",
            'proforma' =>$clientData['proforma']??false,
            'limit_decreto' => $clientData['limitDecreto']??0,
        ]);

        $client->save();

        return $client;

    }

    public function deleteClient(string $clientId){
        $client = Client::find($clientId);
        $client->delete();
    }

}
