<?php

namespace App\Http\Controllers\Api\Private\Invoice;

use App\Models\Task\Task;
use Illuminate\Http\Request;
use App\Enums\Task\TaskStatus;
use App\Models\Invoice\Invoice;
use App\Utils\PaginateCollection;
use App\Services\Task\TaskService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Client\ClientServiceDiscount;
use App\Enums\Client\ClientServiceDiscountStatus;
use App\Enums\Client\ClientServiceDiscountType;
use App\Enums\Client\ServiceDiscountCategory;
use App\Http\Resources\Invoice\AllInvoiceCollection;
use App\Enums\ServiceCategory\ServiceCategoryAddToInvoiceStatus;
use App\Models\Client\ClientPayInstallment;
use App\Models\Client\ClientPayInstallmentSubData;
use App\Models\Invoice\InvoiceDetail;
use App\Models\ServiceCategory\ServiceCategory;
use Carbon\Carbon;

class InvoiceDetailController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->middleware('auth:api');
        //$this->middleware('permission:all_invoices', ['only' => ['index']]);
        //$this->middleware('permission:create_invoice', ['only' => ['create']]);
        // $this->middleware('permission:edit_invoice', ['only' => ['edit']]);
        // $this->middleware('permission:update_invoice', ['only' => ['update']]);
        // $this->middleware('permission:delete_invoice', ['only' => ['delete']]);
        $this->taskService = $taskService;
    }


    public function index(Request $request)
    {
        $filters = $request->filter ?? null;


    }


    public function create(Request $request)
    {
        try {
            DB::beginTransaction();

            $invoiceDetail = new InvoiceDetail([
                'invoice_id' => $request->invoiceId,
                'price' => $request->price,
                'price_after_discount' => $request->priceAfterDiscount,
                'extra_price' => 0,
                'description' => $request->description
            ]);

            $invoiceDetail->save();

            DB::commit();

            return response()->json([
                'message' => __('messages.success.created')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }

    public function edit(Request $request)
    {
        $invoiceDetail = InvoiceDetail::find($request->invoiceDetailId);

        return response()->json([
            'data' => [
                'invoiceDetailId' => $invoiceDetail->id,
                'price' => $invoiceDetail->price,
                'priceAfterDiscount' => $invoiceDetail->price_after_discount,
                'extraPrice' => $invoiceDetail->extra_price,
                'invoiceId' => $invoiceDetail->invoice_id,
                'invoiceableId' => $invoiceDetail->invoiceable_id,
                'invoiceableType' => $invoiceDetail->invoiceable_type,
                'description' => $invoiceDetail->description
            ]
        ]);
    }

    public function update(Request $request)
    {
        try {
            DB::beginTransaction();

            $invoiceDetail = InvoiceDetail::find($request->invoiceDetailId);
            $invoiceDetail->price = $request->price;
            $invoiceDetail->price_after_discount = $request->priceAfterDiscount;
            $invoiceDetail->extra_price = 0;
            $invoiceDetail->description = $request->description;
            $invoiceDetail->save();

            DB::commit();

            return response()->json([
                'message' => __('messages.success.updated')
            ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
    }


    public function delete(Request $request)
    {
        try {
            DB::beginTransaction();

            $invoiceDetail = InvoiceDetail::find($request->invoiceDetailId);

            if (!$invoiceDetail) {
                return response()->json([
                    'message' => __('messages.error.not_found')
                ], 404);
            }

            $invoiceDetail->delete();

            DB::commit();

            return response()->json([
                'message' => __('messages.success.deleted')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


}
