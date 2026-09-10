<?php

namespace App\Services\Invoice;

use App\Models\Task\Task;
use Illuminate\Support\Collection;

class InvoiceReconciliationService
{
    /** Compare pre-tax source amounts; IVA, surcharges and stamps are in Fatture. */
    public function compare(Collection $scheduled, Collection $invoices): array
    {
        $groups = [];
        foreach ($scheduled as $row) {
            $key = $row['source_type'].':'.$row['source_id'];
            $groups[$key] = [
                'client' => $row['ragione_sociale'],
                'description' => $row['description'],
                'source' => class_basename($row['source_type']).':'.$row['source_id'],
                'scheduled' => (float) $row['amount'],
                'invoiced' => 0.0,
                'extra' => 0.0,
                'invoiceIds' => [],
                'missingXmlIds' => [],
                'lineCount' => 0,
                'hasSchedule' => true,
                'task' => false,
            ];
        }

        foreach ($invoices as $invoice) {
            foreach ($invoice->invoiceDetails as $detail) {
                $key = $detail->invoiceable_type.':'.$detail->invoiceable_id;
                if (! $detail->invoiceable_type || ! $detail->invoiceable_id) {
                    $key = 'detail:'.$detail->id;
                }
                $groups[$key] ??= [
                    'client' => $invoice->client?->ragione_sociale ?? '',
                    'description' => $detail->description ?? '',
                    'source' => class_basename($detail->invoiceable_type ?? 'InvoiceDetail').':'.($detail->invoiceable_id ?? $detail->id),
                    'scheduled' => 0.0,
                    'invoiced' => 0.0,
                    'extra' => 0.0,
                    'invoiceIds' => [],
                    'missingXmlIds' => [],
                    'lineCount' => 0,
                    'hasSchedule' => false,
                    'task' => $detail->invoiceable_type === Task::class,
                ];
                $groups[$key]['invoiced'] += (float) $detail->price_after_discount;
                $groups[$key]['extra'] += (float) $detail->extra_price;
                $groups[$key]['invoiceIds'][] = $invoice->id;
                $groups[$key]['lineCount']++;
                if (trim((string) $invoice->invoice_xml_number) === '') {
                    $groups[$key]['missingXmlIds'][] = $invoice->id;
                }
            }
        }

        return array_map(function (array $group) {
            $reasons = [];
            $difference = round($group['invoiced'] - $group['scheduled'], 2);
            if (! $group['invoiceIds']) {
                $reasons[] = 'Nessuna fattura collegata nel periodo';
            } elseif (! $group['hasSchedule']) {
                $reasons[] = $group['task'] ? 'Servizio aggiuntivo da task' : 'Voce solo in fattura nel periodo';
            } elseif ($difference != 0) {
                $reasons[] = 'Importo diverso';
            }
            if ($group['lineCount'] > 1) {
                $reasons[] = 'Collegamenti multipli da verificare';
            }
            if ($group['missingXmlIds']) {
                $reasons[] = 'Numero XML assente: verificare emissione';
            }

            return [
                $group['client'], $group['description'], $group['source'],
                round($group['scheduled'], 2), round($group['invoiced'], 2), $difference,
                round($group['extra'], 2), implode(', ', array_unique($group['invoiceIds'])),
                implode(', ', array_unique($group['missingXmlIds'])), implode('; ', $reasons) ?: 'Corrisponde',
            ];
        }, array_values($groups));
    }
}
