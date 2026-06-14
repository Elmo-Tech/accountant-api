<?php

use App\Http\Controllers\Api\Private\Client\ClientPaymentTypeController;
use App\Http\Controllers\Api\Private\Task\ChangeTaskTimeLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Parameter\ParameterValue;
use App\Http\Controllers\Api\Public\Auth\AuthController;
use App\Http\Controllers\Api\Private\Task\TaskController;
use App\Http\Controllers\Api\Private\User\UserController;
use App\Http\Controllers\Api\Private\Client\ClientController;
use App\Http\Controllers\Api\Private\Select\SelectController;
use App\Http\Controllers\Api\Private\Reports\ReportController;
use App\Http\Controllers\Api\Private\Task\AdminTaskController;
use App\Http\Controllers\Api\Private\Invoice\InvoiceController;
use App\Http\Controllers\Api\Private\Invoice\PayInvoiceController;
use App\Http\Controllers\Api\Private\Invoice\PaidInvoicesTotalController;
use App\Http\Controllers\Api\Private\Invoice\InvoiceListByStatusController;
use App\Http\Controllers\Api\Private\Task\ActiveTaskController;
use App\Http\Controllers\Api\Private\Task\TaskTimeLogController;
use App\Http\Controllers\Api\Private\Parameter\ParameterController;
use App\Http\Controllers\Api\Private\Client\ClientAddressController;
use App\Http\Controllers\Api\Private\Client\ClientBankAccountController;
use App\Http\Controllers\Api\Private\Client\ClientContactController;
use App\Http\Controllers\Api\Private\Client\ClientPayInstallmentController;
use App\Http\Controllers\Api\Private\Client\ClientPayInstallmentDividerController;
use App\Http\Controllers\Api\Private\Client\ClientPayInstallmentEndDateController;
use App\Http\Controllers\Api\Private\Client\ClientPayInstallmentSubDataController;
use App\Http\Controllers\Api\Private\Client\ClientPaymentPeriodController;
use App\Http\Controllers\Api\Private\Parameter\ParameterValueController;
use App\Http\Controllers\Api\Private\ServiceCategory\ServiceCategoryController;
use App\Http\Controllers\Api\Private\Client\ClientServiceCategoryDiscountController;
use App\Http\Controllers\Api\Private\Invoice\AssignedInvoiceController;
use App\Http\Controllers\Api\Private\Invoice\ClientEmailController;
use App\Http\Controllers\Api\Private\Invoice\ImageToExcelController;
use App\Http\Controllers\Api\Private\Invoice\InvoiceDetailController;
use App\Http\Controllers\Api\Private\Invoice\RecurringInvoiceController;
use App\Http\Controllers\Api\Private\Invoice\SendEmailController;
use App\Http\Controllers\Api\Private\Invoice\SendInvoiceController;
use App\Http\Controllers\Api\Private\Reports\InvoiceCsvReportController;
use App\Http\Controllers\Api\Private\Reports\InvoicePdfReportController;
use App\Http\Controllers\Api\Private\Reports\InvoiceReportExportController;
use App\Http\Controllers\Api\Private\Task\AdminTaskExportController;
use App\Http\Controllers\ImportClientController;
use App\Http\Controllers\ImportServiceCategoryController;
use App\Http\Controllers\Api\Private\Client\ClientPaymentExportController;
use App\Http\Controllers\Api\Private\Client\ImportClientBankAccountController;
use App\Http\Controllers\Api\Private\Invoice\RecurringInvoiceToAllClientsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1/auth')->group(function(){
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::prefix('v1/users')->group(function(){
    Route::get('', [UserController::class, 'index']);
    Route::post('create', [UserController::class, 'create']);
    Route::get('edit', [UserController::class, 'edit']);
    Route::put('update', [UserController::class, 'update']);
    Route::delete('delete', [UserController::class, 'delete']);
    Route::put('change-status', [UserController::class, 'changeStatus']);
});

Route::prefix('v1/clients')->group(function(){
    Route::get('', [ClientController::class, 'index']);
    Route::post('create', [ClientController::class, 'create']);
    Route::get('edit', [ClientController::class, 'edit']);
    Route::put('update', [ClientController::class, 'update']);
    Route::delete('delete', [ClientController::class, 'delete']);
});

