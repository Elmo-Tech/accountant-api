<?php

namespace App\Http\Controllers;

use App\Imports\ClientImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportClientController extends Controller
{
    public function index(Request $request)
    {
        Excel::import(new ClientImport, $request->path, 'public');
    }
}
