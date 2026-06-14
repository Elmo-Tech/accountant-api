<?php


namespace App\Services\Select;

use App\Services\Select\Invoice\InvoiceSelectService;
use App\Services\Select\Parameter\ParameterSelectService;

class SelectService
{
    public function getSelects(String $selects)
    {
        $selectsArr = explode(',', $selects);
        $selectData = [];

        foreach ($selectsArr as $select) {


            $selectServiceData = $this->resolveSelectService($select);

            //dd($selectServiceData);
            if ($selectServiceData) {
                [$method, $selectServiceClass, $paramValue] = $selectServiceData;

                $selectService = new $selectServiceClass(); // Instantiate the service class
                if(explode('=', $select)[0] == 'parameters'){
                    $selectData[] = [
                        'label' => explode('=', $select)[0] . "" . explode('=', $select)[1],
                        'options' => $selectService->$method($paramValue)
                    ];
                }elseif(explode('=', $select)[0] == 'claimTextSelect'){

                    $selectData[] = [
                        'label' => explode('=', $select)[0] . "" . str_replace(['{', '}'], '', explode('=', $select)[1]),
                        'options' => $selectService->$method($paramValue)
                    ];
                }elseif (isset($paramValue)) {
                    $selectData[] = [
                        'label' => explode("=", $select)[0],
                        'options' => $selectService->$method($paramValue)
                    ];
                }else {
                    $selectData[] = [
                        'label' => $select,
                        'options' => $selectService->$method()
                    ];
                }
            }
        }

        return $selectData;
    }

    private function resolveSelectService($select)
    {
        $selectServiceMap = [
            'users' => ['getAllUsers', UserSelectService::class],
            'clients' => ['getAllClients', ClientSelectService::class],
            'roles' => ['getAllRoles', RoleSelectService::class],
            'permissions' => ['getAllPermissions', PermissionSelectService::class],
            'parameters' => ['getAllParameters', ParameterSelectService::class],
            'serviceCategories' => ['getAllServiceCategories', ServiceCategorySelectService::class],
            'bankAccounts' => ['getAllBankAccounts', ParameterSelectService::class],
            'invoices' => ['getAllInvoices', InvoiceSelectService::class],
        ];

        $paramValue = null; // Initialize paramValue

        if (preg_match('/(\w+)=(?:(\b[0-9A-Fa-f\-]{36}\b)|\{([a-zA-Z]+)\}|(\d+))/', $select, $matches)) {
            $select = $matches[1];
            $paramValue = !empty($matches[2]) ? $matches[2] : (!empty($matches[3]) ? $matches[3] : $matches[4]);
        }

        if (array_key_exists($select, $selectServiceMap)) {
            $serviceData = $selectServiceMap[$select];
            // Ensure $paramValue is set as the third element
            $serviceData[] = $paramValue;
            return $serviceData;
        }

        // If no matching service is found, you can handle it accordingly (e.g., return null or throw an exception)
        return null;
    }

}