Route::prefix('v1/client-addresses')->group(function(){
    Route::get('', [ClientAddressController::class, 'index']);
    Route::post('create', [ClientAddressController::class, 'create']);
    Route::get('edit', [ClientAddressController::class, 'edit']);
    Route::put('update', [ClientAddressController::class, 'update']);
    Route::delete('delete', [ClientAddressController::class, 'delete']);
});

Route::prefix('v1/client-contacts')->group(function(){
    Route::get('', [ClientContactController::class, 'index']);
    Route::post('create', [ClientContactController::class, 'create']);
    Route::get('edit', [ClientContactController::class, 'edit']);
    Route::put('update', [ClientContactController::class, 'update']);
    Route::delete('delete', [ClientContactController::class, 'delete']);
});

Route::prefix('v1/client-bank-accounts')->group(function(){
    Route::get('', [ClientBankAccountController::class, 'index']);
    Route::post('create', [ClientBankAccountController::class, 'create']);
    Route::get('edit', [ClientBankAccountController::class, 'edit']);
    Route::put('update', [ClientBankAccountController::class, 'update']);
    Route::delete('delete', [ClientBankAccountController::class, 'delete']);
});

Route::prefix('v1/client-pay-installments')->group(function(){
    Route::get('', [ClientPayInstallmentController::class, 'index']);
    Route::post('create', [ClientPayInstallmentController::class, 'create']);
    Route::get('edit', [ClientPayInstallmentController::class, 'edit']);
    Route::put('update', [ClientPayInstallmentController::class, 'update']);
    Route::delete('delete', [ClientPayInstallmentController::class, 'delete']);
});

Route::prefix('v1/client-pay-installment-sub-data')->group(function(){
    Route::get('', [ClientPayInstallmentSubDataController::class, 'index']);
    Route::post('create', [ClientPayInstallmentSubDataController::class, 'create']);
    Route::get('edit', [ClientPayInstallmentSubDataController::class, 'edit']);
    Route::put('update', [ClientPayInstallmentSubDataController::class, 'update']);
    Route::put('delete', [ClientPayInstallmentSubDataController::class, 'delete']);
});

Route::prefix('v1/client-pay-installment-divider')->group(function(){
    Route::get('', [ClientPayInstallmentDividerController::class, 'index']);
});

Route::prefix('v1/service-categories')->group(function(){
    Route::get('', [ServiceCategoryController::class, 'index']);
    Route::post('create', [ServiceCategoryController::class, 'create']);
    Route::get('edit', [ServiceCategoryController::class, 'edit']);
    Route::put('update', [ServiceCategoryController::class, 'update']);
    Route::delete('delete', [ServiceCategoryController::class, 'delete']);
});

Route::prefix('v1/client-service-discounts')->group(function(){
    Route::post('changeShow', [ClientServiceCategoryDiscountController::class, 'changeShow']);
    Route::get('', [ClientServiceCategoryDiscountController::class, 'index']);
    Route::post('create', [ClientServiceCategoryDiscountController::class, 'create']);
    Route::get('edit', [ClientServiceCategoryDiscountController::class, 'edit']);
    Route::put('update', [ClientServiceCategoryDiscountController::class, 'update']);
    Route::delete('delete', [ClientServiceCategoryDiscountController::class, 'delete']);
});

Route::prefix('v1/tasks')->group(function(){
    Route::get('', [TaskController::class, 'index']);
    Route::post('create', [TaskController::class, 'create']);
    Route::get('edit', [TaskController::class, 'edit']);
    Route::put('update', [TaskController::class, 'update']);
    Route::delete('delete', [TaskController::class, 'delete']);
    Route::put('change-status', [TaskController::class, 'changeStatus']);
});

Route::prefix('v1/admin-tasks')->group(function(){
    Route::get('', [AdminTaskController::class, 'index']);
});

Route::prefix('v1/task-time-logs')->group(function(){
    Route::get('', [TaskTimeLogController::class, 'index']);
    Route::post('create', [TaskTimeLogController::class, 'create']);
    Route::get('edit', [TaskTimeLogController::class, 'edit']);
    Route::put('update', [TaskTimeLogController::class, 'update']);
    Route::delete('delete', [TaskTimeLogController::class, 'delete']);
});

