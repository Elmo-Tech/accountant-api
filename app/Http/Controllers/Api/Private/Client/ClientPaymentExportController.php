<?php

namespace App\Http\Controllers\Api\Private\Client;

use App\Http\Controllers\Controller;
use App\Services\Task\ExportTaskService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\DB;

class ClientPaymentExportController extends Controller
{
    protected $taskService;

    public function __construct(ExportTaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function index(Request $request)
    {
        $request->validate([
            'clientId' => 'nullable|integer',
            'startAt' => 'nullable|date',
            'endAt' => 'nullable|date',
            'filter' => 'nullable|array',
            'filter.clientId' => 'nullable|integer',
            'filter.startAt' => 'nullable|date',
            'filter.endAt' => 'nullable|date',
        ]);
        $filters = array_merge($request->only(['clientId', 'startAt', 'endAt']), $request->input('filter', []));
        $spreadsheet = new Spreadsheet();

        // 1. جلب قاموس الفئات (Macro Servizi) - نفترض أن parameter_order = 12 هي الفئات
        $categoryNames = DB::table('parameter_values')
            ->where('parameter_order', 12)
            ->pluck('parameter_value', 'id')
            ->toArray();

        // 2. بناء مصدر البيانات الموحد (Flat Data Structure)
        // هذا الجزء يضمن أن ما يظهر في الصفحة الأولى هو "البذرة" لكل العمليات الحسابية اللاحقة
        $rawInstallments = DB::table('client_pay_installments as cpi')
            ->whereNull('cpi.deleted_at')
            ->join('clients as c', 'c.id', '=', 'cpi.client_id')
            ->whereNull('c.deleted_at')
            ->leftJoin('parameter_values as pv', 'pv.id', '=', 'cpi.parameter_value_id')
            ->whereIn('pv.parameter_id', [8, 9])
            ->when(isset($filters['clientId']), fn ($q) => $q->where('cpi.client_id', $filters['clientId']))
            ->when(isset($filters['startAt']), fn ($q) => $q->whereDate('cpi.end_at', '>=', Carbon::parse($filters['startAt'])->format('Y-m-d')))
            ->when(isset($filters['endAt']), fn ($q) => $q->whereDate('cpi.end_at', '<=', Carbon::parse($filters['endAt'])->format('Y-m-d')))
            ->select(
                'cpi.id',
                'cpi.client_id',
                'cpi.end_at',
                'c.ragione_sociale',
                'pv.id as pv_id',
                'pv.parameter_value as pv_name',
                'pv.description as description',
                'pv.description2 as category_id', // هذا الحقل يجب أن يحتوي على ID الفئة
                'cpi.amount'
            )
            ->get();

        $allTransactions = collect();

        foreach ($rawInstallments as $inst) {
            // إضافة الحركة الأساسية
            $allTransactions->push([
                'source_type' => \App\Models\Client\ClientPayInstallment::class,
                'source_id' => $inst->id,
                'client_id'       => $inst->client_id,
                'ragione_sociale' => $inst->ragione_sociale,
                'date'            => $inst->end_at ? Carbon::parse($inst->end_at)->format('d/m/Y') : '',
                'description'     => $inst->description,
                'pv_id'           => $inst->pv_id,
                'pv_name'         => $inst->pv_name,
                'cat_name'        => $categoryNames[$inst->category_id] ?? 'Senza Categoria',
                'amount'          => (float)($inst->amount ?? 0)
            ]);

            // إضافة الحركات الفرعية (Sub Data)
            $subs = DB::table('client_pay_installment_sub_data as sub')
                ->where('sub.client_pay_installment_id', $inst->id)
                ->whereNull('sub.deleted_at')
                ->leftJoin('parameter_values as pv_sub', 'pv_sub.id', '=', 'sub.parameter_value_id')
                ->select(
                    'sub.id as source_id',
                    'pv_sub.id as pv_id',
                    'pv_sub.parameter_value as pv_name',
                    'pv_sub.description',
                    'pv_sub.description2 as category_id',
                    'sub.price'
                )
                ->get();

            foreach ($subs as $sub) {
                $allTransactions->push([
                    'source_type' => \App\Models\Client\ClientPayInstallmentSubData::class,
                    'source_id' => $sub->source_id,
                    'client_id'       => $inst->client_id,
                    'ragione_sociale' => $inst->ragione_sociale,
                    'date'            => $inst->end_at ? Carbon::parse($inst->end_at)->format('d/m/Y') : '',
                    'description'     => $sub->description,
                    'pv_id'           => $sub->pv_id ?? $inst->pv_id,
                    'pv_name'         => $sub->pv_name ?? $inst->pv_name,
                    'cat_name'        => $categoryNames[$sub->category_id ?? $inst->category_id] ?? 'Senza Categoria',
                    'amount'          => (float)($sub->price ?? 0)
                ]);
            }
        }

        // ===================== الصفحة 1: Dettaglio (المرجع) =====================
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dettaglio');
        $sheet->setCellValue('A1', 'Cliente');
        $sheet->setCellValue('B1', 'End Date');
        $sheet->setCellValue('C1', 'Descrizione');
        $sheet->setCellValue('D1', 'Totale');

        $row = 2;
        foreach ($allTransactions as $trans) {
            $sheet->setCellValue('A' . $row, $trans['ragione_sociale']);
            $sheet->setCellValue('B' . $row, $trans['date']);
            $sheet->setCellValue('C' . $row, $trans['description']);
            $sheet->setCellValue('D' . $row, $trans['amount']);
            $row++;
        }
        $sheet->setCellValue('A' . $row, 'TOTALE');
        $sheet->setCellValue('D' . $row, $row > 2 ? "=SUM(D2:D" . ($row - 1) . ")" : 0);
        $this->applyStyle($sheet, 'D', $row);

        // ===================== الصفحة 2: Proposta =====================
        $proposta = $spreadsheet->createSheet();
        $proposta->setTitle('Proposta');

        $activePvs = $allTransactions->where('amount', '!=', 0)->groupBy('pv_id');
        $pvColMap = [];
        $col = 2;
        $proposta->setCellValueByColumnAndRow(1, 1, 'Cliente');
        foreach ($activePvs as $pvId => $items) {
            $proposta->setCellValueByColumnAndRow($col, 1, $items->first()['pv_name']);
            $pvColMap[$pvId] = $col;
            $col++;
        }
        $pTotalCol = $col;
        $proposta->setCellValueByColumnAndRow($pTotalCol, 1, 'Totale');

        $pRow = 2;
        foreach ($allTransactions->groupBy('client_id') as $clientId => $clientTrans) {
            $proposta->setCellValueByColumnAndRow(1, $pRow, $clientTrans->first()['ragione_sociale']);
            foreach ($pvColMap as $pvId => $colIdx) {
                $proposta->setCellValueByColumnAndRow($colIdx, $pRow, $clientTrans->where('pv_id', $pvId)->sum('amount'));
            }
            $proposta->setCellValueByColumnAndRow($pTotalCol, $pRow, $clientTrans->sum('amount'));
            $pRow++;
        }
        $this->addFooterTotals($proposta, $pRow, $pTotalCol);

        // ===================== الصفحة 3: Macro_Servizi (حل مشكلة الأصفار) =====================
        $macro = $spreadsheet->createSheet();
        $macro->setTitle('Macro_Servizi');

        // جلب الفئات التي تحتوي على مبالغ فقط لعدم عرض أعمدة فارغة
        $activeCats = $allTransactions->where('amount', '!=', 0)->pluck('cat_name')->unique()->sort();
        $catColMap = [];
        $col = 2;
        $macro->setCellValueByColumnAndRow(1, 1, 'Cliente');
        foreach ($activeCats as $catName) {
            $macro->setCellValueByColumnAndRow($col, 1, $catName);
            $catColMap[$catName] = $col;
            $col++;
        }
        $mTotalCol = $col;
        $macro->setCellValueByColumnAndRow($mTotalCol, 1, 'Totale');

        $mRow = 2;
        // عرض العملاء الذين لديهم تعاملات فقط
        foreach ($allTransactions->where('amount', '!=', 0)->groupBy('client_id') as $clientId => $clientTrans) {
            $macro->setCellValueByColumnAndRow(1, $mRow, $clientTrans->first()['ragione_sociale']);
            foreach ($catColMap as $catName => $colIdx) {
                $macro->setCellValueByColumnAndRow($colIdx, $mRow, $clientTrans->where('cat_name', $catName)->sum('amount'));
            }
            $macro->setCellValueByColumnAndRow($mTotalCol, $mRow, $clientTrans->sum('amount'));
            $mRow++;
        }
        $this->addFooterTotals($macro, $mRow, $mTotalCol);

        // ===================== الصفحة 4: Riepilogo =====================
        $riepilogo = $spreadsheet->createSheet();
        $riepilogo->setTitle('Riepilogo');
        $riepilogo->setCellValue('A1', 'Macro Servizi');
        $riepilogo->setCellValue('B1', 'Totale');

        $rRow = 2;
        foreach ($allTransactions->groupBy('cat_name') as $catName => $items) {
            $catSum = $items->sum('amount');
            if ($catSum != 0) {
                $riepilogo->setCellValue('A' . $rRow, $catName);
                $riepilogo->setCellValue('B' . $rRow, $catSum);
                $rRow++;
            }
        }
        $riepilogo->setCellValue('A' . $rRow, 'TOTALE');
        $riepilogo->setCellValue('B' . $rRow, $rRow > 2 ? "=SUM(B2:B" . ($rRow - 1) . ")" : 0);
        $this->applyStyle($riepilogo, 'B', $rRow);

        $this->addInvoiceReconciliation($spreadsheet, $allTransactions, $filters);

        // تصدير الملف
        $fileName = 'client_payments_' . now()->format('YmdHis') . '.xlsx';
        $filePath = 'exports/' . $fileName;
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        Storage::disk('public')->put($filePath, ob_get_clean());

        return response()->json(['path' => Storage::disk('public')->url($filePath)]);
    }

    private function addInvoiceReconciliation(Spreadsheet $spreadsheet, $scheduled, array $filters): void
    {
        $listing = app(\App\Services\Invoice\InvoiceListService::class);
        // This workbook uses due dates, like the payment schedule and income stats.
        $invoices = $listing->ordered($listing->query(array_intersect_key($filters, ['clientId' => true]))
            ->when(isset($filters['startAt']), fn ($q) => $q->whereDate('invoices.end_at', '>=', Carbon::parse($filters['startAt'])->format('Y-m-d')))
            ->when(isset($filters['endAt']), fn ($q) => $q->whereDate('invoices.end_at', '<=', Carbon::parse($filters['endAt'])->format('Y-m-d')))
            ->get());
        $calculator = app(\App\Services\Invoice\InvoiceTotalsService::class);
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Fatture');
        $sheet->fromArray(['Cliente', 'ID fattura', 'Numero fattura', 'Scadenza', 'Imponibile', 'IVA 22%', 'Spese escluse IVA', 'Bollo', 'Totale', 'Verifica numero'], null, 'A1');
        $row = 2;
        foreach ($invoices as $invoice) {
            $amounts = $calculator->forInvoice($invoice);
            $sheet->fromArray([
                $invoice->client?->ragione_sociale ?? '', $invoice->id, $invoice->invoice_xml_number ?? '',
                $invoice->end_at ? Carbon::parse($invoice->end_at)->format('d/m/Y') : '',
                $amounts['taxableAmount'], $amounts['ivaAmount'], $amounts['extraTotal'],
                $amounts['stampAmount'], $amounts['total'],
                trim((string) $invoice->invoice_xml_number) === '' ? 'Numero XML assente: verificare emissione' : 'Numero presente',
            ], null, 'A'.$row, true);
            $row++;
        }
        $sheet->getStyle('E2:I'.max(2, $row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->setAutoFilter('A1:J'.max(1, $row - 1));
        $this->applyStyle($sheet, 'J', max(1, $row - 1));

        $comparison = $spreadsheet->createSheet();
        $comparison->setTitle('Riconciliazione');
        $comparison->fromArray(['Cliente', 'Descrizione', 'Origine', 'Importo previsto netto', 'Importo fatturato netto', 'Differenza netta', 'Spese escluse IVA', 'ID fatture', 'ID senza numero XML', 'Esito'], null, 'A1');
        $rows = app(\App\Services\Invoice\InvoiceReconciliationService::class)->compare($scheduled, $invoices);
        if ($rows) {
            $comparison->fromArray($rows, null, 'A2', true);
        }
        $comparison->getStyle('D2:G'.max(2, count($rows) + 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $comparison->setAutoFilter('A1:J'.(count($rows) + 1));
        $this->applyStyle($comparison, 'J', count($rows) + 1);
    }

    private function applyStyle($sheet, $lastCol, $lastRow) {
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (range('A', $lastCol) as $c) $sheet->getColumnDimension($c)->setAutoSize(true);
    }

    private function addFooterTotals($sheet, $row, $lastColIdx) {
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIdx);
        $sheet->setCellValueByColumnAndRow(1, $row, 'TOTALE');
        for ($i = 2; $i <= $lastColIdx; $i++) {
            $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->setCellValue($colL . $row, $row > 2 ? "=SUM({$colL}2:{$colL}" . ($row - 1) . ")" : 0);
        }
        $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")->getFont()->setBold(true);
        $this->applyStyle($sheet, $lastColLetter, $row);
    }
}
