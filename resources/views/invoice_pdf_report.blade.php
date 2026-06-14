<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Fattura</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            color: #333;
        }
        /* .header, .footer {
            margin-bottom: 30px;
        } */
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-direction: row;
            width: 100%; /* Ensure the container takes full width */
        }
        .flex-between > div {

        }

        .logo {
            height: 60px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .box {
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background-color: #f5f5f5;
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #ccc;
        }
        td {
            padding: 8px;
        }
        .text-right {
            text-align: right;
        }
        .note {
            font-size: 12px;
            color: #666;
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header flex-between">
        <div>
            <h2 style="margin-bottom: 5px;">Fattura Proforma Del {{ $invoiceStartAt }}</h2>
        </div>
        {{-- <div style="text-align: right;">
            <h2 style="margin-bottom: 5px;">{{ $invoice->number }}</h2>
        </div> --}}
    </div>

    <!-- Addresses -->
    <div class="flex-between" style="margin-bottom: 30px;">
        <div>
            <div class="section-title">DA</div>
            <div><strong>Elaborazioni Srl</strong></div>
            <div>Via Stazione, 9/B, 26013, Crema (CR)</div>
            <div>P.IVA 00987920196</div>
            <div>C.F. 00987920196</div>
        </div>
        <br>
        <br>
        <div>
            <div class="section-title">DESTINATARIO</div>
            <div><strong>{{ $client->ragione_sociale }}</strong></div>
            <div>{{ $clientAddress }}</div>
            <div>P.IVA {{ $client->iva }}</div>
            <div>C.F. {{ $client->cf }}</div>
        </div>
    </div>

    <!-- Invoice Items -->
    <table>
        <thead>
            <tr>
                <th>DESCRIZIONE</th>
                <th class="text-right">IMPORTO</th>
                <th class="text-right">TOTALE</th>
                <th class="text-right">% IVA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoiceItems as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-right">{{ is_string($item['price'])? $item['price']: number_format($item['price'], 2) }}</td>
                    <td class="text-right">{{ is_string($item['priceAfterDiscount'])? $item['priceAfterDiscount']: number_format($item['priceAfterDiscount'], 2) }}</td>
                    <td class="text-right">{{ $item['additionalTaxPercentage']}}%</td>
                </tr>
            @endforeach
            {{-- <tr>
                <td>Consulenza tecnica</td>
                <td class="text-right">500,00 €</td>
                <td class="text-right">500,00 €</td>
                <td class="text-right">0%</td>
            </tr>
            <tr>
                <td>Sviluppo software</td>
                <td class="text-right">1.000,00 €</td>
                <td class="text-right">1.000,00 €</td>
                <td class="text-right">0%</td>
            </tr> --}}
        </tbody>
    </table>

    {{-- <!-- Tax Notes -->
    <div class="note">
        <p>
            <strong>ESENZIONI IVA</strong> 1.500,00 € - Non soggetto - Regime forfettario Art.1, c. 54-89, L. 190/2014 non soggetto ad IVA né a ritenuta ai sensi dell’Art. 1, c. 67 L. 190/2014 e successive modificazioni
        </p>
        <p>
            <strong>BOLLO</strong> 2,00 € - assolto ai sensi del Decreto MEF 28 dicembre 2018
        </p>
    </div> --}}

    <!-- Totals Box -->
    <div class="box">

        @php
    $totalWithStamp = $invoiceTotalWithTax + (!empty($applyStamp) && $applyStamp ? $stampAmount : 0);
@endphp
        <table>
            <tr>
                <td>Imponibile</td>
                <td class="text-right">{{ number_format($invoiceTaxableTotal, 2) }} €</td>
            </tr>
            <tr>
                <td>Iva</td>
                <td class="text-right">{{ number_format($invoiceTotalTax, 2) }} €</td>
            </tr>
            @if(!empty($applyStamp) && $applyStamp)
<tr>
    <td>Imposta di bollo</td>
    <td class="text-right">{{ number_format($stampAmount, 2, ',', '.') }} €</td>
</tr>
@endif

            <tr>
                <td>Totale fattura</td>
               <td class="text-right">{{ number_format($totalWithStamp, 2, ',', '.') }} €</td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 15px; border-top: 1px solid #ccc; font-weight: bold; font-size: 18px;">
                    Totale dovuto
                    <span style="float: right;">{{ number_format($totalWithStamp, 2, ',', '.') }} €</span>
                </td>
            </tr>
        </table>
    </div>


    <!-- Payment Info -->
    <table style="margin-top: 30px; font-size: 13px;">
        <tr>
            <td>
                <div style="font-weight: 600; color: #7f8fa6;">MODALITÀ DI PAGAMENTO</div>
                <div>{{ $paymentMethodName }}</div>
            </td>
            <td>
                <div style="font-weight: 600; color: #7f8fa6;">IBAN</div>
                <div>{{ $clientBankAccount['iban'] }}</div>
            </td>
            <td>
                <div style="font-weight: 600; color: #7f8fa6;">DATA SCADENZA</div>
                <div>{{ Carbon\Carbon::parse($invoice->end_at)->format('d/m/Y') }}</div>
            </td>
            <td>
                <div style="font-weight: 600; color: #7f8fa6;">IMPORTO</div>
               <div>{{ number_format($totalWithStamp, 2, ',', '.') }} €</div>
            </td>
        </tr>
    </table>

    <!-- Payment Method Details -->
    @if($paymentMethod === 'MP05')
    <div style="margin-top: 30px; padding: 15px; background-color: #ffffff; border: 1px solid #e1e1e1; border-radius: 8px;">
        <div style="font-weight: bold; margin-bottom: 10px; color: #333;">DETTAGLI PAGAMENTO</div>
        <div style="font-size: 13px; color: #333;">
            <strong style="color: #7f8fa6;">Istituto Finanziario:</strong> {{ $bankAccount['bankName'] ?? '' }}<br>
            <strong style="color: #7f8fa6;">IBAN:</strong> {{ $bankAccount['iban'] ?? '' }}
        </div>
    </div>
    @elseif($paymentMethod === 'MP12')
    <div style="margin-top: 30px; padding: 15px; background-color: #ffffff; border: 1px solid #e1e1e1; border-radius: 8px;">
        <div style="font-weight: bold; margin-bottom: 10px; color: #333;">DETTAGLI PAGAMENTO</div>
        <div style="font-size: 13px; color: #333;">
            <strong style="color: #7f8fa6;">Istituto Finanziario:</strong> {{ $clientBankAccount['bankName'] ?? '' }}<br>
            <strong style="color: #7f8fa6;">ABI:</strong> {{ $clientBankAccount['abi'] ?? '' }}<br>
            <strong style="color: #7f8fa6;">CAB:</strong> {{ $clientBankAccount['cab'] ?? '' }}
        </div>
    </div>
    @endif

    <!-- Footer -->
    {{-- <div class="note" style="margin-top: 40px;">
        <p>
            Mario Rossi Srl - Regime fiscale: Regime forfettario Art.1, c. 54-89, L. 190/2014 non soggetta ad IVA né a ritenuta ai sensi dell’Art. 1, c. 67<br>
            Copia di fattura elettronica inviata al suo Cassetto Fiscale. Generata con FatturABitento APP
        </p>
        <p style="margin-top: 10px;">
            Fattura del 06/04/2025
        </p>
    </div> --}}

</body>
</html>
