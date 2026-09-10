<?php

namespace App\Http\Controllers\Api\Private\Reports;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\ClientAddress;
use App\Models\Client\ClientBankAccount;
use App\Models\Client\ClientPayInstallment;
use App\Models\Client\ClientPayInstallmentSubData;
use App\Models\Invoice\Invoice;
use App\Models\Parameter\ParameterValue;
use App\Models\Task\Task;
use App\Services\Reports\ReportService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;


class InvoiceReportExportController extends Controller
{

    protected $reportService;
    public function  __construct(ReportService $reportService)
    {
        //$this->middleware('auth:api');
        //$this->middleware('permission:all_reports', ['only' => ['__invoke']]);
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        if ($request->type == 'pdf') {
            return $this->generateInvoicePdf($this->getInvoiceData($request));
        } elseif ($request->type == 'csv') {
            return $this->generateInvoiceExcel($this->getInvoiceData($request));
        } elseif ($request->type == 'xlsx') {
            return $this->generateSimpleXlsx($this->getInvoiceData($request));
        } elseif ($request->type == 'xml') {
            $data = $this->getInvoiceData($request);

            // Check if client has CAB, ABI and bankName for XML export
            if (empty($data['clientBankAccount']['cab']) || empty($data['clientBankAccount']['abi']) || empty($data['clientBankAccount']['bankName'])) {
                return response()->json([
                    'message' => 'Questo cliente non ha CAB, ABI e nome banca associati'
                ], 401);
            }

            return $this->generateInvoiceXml($data);
        }
    }

    private function getInvoiceData(Request $request)
    {
        $invoice = Invoice::with(['client', 'invoiceDetails'])->findOrFail($request->invoiceIds[0]);
        $document = app(\App\Services\Invoice\InvoiceDocumentService::class)->build($invoice);
        $client = $invoice->client;

        $clientAddressFormatted = ClientAddress::where('client_id', $client->id)->first()?->address ?? "";

        // First try to get main bank account, if not found get any bank account
        $clientBankAccount = ClientBankAccount::with('bank')->where('client_id', $client->id)->where('is_main', 1)->first();

        if ($clientBankAccount == null) {
            $clientBankAccount = ClientBankAccount::with('bank')->where('client_id', $client->id)->first();
        }

        $clientBankAccountFormatted = [];

        if ($clientBankAccount != null) {
            $clientBankAccountFormatted = [
                'iban'     => $clientBankAccount->iban ?? "",
                'abi'      => $clientBankAccount->abi ?? "",
                'cab'      => $clientBankAccount->cab ?? "",
                'bankName' => $clientBankAccount->bank?->parameter_value ?? ""
            ];
        }

        $clientAddressData = ClientAddress::where('client_id', $client->id)->first();

        $paymentMethod = ParameterValue::find($invoice->payment_type_id ?? null);

        $bankAccount = null;

        if ($invoice->bank_account_id) {
            $bankAccount = ParameterValue::where('id', $invoice->bank_account_id)->first();
        } else {
            $bankAccount = ParameterValue::where('parameter_order', 7)->where('is_default', 1)->first();
        }

        return [
            'invoice'              => $invoice,
            'clientAddressData'    => $clientAddressData?->toArray() ?? [],
            ...$document,
            'client'               => $client,
            'clientAddress'        => $clientAddressFormatted,
            'clientBankAccount'    => $clientBankAccountFormatted,
            'paymentMethod'        => $paymentMethod->code ?? "",
            'paymentMethodName'    => $paymentMethod->parameter_value ?? "",
            'bankAccount'          => [
                'iban'     => $bankAccount->parameter_value ?? '',
                'abi'      => $bankAccount->description2 ?? '',
                'cab'      => $bankAccount->description3 ?? '',
                'bankName' => $bankAccount->description ?? ''
            ],
        ];
    }


    private function generateInvoicePdf(array $data)
    {
        $pdf = PDF::loadView('invoice_pdf_report', $data);

        $fileName = 'invoice_' . $data['invoice']->id . '_' . now()->format('d_m_Y_H_i_s') . '.pdf';
        $path = 'exportedInvoices/' . $fileName;

        Storage::disk('public')->put($path, $pdf->output());

        $url = asset('storage/' . $path);

        return response()->json(['path' => env('APP_URL') . $url]);
    }

