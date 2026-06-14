<?php

namespace App\Imports;

use App\Models\ServiceCategory\ServiceCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ServiceCategoryImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {

        return new ServiceCategory([
            'name' => $row['name'],
            'description' => null,
            'price' => $row['price'],
            'add_to_invoice' => 0,
            'service_type_id' => null
        ]);
    }

}
