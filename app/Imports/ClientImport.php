<?php

namespace App\Imports;

use App\Models\Client\Client;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ClientImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        //$clientAcronym = explode('-', $row['codice'])[0];
        //$serviceInternalCode = explode('-', $row['codice'])[1];
        //dd($clientAcronym);


        $client = Client::where('ragione_sociale', $row['ragione_sociale'])->first();

        if ($client) {
            return null;
        }

        return new Client([
            'ragione_sociale' => $row['ragione_sociale'],
            'iva' => $row['pivacodfi_st'],
            'cf' => $row['pivacodfi_st'],
        ]);
    }

}
