<?php

namespace App\Http\Controllers\Api\Private\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\ClientBankAccountImport;
use Maatwebsite\Excel\Facades\Excel;


class ImportClientBankAccountController extends Controller
{

    protected $clientBankAccountService;


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new ClientBankAccountImport, $request->file('file'));

        return response()->json([
            'message' => 'Bank accounts imported successfully',
        ]);
    }

}