    public function generateSimpleXlsx(array $data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Cliente');
        $sheet->setCellValue('B1', 'Descrizione');
        $sheet->setCellValue('C1', 'Totale');

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 2;
        $lineTotal = 0;
        foreach ($data['invoiceItems'] as $entry) {
            $price = (float)($entry['priceAfterDiscount'] ?? 0);
            if (!empty($entry['is_discount'])) {
                $price = -abs($price);
            }
            $tax   = (float)($entry['additionalTaxPercentage'] ?? 22);
            $total = $tax > 0 ? $price * (1 + $tax / 100) : $price;

            $sheet->setCellValue('A' . $row, $data['client']->ragione_sociale ?? '');
            $sheet->setCellValue('B' . $row, $entry['description'] ?? '');
            $sheet->setCellValue('C' . $row, round($total, 2));
            $lineTotal += round($total, 2);
            $row++;
        }

        // IVA is rounded on the invoice base, rather than independently per line.
        $rounding = round($data['invoiceTotalWithTax'] - $lineTotal, 2);
        if ($rounding != 0) {
            $sheet->fromArray([$data['client']->ragione_sociale ?? '', 'Arrotondamento IVA', $rounding], null, 'A'.$row++);
        }
        if ($data['applyStamp']) {
            $sheet->fromArray([$data['client']->ragione_sociale ?? '', 'Imposta di bollo', $data['stampAmount']], null, 'A'.$row++);
        }

        $sheet->fromArray([
            ['Imponibile', $data['invoiceTaxableTotal']],
            ['IVA 22%', $data['invoiceTotalTax']],
            ['Totale fattura', round($data['invoiceTotalWithTax'] + $data['stampAmount'], 2)],
        ], null, 'E1', true);
        $sheet->getStyle('F1:F3')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);

