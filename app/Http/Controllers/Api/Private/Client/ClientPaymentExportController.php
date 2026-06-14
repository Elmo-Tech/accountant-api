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
                    'pv_sub.id as pv_id',
                    'pv_sub.parameter_value as pv_name',
                    'pv_sub.description',
                    'pv_sub.description2 as category_id',
                    'sub.price'
                )
                ->get();

            foreach ($subs as $sub) {
                $allTransactions->push([
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
        $sheet->setCellValue('D' . $row, "=SUM(D2:D" . ($row - 1) . ")");
        $this->applyStyle($sheet, 'D', $row);

        // ===================== الصفحة 2: Proposta =====================
        $proposta = $spreadsheet->createSheet();
        $proposta->setTitle('Proposta');

        $activePvs = $allTransactions->where('amount', '>', 0)->groupBy('pv_id');
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
        $activeCats = $allTransactions->where('amount', '>', 0)->pluck('cat_name')->unique()->sort();
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
        foreach ($allTransactions->where('amount', '>', 0)->groupBy('client_id') as $clientId => $clientTrans) {
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
            if ($catSum > 0) {
                $riepilogo->setCellValue('A' . $rRow, $catName);
                $riepilogo->setCellValue('B' . $rRow, $catSum);
                $rRow++;
            }
        }
        $riepilogo->setCellValue('A' . $rRow, 'TOTALE');
        $riepilogo->setCellValue('B' . $rRow, "=SUM(B2:B" . ($rRow - 1) . ")");
        $this->applyStyle($riepilogo, 'B', $rRow);

        // تصدير الملف
        $fileName = 'client_payments_' . now()->format('YmdHis') . '.xlsx';
        $filePath = 'exports/' . $fileName;
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        Storage::disk('public')->put($filePath, ob_get_clean());

        return response()->json(['path' => Storage::disk('public')->url($filePath)]);
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
            $sheet->setCellValue($colL . $row, "=SUM({$colL}2:{$colL}" . ($row - 1) . ")");
        }
        $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")->getFont()->setBold(true);
        $this->applyStyle($sheet, $lastColLetter, $row);
    }
}
