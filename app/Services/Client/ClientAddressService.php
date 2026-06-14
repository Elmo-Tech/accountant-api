<?php

namespace App\Services\Client;

use App\Filters\Address\FilterAddress;
use App\Models\Client\ClientAddress;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ClientAddressService{

    public function allAddresses(array $filters){

        $addresses = QueryBuilder::for(ClientAddress::class)
        ->allowedFilters([
            //AllowedFilter::custom('search', new FilterAddress()), // Add a custom search filter
            //AllowedFilter::exact('addressType', 'address_type'),
        ])
        ->when(
            $filters['clientId'] ?? null,
            fn ($query) => $query->where('client_id', $filters['clientId'])
        )
        ->get();
        return $addresses;

    }

    public function createAddress(array $addressData){

        $address = ClientAddress::create([
            'address' => $addressData['address'],
            'province' => $addressData['province']??"",
            'cap' => $addressData['cap']??"",
            'city' => $addressData['city']??"",
            'region' => $addressData['region']??"",
            'latitude' => $addressData['latitude']??0,
            'longitude' => $addressData['longitude']??0,
            'note' => $addressData['note']??"",
            'parameter_value_id' => $addressData['parameterValueId'],
            'client_id' => $addressData['clientId'],
        ]);

        return $address;

    }

    public function editAddress(string $clientAddressId){
        $address = ClientAddress::find($clientAddressId);

        return $address;

    }

    public function updateAddress(array $addressData){

        $address = ClientAddress::find($addressData['clientAddressId']);

        $address->fill([
            'address' => $addressData['address'],
            'province' => $addressData['province']??"",
            'cap' => $addressData['cap']??"",
            'city' => $addressData['city']??"",
            'region' => $addressData['region']??"",
            'latitude' => $addressData['latitude']??0,
            'longitude' => $addressData['longitude']??0,
            'note' => $addressData['note']??"",
            'parameter_value_id' => $addressData['parameterValueId'],
        ]);

        $address->save();

        return $address;

    }

    public function deleteAddress(string $clientAddressId){
        $address = ClientAddress::find($clientAddressId);
        $address->delete();
    }

}
