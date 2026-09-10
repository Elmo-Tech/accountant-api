# Invoice ordering, IVA and reconciliation

Implemented scope: meeting items 2, 3, 5 and 7. No competence-period fields
were added (item 6).

## Invoice list API

`GET /api/v1/invoices?filter[unassigned]=0`

- Orders by `invoiceXmlNumber` using natural numeric comparison (`1/2` before
  `1/10`), before pagination. For assigned invoices, the optional top-level query
  parameter `sortXmlNumber=asc|desc` selects the direction; omitted means `asc`.
  Invalid values return HTTP 422. Blank/null numbers follow numbered invoices
  in either direction. Internal invoice number, then ID, break ties in the
  selected direction. Sorting does not change filtered totals.

  Example: `GET /api/v1/invoices?filter[unassigned]=0&sortXmlNumber=desc`
- Keeps the existing response envelope and field names.
- Adds two numeric fields to every invoice:

```json
{
  "taxableAmount": 100,
  "ivaAmount": 22,
  "totalInvoiceAfterDiscount": 122
}
```

`taxableAmount` is the final IVA base after the client surcharge and invoice
discount. `ivaAmount` is that base multiplied by 22%, rounded to two decimals.
Excluded expenses and the existing stamp charge are part of the final total,
but not part of this IVA base. `additionalTax` remains the client's surcharge
percentage; it is not the IVA amount. `invoiceDiscount` retains the configured
discount value/percentage for compatibility.

The same two fields are available on unassigned invoice previews and
`GET /api/v1/invoice-income-items`. Existing income statistics now use the same
calculation.

### Filtered table totals (item 7)

Both existing list endpoints now return a top-level `totals` object, alongside
`result` and `pagination`. No additional request parameters or endpoint are needed:

- `GET /api/v1/invoices`: assigned invoices (`filter[unassigned]=0`, the default)
  and unassigned client previews (`filter[unassigned]=1`).
- `GET /api/v1/invoice-income-items`: all (`type=0`), paid (`type=1`),
  and overdue unpaid (`type=2`, preserving the existing meaning).

```json
{
  "totals": {
    "taxableAmount": 300,
    "ivaAmount": 66,
    "totalAmount": 366
  }
}
```

- `totals.taxableAmount`: sum of taxable amounts of all matching rows.
- `totals.ivaAmount`: sum of their individually rounded IVA amounts (a monetary
  value, not the rate). IVA is not recalculated on the combined base.
- `totals.totalAmount`: sum of final amounts including excluded expenses and
  any stamp. Uses `totalInvoiceAfterDiscount` on the main list and `total` on
  the income list.

All existing filters apply. Totals are calculated before pagination, so changing
`page` or `pageSize` does not change them. An out-of-range page still returns the
matching totals. No matches return three numeric zeros. On unassigned previews,
these are estimated totals of the displayed client groups, not issued revenue.
Frontend code should read these response keys instead of summing the current
page; `pagination.total` remains the number of matching rows.

### Calculation contract

`InvoiceTotalsService` supplies the list, income statistics, and document data:

1. Sum active `invoice_details.price_after_discount` and stored `extra_price`.
2. Apply the client's additional percentage to the service subtotal, respecting
   `limit_decreto` when positive.
3. Invoice discount type **0 is fixed**, **1 is percentage**, as documented in
   the existing invoice API. This is separate from client service-discount enums.
   The discount reduces the taxable base before IVA, excludes non-taxable
   expenses, and cannot exceed the positive taxable base.
4. Calculate IVA at 22% and round monetary results to two decimals.
5. Add excluded expenses and the existing stamp rule (2.00 when excluded
   expenses exceed 77.47).

PDF, XML and XLSX use `InvoiceDocumentService`. Discounts are negative document
lines. XLSX includes the stamp and, when necessary, a small IVA rounding line
so its line sum agrees with the invoice total. The legacy `type=csv` export
continues producing its existing XLSX import layout with net line amounts;
discount signs and the invoice date are corrected there too.

Stored invoice detail amounts are authoritative. Editing the service catalog
or installment does not rewrite invoice amounts. Missing/deleted source
records do not discard stored amounts. Client surcharge configuration is still
read from the client, as before; this change does not add historical tax
configuration snapshots or backfill old data.

### Invoice date filtering

The effective invoice date is `invoices.start_date`, otherwise the earliest
active linked installment's `start_at`, otherwise `invoices.created_at`.
Filtering selects a whole invoice, never a subset of its lines. The former
hardcoded January 4, 2026 detail cutoff is removed from assigned invoices.
Soft-deleted invoices, clients and invoice details are excluded.

## Payment workbook and difference investigation

`GET /api/v1/export-client-payment`

Optional filters: `clientId`, `startAt`, `endAt`, either at the top level or
inside `filter[...]`. Dates here filter **due dates**, matching the payment
schedule and all-invoice income statistics. Paid-income statistics instead
use payment dates. Invoice-list date filters use the effective invoice date
described above, so comparing those screens also requires matching date bases.

The existing four sheets remain scheduled/proposed amounts sourced from
installments and their sub-data. Their summaries now preserve negative
adjustments, and empty exports use zero instead of circular SUM formulas.
These planned amounts are not relabeled as issued revenue.

Two additional sheets make actual amounts and differences reviewable:

- **Fatture**: all matching active invoices, including task services and invoices
  without an XML number. Shows taxable amount, IVA, excluded expenses, stamp,
  final total, IDs and invoice numbers, calculated by the shared service.
- **Riconciliazione**: compares each scheduled source amount with its linked
  active invoice detail amounts in the period. Shows the pre-tax difference,
  excluded expenses separately, invoice IDs, and IDs without an XML number.
  It flags absent links, changed amounts, multiple links to investigate,
  task services outside the installment proposal and invoice-only sources.

Reconciliation compares source amounts before invoice-level surcharge,
discount, IVA and stamp. These adjustments are included in Fatture, so a sum
of proposal amounts is not directly comparable to gross invoice totals.
An absent link in a filtered period can also mean different due dates; inspect
the same export without date filters before treating it as missing data.

An XML number's presence/absence is only an investigation signal. It is not
proof of issue confirmation, delivery or payment. No automatic confirmation,
renumbering, invoice regeneration or production data modification is performed.
To identify actual AIRROOM cases, run this report against the deployed data
with its client ID and the relevant period, then inspect the flagged IDs.

## Verification

```sh
php vendor/phpunit/phpunit/phpunit --do-not-cache-result tests/Unit/InvoiceTotalsTest.php tests/Feature/InvoiceConsistencyTest.php
```

Tests use an isolated in-memory SQLite schema because the legacy migration set
does not describe every deployed column. Coverage includes numeric order before
pagination, whole-invoice date filtering, soft deletes, fixed/percentage
discounts, surcharge caps, stored extra amounts, IVA/stamp boundaries, signed
XML lines and matching payment totals, XLSX rounding, missing sources and the
payment reconciliation workbook. No production database is accessed.
