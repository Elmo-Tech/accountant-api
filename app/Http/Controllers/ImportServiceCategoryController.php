<?php

namespace App\Http\Controllers;

use App\Imports\ServiceCategoryImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportServiceCategoryController extends Controller
{
    public function index(Request $request)
    {
        Excel::import(new ServiceCategoryImport, $request->path, 'public');
    }
}
