<?php

namespace App\Imports;

use App\Models\Client\Client;
use App\Models\Client\ClientBankAccount;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;


class ClientBankAccountImport implements ToCollection, WithHeadingRow
{
    
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT, // IVA column
            'C' => NumberFormat::FORMAT_TEXT, // IVA column
        ];
    }
    
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            $cf  = trim($row['cf'] ?? '');
            $iva = trim($row['iva'] ?? '');
    
            if (!$cf && !$iva) {
                continue; // skip invalid rows
            }
            
            
                    $clientQuery = Client::query();
            
                    if ($iva !== '') {
                        $clientQuery->where('iva', $iva);
                    } else {
                        $clientQuery->where('cf', $cf);
                    }

            $client = $clientQuery->first();
            

            if (!$client) {
                continue; // client not found → skip
            }

            ClientBankAccount::create([
                'client_id' => $client->id,
                'iban'      => '', // always empty
                'abi'       => $row['abi'] ?? null,
                'cab'       => $row['cab'] ?? null,
                'is_main'   => 1,
            ]);
        }
    }
}
