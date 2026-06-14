<?php

namespace App\Services\Client;

use App\Filters\Contact\FilterContact;
use App\Models\Client\ClientContact;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ClientContactService{

    public function allContacts(array $filters){

        $contacts = QueryBuilder::for(ClientContact::class)
        ->allowedFilters([
            //AllowedFilter::custom('search', new FilterContact()), // Add a custom search filter
            //AllowedFilter::exact('phoneType', 'phone_type'),
        ])
        ->when(
            $filters['clientId'] ?? null,
            fn ($query) => $query->where('client_id', $filters['clientId'])
        )
        ->get();
        return $contacts;

    }

    public function createContact(array $phoneData){

        $phone = ClientContact::create([
            'first_name' => $phoneData['firstName']??"",
            'last_name' => $phoneData['lastName']??"",
            'phone' => $phoneData['phone']??"",
            'prefix' => $phoneData['prefix']??"",
            'email' => $phoneData['email']??"",
            'note' => $phoneData['note']??"",
            'parameter_value_id' => $phoneData['parameterValueId'],
            'client_id' => $phoneData['clientId'],
            'cf' => $phoneData['cf']??"",
        ]);

        return $phone;

    }

    public function editContact(string $clientContactId){
        $contact = ClientContact::find($clientContactId);

        return $contact;

    }

    public function updateContact(array $phoneData){

        $phone = ClientContact::find($phoneData['clientContactId']);

        $phone->fill([
            'first_name' => $phoneData['firstName']??"",
            'last_name' => $phoneData['lastName']??"",
            'phone' => $phoneData['phone']??"",
            'prefix' => $phoneData['prefix']??"",
            'email' => $phoneData['email']??"",
            'note' => $phoneData['note']??"",
            'parameter_value_id' => $phoneData['parameterValueId'],
            'cf' => $phoneData['cf']??"",
        ]);

        $phone->save();

        return $phone;

    }

    public function deleteContact(string $clientContactId){
        $phone = ClientContact::find($clientContactId);
        $phone->delete();
    }

}
