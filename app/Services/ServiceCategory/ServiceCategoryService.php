<?php

namespace App\Services\ServiceCategory;

use App\Enums\ServiceCategory\ServiceCategoryAddToInvoiceStatus;
use App\Filters\ServiceCategory\FilterServiceCategory;
use App\Models\ServiceCategory\ServiceCategory;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ServiceCategoryService{

    public function allServiceCategories(){

        $serviceCategories = QueryBuilder::for(ServiceCategory::class)
        ->allowedFilters([
            AllowedFilter::custom('search', new FilterServiceCategory()), // Add a custom search filter
        ])
        ->get();
        return $serviceCategories;

    }

    public function createServiceCategory(array $serviceCategoryData){

        $serviceCategory = ServiceCategory::create([
            'name' => $serviceCategoryData['name'],
            'code' => $serviceCategoryData['code']??null,
            'description' => $serviceCategoryData['description'],
            'service_type_id'=>$serviceCategoryData['serviceTypeId']??null,
            'add_to_invoice' => ServiceCategoryAddToInvoiceStatus::from($serviceCategoryData['addToInvoice']),
            'price' => $serviceCategoryData['price'],
            'extra_is_pricable' => $serviceCategoryData['extraIsPricable']??0,
            'extra_code' => $serviceCategoryData['extraCode']??"",
            'extra_price_description' => $serviceCategoryData['extraPriceDescription']??"",
            'extra_price' => $serviceCategoryData['extraPrice']??0
        ]);

        return $serviceCategory;

    }

    public function editServiceCategory(string $serviceCategoryId){
        $serviceCategory = ServiceCategory::find($serviceCategoryId);

        return $serviceCategory;

    }

    public function updateServiceCategory(array $serviceCategoryData){

        $serviceCategory = ServiceCategory::find($serviceCategoryData['serviceCategoryId']);

        $serviceCategory->fill([
            'name' => $serviceCategoryData['name'],
            'code' => $serviceCategoryData['code']??null,
            'description' => $serviceCategoryData['description'],
            'service_type_id'=>$serviceCategoryData['serviceTypeId']??null,
            'add_to_invoice' => ServiceCategoryAddToInvoiceStatus::from($serviceCategoryData['addToInvoice']),
            'price' => $serviceCategoryData['price'],
            'extra_is_pricable' => $serviceCategoryData['extraIsPricable']??0,
            'extra_code' => $serviceCategoryData['extraCode']??"",
            'extra_price_description' => $serviceCategoryData['extraPriceDescription']??"",
            'extra_price' => $serviceCategoryData['extraPrice']??0
        ]);

        $serviceCategory->save();

        return $serviceCategory;

    }

    public function deleteServiceCategory(string $serviceCategoryId){
        $serviceCategory = ServiceCategory::find($serviceCategoryId);
        $serviceCategory->delete();
    }

}