Route::prefix('v1/task-time-logs/change-time')->group(function(){
    Route::put('', [ChangeTaskTimeLogController::class, 'update']);
});


Route::prefix('v1/user-active-tasks')->group(function(){
    Route::get('', [ActiveTaskController::class, 'index']);
    Route::put('update', [ActiveTaskController::class, 'update']);
});

Route::prefix('v1/invoices')->group(function(){
    Route::get('', [InvoiceController::class, 'index']);
    Route::post('create', [InvoiceController::class, 'create']);
    Route::get('edit', [InvoiceController::class, 'edit']);
    Route::put('update', [InvoiceController::class, 'update']);
    Route::post('add-tasks', [InvoiceController::class, 'addTasksToInvoice']);
    Route::post('generate-xml-number', [InvoiceController::class, 'generateXmlNumber']);
    Route::put('pay-invoice', [PayInvoiceController::class, 'update']);
});

Route::get('v1/invoice-income-stats', PaidInvoicesTotalController::class);
Route::get('v1/invoice-income-items', InvoiceListByStatusController::class);


Route::prefix('v1/invoice-details')->group(function(){
    Route::get('', [InvoiceDetailController::class, 'index']);
    Route::post('create', [InvoiceDetailController::class, 'create']);
    Route::get('edit', [InvoiceDetailController::class, 'edit']);
    Route::put('update', [InvoiceDetailController::class, 'update']);
    Route::delete('delete', [InvoiceDetailController::class, 'delete']);
});



Route::prefix('v1/recurring-invoices')->group(function(){
    Route::post('create', [RecurringInvoiceController::class, 'create']);
});

Route::prefix('v1/parameters')->group(function(){
    Route::get('', [ParameterValueController::class, 'index']);
    Route::post('create', [ParameterValueController::class, 'create']);
    Route::get('edit', [ParameterValueController::class, 'edit']);
    Route::put('update', [ParameterValueController::class, 'update']);
    Route::delete('delete', [ParameterValueController::class, 'delete']);
});
Route::prefix('v1/reports')->group(function(){
Route::get('', ReportController::class);
});
Route::prefix('v1/selects')->group(function(){
    Route::get('', [SelectController::class, 'getSelects']);
    Route::get('invoices', [SelectController::class, 'getAllInvoices']);
});

Route::prefix('v1/import-clients')->group(function(){
    Route::post('', [ImportClientController::class, 'index']);
});

Route::prefix('v1/import-service-categories')->group(function(){
    Route::post('', [ImportServiceCategoryController::class, 'index']);
});

Route::prefix('v1/client-payment-type')->group(function(){
    Route::get('', [ClientPaymentTypeController::class, 'index']);
});

Route::prefix('v1/client-payment-period')->group(function(){
    Route::get('', [ClientPaymentPeriodController::class, 'index']);
});

Route::prefix('v1/export-invoice-report')->group(function(){
    Route::get('', [InvoiceReportExportController::class, 'index']);
});

Route::prefix('v1/export-client-payment')->group(function(){
    Route::get('', [ClientPaymentExportController::class, 'index']);
});

Route::prefix('v1/image-to-excel')->group(function(){
    Route::post('', [ImageToExcelController::class, 'index']);
});

Route::prefix('v1/send-invoice-email')->group(function(){
    Route::post('', [SendEmailController::class, 'index']);
});
Route::prefix('v1/client-email')->group(function(){
    Route::get('edit', [ClientEmailController::class, 'edit']);
});

Route::prefix('v1/send-uploaded-invoice')->group(function(){
    Route::post('', [SendInvoiceController::class, 'index']);
});

Route::prefix('v1/admin-ticket-export')->group(function(){
    Route::get('', [AdminTaskExportController::class, 'index']);
});

Route::prefix('v1/installment-end-at')->group(function(){
    Route::get('', [ClientPayInstallmentEndDateController::class, 'index']);
});



Route::prefix('v1/import-client-bank-accounts')->group(function(){
    Route::post('', [ImportClientBankAccountController::class, 'import']);
});


Route::prefix('v1/clients/recurring-all-invoices')->group(function(){
    Route::post('create', [RecurringInvoiceToAllClientsController::class, 'create']);
});