        $sheet->getStyle('A1:C' . ($row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (['A', 'B', 'C'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setAutoFilter('A1:C1');

        $fileName = 'invoice_' . $data['invoice']->id . '_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        $filePath = 'exportedInvoices/' . $fileName;

        ob_start();
        (new Xlsx($spreadsheet))->save('php://output');
        Storage::disk('public')->put($filePath, ob_get_clean());

        return response()->json([
            'path' => env('APP_URL') . parse_url(asset('storage/' . $filePath), PHP_URL_PATH),
        ]);
    }

    public function generateInvoiceExcel($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Define headers
        $headers = ['Cliente', 'Descrizione', 'Prezzo unitario', 'Quantitestazione'];

        // Fill headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Style headers
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:F1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Fill rows
        $row = 2;

        foreach ($data['invoiceItems'] as $entry) {
            $price = (float) ($entry['priceAfterDiscount'] ?? 0);
            if (!empty($entry['is_discount'])) {
                $price = -abs($price);
            }
            $sheet
                ->setCellValue('A' . $row, $data['client']->ragione_sociale ?? '')
                ->setCellValue('B' . $row, $entry['description'] ?? '')
                ->setCellValue('C' . $row, $price)
                ->setCellValue('D' . $row, $entry['quantita'] ?? 1)
                ->setCellValue('E' . $row, $price * ($entry['quantita'] ?? 1))
                ->setCellValue('F' . $row, $data['invoiceStartAt']);
            $row++;
        }

        // Apply borders and autosize
        $sheet->getStyle('A1:F' . ($row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range('A', 'F') as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $sheet->setAutoFilter('A1:F1');

        // Write to memory and store
        $fileName = 'user_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
        $filePath = 'exportedInvoices/' . $fileName;

        ob_start();
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $excelOutput = ob_get_clean();

        Storage::disk('public')->put($filePath, $excelOutput);

        $url = asset('storage/' . $filePath);

        return response()->json([
            'path' => env('APP_URL') . parse_url($url, PHP_URL_PATH),
        ]);
    }

    public function generateInvoiceXml(array $data)
    {
        // Function to sanitize text by removing accents and special characters
        $removeAccents = function ($string) {
            $accents = [
                'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
                'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
                'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
                'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
                'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
                'ç' => 'c', 'ñ' => 'n',
                'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
                'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
                'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
                'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
                'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
                'Ç' => 'C', 'Ñ' => 'N'
            ];
            return strtr($string, $accents);
        };

        $safe = fn($v) => htmlspecialchars($removeAccents(trim((string)$v)), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $parseDate = function ($value) {
            try {
                if (!$value) return now()->format('Y-m-d');
                $clean = trim(explode(' ', (string)$value)[0]);
                if (str_contains($clean, '/')) {
                    return \Carbon\Carbon::createFromFormat('d/m/Y', $clean)->format('Y-m-d');
                }
                return \Carbon\Carbon::parse($clean)->format('Y-m-d');
            } catch (\Exception $e) {
                return now()->format('Y-m-d');
            }
        };

        $usePassepartout = !empty($data['client']['sdi_code']) && $data['client']['sdi_code'] !== '0000000';

        $extraTotal = 0;
        foreach ($data['invoiceItems'] as $item) {
            if ((float)($item['additionalTaxPercentage'] ?? 22) == 0) {
                $extraTotal += (float)$item['priceAfterDiscount'];
            }
        }

        $applyStamp = $data['applyStamp'];
        $stampAmount = $data['stampAmount'];
        $totalWithStamp = (float)$data['invoiceTotalWithTax'] + ($applyStamp ? $stampAmount : 0);

        /* ================= 1. Build basic structure without Namespaces temporarily ================= */
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="windows-1252"?>' .
            '<?xml-stylesheet type="text/xsl" href="fatturaordinaria_v1.2.xsl"?>' .
            '<FatturaElettronica versione="FPR12"></FatturaElettronica>'
        );

        /* ================= HEADER ================= */
        $header = $xml->addChild('FatturaElettronicaHeader');

        /* --- DatiTrasmissione --- */
        $trasm = $header->addChild('DatiTrasmissione');

        $idTras = $trasm->addChild('IdTrasmittente');
        if ($usePassepartout) {
            $idTras->addChild('IdPaese', 'SM');
            $idTras->addChild('IdCodice', '03473');
        } else {
            $idTras->addChild('IdPaese', 'IT');
            $idTras->addChild('IdCodice', '00987920196');
        }

        // Check if invoice already has XML number, if not generate new one
        if (!empty($data['invoice']->invoice_xml_number)) {
            $invoiceNewNumber = $data['invoice']->invoice_xml_number;
        } else {
            DB::transaction(function () use (&$invoiceNewNumber) {
                $parameterValue = ParameterValue::where('parameter_order', 13)->lockForUpdate()->first();
                $parameterNumber = $parameterValue?->parameter_value ?? '1/60';

                $parameterParts = explode('/', $parameterNumber);
                $currentNum = (int) ($parameterParts[1] ?? 60);

                $parameterParts[1] = $currentNum + 1;
                $invoiceNewNumber = implode('/', $parameterParts);

                if ($parameterValue) {
                    $parameterValue->parameter_value = $invoiceNewNumber;
                    $parameterValue->save();
                }
            });
        }

        $trasm->addChild('ProgressivoInvio', $invoiceNewNumber);
        $trasm->addChild('FormatoTrasmissione', 'FPR12');
        $trasm->addChild('CodiceDestinatario', $safe($data['client']['sdi'] ?? '0000000'));

        /* --- CedentePrestatore --- */
        $ced = $header->addChild('CedentePrestatore');
        $datiCed = $ced->addChild('DatiAnagrafici');
        $ivaCed = $datiCed->addChild('IdFiscaleIVA');
        $ivaCed->addChild('IdPaese', 'IT');
        $ivaCed->addChild('IdCodice', '00987920196');
        $datiCed->addChild('CodiceFiscale', '00987920196');
        $anaCed = $datiCed->addChild('Anagrafica');
        $anaCed->addChild('Denominazione', 'ELABORAZIONI SRL');
        $datiCed->addChild('RegimeFiscale', 'RF01');

        $sedeCed = $ced->addChild('Sede');
        $sedeCed->addChild('Indirizzo', 'VIA STAZIONE 9/B');
        $sedeCed->addChild('CAP', '26013');
        $sedeCed->addChild('Comune', 'CREMA');
        $sedeCed->addChild('Provincia', 'CR');
        $sedeCed->addChild('Nazione', 'IT');

        $rea = $ced->addChild('IscrizioneREA');
        $rea->addChild('Ufficio', 'CR');
        $rea->addChild('NumeroREA', '126442');
        $rea->addChild('CapitaleSociale', '10000.00');
        $rea->addChild('SocioUnico', 'SM');
        $rea->addChild('StatoLiquidazione', 'LN');

        $contatti = $ced->addChild('Contatti');
        $contatti->addChild('Telefono', '037386998');
        $contatti->addChild('Email', 'info@studiocrottibignami.it');

        /* --- CessionarioCommittente --- */
        /*
         * REGOLA:
         * - Se il cliente ha una vera P.IVA (11 cifre numeriche IT) -> IdFiscaleIVA + (eventuale) CodiceFiscale + Denominazione
         * - Se il cliente è un privato (CF persona fisica, 16 alfanumerici) -> SOLO CodiceFiscale + Nome/Cognome
         * - Altri casi -> solo CodiceFiscale + Denominazione
         */
        $cess = $header->addChild('CessionarioCommittente');
        $datiCess = $cess->addChild('DatiAnagrafici');

        $ivaRaw = trim((string)($data['client']['iva'] ?? ''));
        $cfRaw  = trim((string)($data['client']['cf']  ?? ''));

        $isValidPIva       = $ivaRaw !== '' && preg_match('/^\d{11}$/', $ivaRaw);
        $isCfPersonaFisica = $cfRaw !== '' && preg_match('/^[A-Z0-9]{16}$/i', $cfRaw);

        if ($isValidPIva) {
            // Soggetto con partita IVA
            $ivaCess = $datiCess->addChild('IdFiscaleIVA');
            $ivaCess->addChild('IdPaese', 'IT');
            $ivaCess->addChild('IdCodice', $safe($ivaRaw));

            if ($cfRaw !== '') {
                $datiCess->addChild('CodiceFiscale', $safe(strtoupper($cfRaw)));
            }

            $anaCess = $datiCess->addChild('Anagrafica');
            $anaCess->addChild('Denominazione', $safe($data['client']['ragione_sociale']));
        } else {
            // Privato senza P.IVA: solo CodiceFiscale, NIENTE IdFiscaleIVA
            if ($cfRaw !== '') {
                $datiCess->addChild('CodiceFiscale', $safe(strtoupper($cfRaw)));
            }

            $anaCess = $datiCess->addChild('Anagrafica');

            if ($isCfPersonaFisica) {
                // Persona fisica: prova a usare Nome/Cognome separati
                $ragSoc = trim((string)$data['client']['ragione_sociale']);
                $parts  = preg_split('/\s+/', $ragSoc, 2);

                if (count($parts) === 2) {
                    // IMPORTANTE: lo schema XSD del SdI richiede Nome PRIMA di Cognome.
                    // La ragione_sociale è memorizzata come "Cognome Nome" (es. "Giotti Ilaria"),
                    // quindi $parts[0] = Cognome, $parts[1] = Nome.
                    // Nell'XML però vanno scritti in ordine: prima Nome, poi Cognome.
                    $anaCess->addChild('Nome',    $safe($parts[1]));
                    $anaCess->addChild('Cognome', $safe($parts[0]));
                } else {
                    $anaCess->addChild('Denominazione', $safe($ragSoc));
                }
            } else {
                $anaCess->addChild('Denominazione', $safe($data['client']['ragione_sociale']));
            }
        }

        $provRaw = $data['clientAddressData']['province'] ?? '';
        $prov = strtoupper(substr(trim($provRaw), 0, 2)) ?: 'XX';
        $sedeCess = $cess->addChild('Sede');
        $sedeCess->addChild('Indirizzo', $safe($data['clientAddressData']['address']));
        $sedeCess->addChild('CAP', $safe($data['clientAddressData']['cap'] ?? '00000'));
        $sedeCess->addChild('Comune', $safe($data['clientAddressData']['city']));
        $sedeCess->addChild('Provincia', $prov);
        $sedeCess->addChild('Nazione', 'IT');

        /* --- Terzo Intermediario --- */
        $terzo = $header->addChild('TerzoIntermediarioOSoggettoEmittente');
        $datiTerzo = $terzo->addChild('DatiAnagrafici');
        $ivaTerzo = $datiTerzo->addChild('IdFiscaleIVA');
        $ivaTerzo->addChild('IdPaese', 'SM');
        $ivaTerzo->addChild('IdCodice', '03473');
        $anaTerzo = $datiTerzo->addChild('Anagrafica');
        $anaTerzo->addChild('Denominazione', 'Passepartout S.p.A');

        $header->addChild('SoggettoEmittente', 'TZ');

        /* ================= BODY ================= */
        $body = $xml->addChild('FatturaElettronicaBody');
        $gen = $body->addChild('DatiGenerali');

        $doc = $gen->addChild('DatiGeneraliDocumento');
        $doc->addChild('TipoDocumento', 'TD01');
        $doc->addChild('Divisa', 'EUR');
        $doc->addChild('Data', $parseDate($data['invoiceStartAt']));

        // Extract the second part of invoiceNewNumber (e.g., '1/57' -> '57')
        $invoiceNumberParts = explode('/', $invoiceNewNumber ?? '');
        $invoiceNumero = $invoiceNumberParts[1] ?? $data['invoice']['number'];
        $doc->addChild('Numero', $safe($invoiceNumero));

        if ($applyStamp) {
            $datiBollo = $doc->addChild('DatiBollo');
            $datiBollo->addChild('BolloVirtuale', 'SI');
            $datiBollo->addChild('ImportoBollo', number_format($stampAmount, 2, '.', ''));
        }

        $doc->addChild('ImportoTotaleDocumento', number_format($totalWithStamp, 2, '.', ''));

        // Causale dal primo item con valore positivo (esclude righe descrittive e sconto)
        foreach ($data['invoiceItems'] as $item) {
            if (!empty($item['is_discount'])) continue;
            if ((float)($item['priceAfterDiscount'] ?? 0) > 0 && !empty($item['description'])) {
                $doc->addChild('Causale', $safe($item['description']));
                break;
            }
        }

        $beni = $body->addChild('DatiBeniServizi');
        $line = 1;
        foreach (array_values($data['invoiceItems']) as $item) {
            $prezzo        = (float)($item['priceAfterDiscount'] ?? 0);
            $isDescriptive = !empty($item['is_descriptive_only']);
            $isDiscount    = !empty($item['is_discount']);

            // Sconto: nell'XML PrezzoUnitario e PrezzoTotale devono essere NEGATIVI
            // (regola SdI per evitare scarto 00422: la somma dei PrezzoTotale per aliquota
            //  deve corrispondere a ImponibileImporto in DatiRiepilogo).
            if ($isDiscount) {
                $prezzo = -abs($prezzo);
            }

            // Salta righe non valorizzate che non sono né descrittive né sconto
            if ($prezzo == 0 && !$isDescriptive && !$isDiscount) continue;

            $aliquota = (float)($item['additionalTaxPercentage'] ?? 22);
            $det = $beni->addChild('DettaglioLinee');
            $det->addChild('NumeroLinea', (string)$line);

            // CodiceArticolo solo per righe con valore economico positivo e IVA > 0
            // (escluse: righe descrittive a 0, sconti negativi, righe IVA 0%)
            if ($aliquota != 0 && $prezzo > 0) {
                $codArt = $det->addChild('CodiceArticolo');
                $codArt->addChild('CodiceTipo', 'PRESTAZIONE');
                $codArt->addChild('CodiceValore', $item['serviceCode'] ?? '..');
            }

            $det->addChild('Descrizione', $safe($item['description'] ?? 'Senza descrizione'));
            $det->addChild('Quantita', '1.00');
            $det->addChild('UnitaMisura', 'NR');
            $det->addChild('PrezzoUnitario', number_format($prezzo, 6, '.', ''));
            $det->addChild('PrezzoTotale', number_format($prezzo, 2, '.', ''));
            $det->addChild('AliquotaIVA', number_format($aliquota, 2, '.', ''));

            // Natura solo per righe con IVA 0% E valore positivo (extra/escluse)
            if ($aliquota == 0 && $prezzo != 0) {
                $natura = $item['serviceCode'] ?? 'N1';
                if (!preg_match('/^N[1-7](\.[0-9])?$/', $natura)) {
                    $natura = 'N1';
                }
                $det->addChild('Natura', $natura);
            }

            $line++;
        }

        if ($applyStamp) {
            $det = $beni->addChild('DettaglioLinee');
            $det->addChild('NumeroLinea', (string)$line);
            $det->addChild('Descrizione', 'Imposta di bollo');
            $det->addChild('Quantita', '1.00');
            $det->addChild('UnitaMisura', 'NR');
            $det->addChild('PrezzoUnitario', number_format($stampAmount, 2, '.', ''));
            $det->addChild('PrezzoTotale', number_format($stampAmount, 2, '.', ''));
            $det->addChild('AliquotaIVA', '0.00');
            $det->addChild('Natura', 'N1');
        }

        $riep = $beni->addChild('DatiRiepilogo');
        $riep->addChild('AliquotaIVA', '22.00');
        $riep->addChild('ImponibileImporto', number_format((float)$data['invoiceTaxableTotal'], 2, '.', ''));
        $riep->addChild('Imposta', number_format((float)$data['invoiceTotalTax'], 2, '.', ''));
        $riep->addChild('EsigibilitaIVA', 'I');

        if ($extraTotal != 0) {
            $riepN1 = $beni->addChild('DatiRiepilogo');
            $riepN1->addChild('AliquotaIVA', '0.00');
            $riepN1->addChild('Natura', 'N1');
            $riepN1->addChild('ImponibileImporto', number_format(
                $extraTotal + ($applyStamp ? $stampAmount : 0),
                2,
                '.',
                ''
            ));
            $riepN1->addChild('Imposta', '0.00');
            $riepN1->addChild('RiferimentoNormativo', 'Operazione Esclusa art.15 DPR 633/72');
        }

        /* ================= PAGAMENTO ================= */
        $pag = $body->addChild('DatiPagamento');
        $pag->addChild('CondizioniPagamento', 'TP02');
        $detPag = $pag->addChild('DettaglioPagamento');
        $modalita = $data['paymentMethod'];
        $detPag->addChild('ModalitaPagamento', $modalita);
        $detPag->addChild('DataScadenzaPagamento', $parseDate($data['invoice']['end_at'] ?? $data['invoiceStartAt']));
        $detPag->addChild('ImportoPagamento', number_format($totalWithStamp, 2, '.', ''));

        if ($modalita === 'MP05') {
            $detPag->addChild('IstitutoFinanziario', $safe($data['bankAccount']['bankName'] ?? ''));
            $detPag->addChild('IBAN', $data['bankAccount']['iban'] ?? '');
        } elseif ($modalita === 'MP12') {
            $detPag->addChild('IstitutoFinanziario', $safe($data['clientBankAccount']['bankName'] ?? ''));
            $detPag->addChild('ABI', $data['clientBankAccount']['abi'] ?? '');
            $detPag->addChild('CAB', $data['clientBankAccount']['cab'] ?? '');
        }

        /* ================= 2. Convert structure to add p: Namespaces ================= */
        $dom = new \DOMDocument('1.0', 'windows-1252');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        $root = $dom->documentElement;

        $ns = 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2';
        $newRoot = $dom->createElementNS($ns, 'p:FatturaElettronica');

        $newRoot->setAttribute('versione', $root->getAttribute('versione'));
        $newRoot->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
        $newRoot->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        while ($root->hasChildNodes()) {
            $newRoot->appendChild($root->firstChild);
        }

        $dom->replaceChild($newRoot, $root);
        $xmlContent = $dom->saveXML();

        /* ================= SAVE & RETURN ================= */
        $invoiceNumberParts = explode('/', $invoiceNewNumber ?? '');
        $invoiceNumberPart = end($invoiceNumberParts);
        $invoiceNumberPart = str_pad($invoiceNumberPart, 5, '0', STR_PAD_LEFT);

        $fileName = '00987920196' . '_' . $invoiceNumberPart . '.xml';
        $path = 'exportedInvoices/' . $fileName;

        Storage::disk('local')->put($path, $xmlContent);

        // Update invoice with XML number only if it's new
        if (empty($data['invoice']->invoice_xml_number)) {
            Invoice::where('id', $data['invoice']['id'])->update(['invoice_xml_number' => $invoiceNewNumber]);
        }

        return response()->json([
            'data' => [
                'name'    => $fileName,
                'content' => mb_convert_encoding($xmlContent, 'UTF-8', 'WINDOWS-1252'),
            ]
        ]);
    }
}
