<?php

namespace App\Services\Client;

use App\Enums\Client\ServiceDiscountCategory;
use App\Enums\Client\ClientServiceDiscountStatus;
use App\Enums\Client\ClientServiceDiscountType;
use App\Enums\Client\ClientShowStatus;
use App\Filters\ClientServiceDiscount\FilterClientServiceDiscount;
use App\Models\Client\ClientServiceDiscount;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ClientServiceDiscountService{

    public function allClientServiceDiscounts(array $filters){

        $clientServiceDiscountes = QueryBuilder::for(ClientServiceDiscount::class)
        ->allowedFilters([
            //AllowedFilter::custom('search', new FilterClientServiceDiscount()), // Add a custom search filter
            //AllowedFilter::exact('clientServiceDiscountType', 'clientServiceDiscount_type'),
        ])
        ->when(
            $filters['clientId'] ?? null,
            fn ($query) => $query->where('client_id', $filters['clientId'])
        )
        ->get();
        return $clientServiceDiscountes;

    }

    public function createClientServiceDiscount(array $clientServiceDiscountData){

        $clientServiceDiscount = ClientServiceDiscount::create([
            'service_category_ids' => $clientServiceDiscountData['serviceCategoryIds'],
            'discount' => $clientServiceDiscountData['discount'],
            'category' => ServiceDiscountCategory::from($clientServiceDiscountData['category']),
            'type' => ClientServiceDiscountType::from($clientServiceDiscountData['type'])->value,
            'is_active' => ClientServiceDiscountStatus::from($clientServiceDiscountData['isActive'])->value,
            'is_show'=>ClientShowStatus::from($clientServiceDiscountData['isShow'])->value,
            'client_id' => $clientServiceDiscountData['clientId'],
        ]);

        return $clientServiceDiscount;

    }

    public function editClientServiceDiscount(string $clientServiceDiscountId){
        $clientServiceDiscount = ClientServiceDiscount::find($clientServiceDiscountId);

        return $clientServiceDiscount;

    }

    public function updateClientServiceDiscount(array $clientServiceDiscountData){

        $clientServiceDiscount = ClientServiceDiscount::find($clientServiceDiscountData['clientServiceDiscountId']);
        if ($clientServiceDiscount === null) {
            throw new \Exception("ClientServiceDiscount not found for ID: " . $clientServiceDiscountData['clientServiceDiscountId']);
        }
        $clientServiceDiscount->fill([
            'service_category_ids' => $clientServiceDiscountData['serviceCategoryIds'],
            'discount' => $clientServiceDiscountData['discount'],
            'category' => ServiceDiscountCategory::from($clientServiceDiscountData['category']),
            'type' => ClientServiceDiscountType::from($clientServiceDiscountData['type'])->value,
            'is_active' => ClientServiceDiscountStatus::from($clientServiceDiscountData['isActive'])->value,
            'is_show'=>ClientShowStatus::from($clientServiceDiscountData['isShow'])->value
        ]);

        $clientServiceDiscount->save();

        return $clientServiceDiscount;

    }

    public function deleteClientServiceDiscount(string $clientServiceDiscountId){
        $clientServiceDiscount = ClientServiceDiscount::find($clientServiceDiscountId);
        $clientServiceDiscount->delete();
    }
    public function changeShow(int $ClientDiscountId, int $isShow)
    {
        return ClientServiceDiscount::where('id', $ClientDiscountId)->update(['status' => ClientServiceDiscount::from($isShow)->value]);
    }

}
