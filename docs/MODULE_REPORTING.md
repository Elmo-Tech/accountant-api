# Reporting Module Documentation

## Overview

The Reporting Module provides analytics, statistics, and export functionality for the application. It includes dashboard statistics, invoice exports (XML/PDF), task exports (Excel), and client payment exports. The module integrates with Italian electronic invoicing (FatturaPA) for XML generation and supports various export formats for data analysis.

## Module Location

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           └── Private/
│               ├── Reports/
│               │   ├── ReportController.php
│               │   └── InvoiceReportExportController.php
│               ├── Task/
│               │   └── AdminTaskExportController.php
│               └── Client/
│                   └── ClientPaymentExportController.php
├── Services/
│   └── Reports/
│       └── ReportService.php
├── Exports/
│   └── TasksExport.php
└── resources/
    └── views/
        └── invoice_pdf_report.blade.php
```

## Report Types

### 1. Dashboard Statistics
- Client count
- Invoice statistics (invoiced vs not invoiced tasks)
- Task statistics by status (to work, in progress, done)
- User-specific task counts

### 2. Invoice Reports
- XML export (FatturaPA format for Italian electronic invoicing)
- PDF export (printable invoice)
- Supports multiple payment methods
- Automatic invoice numbering
- Accented character removal for XML compatibility

### 3. Task Reports
- Excel export with task details
- Time tracking information
- Service category breakdown
- Client and user information
- Supports time > 24 hours

### 4. Client Payment Reports
- Excel export of client installments
- Main installments and sub-installments
- Payment descriptions and amounts
- Client-wise breakdown


## Controllers

### 1. ReportController (app/Http/Controllers/Api/Private/Reports/ReportController.php)

Provides dashboard statistics and analytics.

#### Methods:

**__invoke()**
- Permission: `all_reports`
- Returns: Dashboard statistics
- Response includes:
  - Total clients count
  - Invoice statistics (invoiced/not invoiced tasks)
  - Task statistics by status for current user
- Response format:

```json
{
  "clients": 150,
  "invoices": {
    "invoiced": 450,
    "notInvoiced": 75
  },
  "tasks": {
    "toWork": 12,
    "inProgress": 5,
    "done": 230
  }
}
```

### 2. InvoiceReportExportController (app/Http/Controllers/Api/Private/Reports/InvoiceReportExportController.php)

Handles invoice export to XML and PDF formats.

#### Methods:

**exportXml(Request $request)**
- Exports invoice to XML format (FatturaPA)
- Validates bank account information (CAB, ABI, bank name)
- Auto-increments invoice XML number
- Removes accented characters for compatibility
- Parameters: `invoiceId`
- Returns: XML file download

**exportPdf(Request $request)**
- Generates PDF invoice
- Uses Blade template for formatting
- Parameters: `invoiceId`
- Returns: PDF file download

#### Key Features:

1. **XML Generation (FatturaPA 1.2)**
   - Electronic invoicing format for Italy
   - Includes supplier and customer data
   - Payment information
   - Line items with prices and taxes
   - Sequential numbering

2. **Accented Character Removal**
   - Converts à→a, è→e, ò→o, etc.
   - Ensures XML compatibility
   - Applied to all text fields

3. **Bank Account Validation**
   - Requires CAB, ABI, and bank name
   - Error if missing: "Questo cliente non ha CAB, ABI e nome banca associati"

4. **Invoice Numbering**
   - Uses parameter_values (parameter_order = 13)
   - Format: "1/60", "1/61", etc.
   - Database locking for concurrency
   - Auto-increment on each export

### 3. AdminTaskExportController (app/Http/Controllers/Api/Private/Task/AdminTaskExportController.php)

Exports tasks to Excel format.

#### Methods:

**index(Request $request)**
- Exports tasks with filtering
- Supports same filters as task list
- Generates Excel file with:
  - Task number, client, title, service
  - User, total hours, start time, creation date, status
  - Sum row for total hours
- Uses PhpSpreadsheet library
- Returns: JSON with file URL

#### Excel Features:

1. **Columns**:
   - Numero ticket (Task number)
   - Cliente (Client name)
   - Oggetto (Task title)
   - Servizio (Service category)
   - Utente (User name)
   - Totale ore (Total hours in [h]:mm:ss format)
   - Ora inizio (Start time)
   - Data creazione (Creation date)
   - Stato (Status: aperto/in lavorazione/chiuso)

2. **Formatting**:
   - Bold headers
   - Auto-sized columns
   - Borders on all cells
   - Auto-filter on headers
   - Time format supports > 24 hours

3. **Sum Row**:
   - Calculates total hours across all tasks
   - Uses Excel SUM formula
   - Formatted as [h]:mm:ss

### 4. ClientPaymentExportController (app/Http/Controllers/Api/Private/Client/ClientPaymentExportController.php)

Exports client payment installments to Excel.

#### Methods:

**index(Request $request)**
- Exports all client installments
- Includes main installments and sub-installments
- Generates Excel file with:
  - Client name
  - Payment description
  - Amount
- Returns: JSON with file URL

#### Excel Structure:

```
Cliente              | Descrizione           | Totale
---------------------|----------------------|--------
ABC Company          | Monthly Service      | 500.00
ABC Company          | - Consulting         | 200.00
ABC Company          | - Development        | 300.00
XYZ Corp             | Annual Maintenance   | 1200.00
```

## Services

### ReportService (app/Services/Reports/ReportService.php)

Business logic for dashboard statistics.

#### Methods:

**reports()**
- Calculates dashboard statistics
- Returns:
  - Total clients count
  - Invoiced tasks count
  - Not invoiced tasks count
  - User's tasks by status (to work, in progress, done)
- Uses authenticated user for task filtering

## Business Logic

### Dashboard Statistics Calculation

```php
// Total clients
$clients = DB::table('clients')->count();

// Invoiced tasks
$invoiced = Task::whereNotNull('invoice_id')->count();

// Not invoiced tasks
$notInvoiced = Task::where('invoice_id', null)->count();

// User's tasks by status
$authUser = auth()->user();
$toWork = Task::where('status', TaskStatus::TO_WORK)
    ->where('user_id', $authUser->id)
    ->count();
$inProgress = Task::where('status', TaskStatus::IN_PROGRESS)
    ->where('user_id', $authUser->id)
    ->count();
$done = Task::where('status', TaskStatus::DONE)
    ->where('user_id', $authUser->id)
    ->count();
```

### Invoice XML Generation

The XML export follows FatturaPA 1.2 format with these key sections:

1. **Transmission Data** (DatiTrasmissione)
   - Sender ID
   - Transmission format
   - Recipient code

2. **Supplier Data** (CedentePrestatore)
   - Company details
   - VAT number
   - Address
   - Contact information

3. **Customer Data** (CessionarioCommittente)
   - Client details
   - VAT number or fiscal code
   - Address

4. **Invoice Body** (FatturaElettronicaBody)
   - General data (dates, numbers, currency)
   - Line items (services, prices, taxes)
   - Payment data (method, bank account)

### Excel Time Format Conversion

For tasks with time > 24 hours:

```php
private function convertToExcelTime($time)
{
    // Input: "39:18:23"
    if (preg_match('/^(\d+):(\d{2}):(\d{2})$/', $time, $matches)) {
        $hours = (int) $matches[1];    // 39
        $minutes = (int) $matches[2];  // 18
        $seconds = (int) $matches[3];  // 23
        
        // Convert to Excel decimal time
        return ($hours / 24) + ($minutes / 1440) + ($seconds / 86400);
    }
    return null;
}

// Apply format: [h]:mm:ss
$sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('[h]:mm:ss');
```


## API Endpoints

### Dashboard Statistics

#### GET /api/v1/reports
Get dashboard statistics.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "clients": 150,
  "invoices": {
    "invoiced": 450,
    "notInvoiced": 75
  },
  "tasks": {
    "toWork": 12,
    "inProgress": 5,
    "done": 230
  }
}
```

### Invoice Export

#### GET /api/v1/export-invoice-report
Export invoice to XML, PDF, or CSV format based on type parameter.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
type - Export format: 'xml', 'pdf', or 'csv'
invoiceIds[] - Array of invoice IDs to export
```

**Export XML:**
```
GET /api/v1/export-invoice-report?type=xml&invoiceIds[]=123
```

**Response (XML):**
```json
{
  "data": {
    "name": "00987920196_00060.xml",
    "content": "<?xml version=\"1.0\" encoding=\"windows-1252\"?>..."
  }
}
```

**Validation Error:**
```json
{
  "message": "Questo cliente non ha CAB, ABI e nome banca associati"
}
```

**Export PDF:**
```
GET /api/v1/export-invoice-report?type=pdf&invoiceIds[]=123
```

**Response (PDF):**
```json
{
  "path": "https://accountant-api.testingelmo.com/storage/exportedInvoices/invoice_123.pdf"
}
```

**Export CSV/Excel:**
```
GET /api/v1/export-invoice-report?type=csv&invoiceIds[]=123
```

**Response (CSV/Excel):**
```json
{
  "path": "https://accountant-api.testingelmo.com/storage/exportedInvoices/user_2026_03_05_14_30_45.xlsx"
}
```
```

**Response:**
- PDF file download
- Filename: `invoice_{invoiceNumber}.pdf`
- Content-Type: `application/pdf`

### Task Export

#### GET /api/v1/admin-ticket-export
Export tasks to Excel.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
filter[search]           - Search in task title, number, client name
filter[userId]           - Filter by assigned user
filter[status]           - Filter by status (0, 1, 2)
filter[serviceCategoryId] - Filter by service category
filter[clientId]         - Filter by client
filter[startDate]        - Filter by start date (YYYY-MM-DD)
filter[endDate]          - Filter by end date (YYYY-MM-DD)
```

**Response:**
```json
{
  "path": "https://accountant-api.testingelmo.com/storage/tasks_exports/tasks_2026_03_05_14_30_45.xlsx"
}
```

### Client Payment Export

#### GET /api/v1/export-client-payment
Export client installments to Excel.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "path": "https://accountant-api.testingelmo.com/storage/client_installments_exports/client_installments_2026_03_05_14_30_45.xlsx"
}
```

## Usage Examples

### JavaScript/Frontend Integration

#### Fetching Dashboard Statistics

```javascript
async function fetchDashboardStats() {
  const response = await fetch('/api/v1/reports', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const stats = await response.json();
  return stats;
}

// Usage
const stats = await fetchDashboardStats();
console.log(`Total clients: ${stats.clients}`);
console.log(`Invoiced tasks: ${stats.invoices.invoiced}`);
console.log(`Tasks to work: ${stats.tasks.toWork}`);
```

#### Exporting Invoice to XML

```javascript
async function exportInvoiceXml(invoiceId) {
  const response = await fetch(`/api/v1/export-invoice-report?type=xml&invoiceIds[]=${invoiceId}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message);
  }
  
  const result = await response.json();
  
  // Create and download XML file from response data
  const blob = new Blob([result.data.content], { type: 'application/xml' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = result.data.name;
  document.body.appendChild(a);
  a.click();
  window.URL.revokeObjectURL(url);
  document.body.removeChild(a);
}

// Usage with error handling
try {
  await exportInvoiceXml(123);
  console.log('XML exported successfully');
} catch (error) {
  console.error('Export failed:', error.message);
  // Error: "Questo cliente non ha CAB, ABI e nome banca associati"
}
```

#### Exporting Invoice to PDF

```javascript
async function exportInvoicePdf(invoiceId) {
  const response = await fetch(`/api/v1/export-invoice-report?type=pdf&invoiceIds[]=${invoiceId}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const result = await response.json();
  
  // Open PDF in new tab
  window.open(result.path, '_blank');
}

// Usage
await exportInvoicePdf(123);
```

#### Exporting Invoice to Excel

```javascript
async function exportInvoiceExcel(invoiceId) {
  const response = await fetch(`/api/v1/export-invoice-report?type=csv&invoiceIds[]=${invoiceId}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const result = await response.json();
  
  // Open Excel file in new tab
  window.open(result.path, '_blank');
}

// Usage
await exportInvoiceExcel(123);
```

#### Exporting Tasks to Excel

```javascript
async function exportTasks(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.search) params.append('filter[search]', filters.search);
  if (filters.userId) params.append('filter[userId]', filters.userId);
  if (filters.status !== undefined) params.append('filter[status]', filters.status);
  if (filters.clientId) params.append('filter[clientId]', filters.clientId);
  if (filters.startDate) params.append('filter[startDate]', filters.startDate);
  if (filters.endDate) params.append('filter[endDate]', filters.endDate);
  
  const response = await fetch(`/api/v1/admin-ticket-export?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const result = await response.json();
  
  // Open the file in new tab
  window.open(result.path, '_blank');
}

// Usage
await exportTasks({
  status: 2, // DONE
  startDate: '2026-03-01',
  endDate: '2026-03-31'
});
```

#### Exporting Client Payments

```javascript
async function exportClientPayments() {
  const response = await fetch('/api/v1/export-client-payment', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const result = await response.json();
  
  // Open the file in new tab
  window.open(result.path, '_blank');
}

// Usage
await exportClientPayments();
```

#### Dashboard Component

```javascript
class DashboardStats {
  constructor(containerElement) {
    this.container = containerElement;
    this.stats = null;
  }
  
  async load() {
    this.stats = await fetchDashboardStats();
    this.render();
  }
  
  render() {
    if (!this.stats) return;
    
    this.container.innerHTML = `
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Clients</h3>
          <p class="stat-value">${this.stats.clients}</p>
        </div>
        
        <div class="stat-card">
          <h3>Invoiced Tasks</h3>
          <p class="stat-value">${this.stats.invoices.invoiced}</p>
        </div>
        
        <div class="stat-card">
          <h3>Not Invoiced</h3>
          <p class="stat-value">${this.stats.invoices.notInvoiced}</p>
        </div>
        
        <div class="stat-card">
          <h3>To Work</h3>
          <p class="stat-value">${this.stats.tasks.toWork}</p>
        </div>
        
        <div class="stat-card">
          <h3>In Progress</h3>
          <p class="stat-value">${this.stats.tasks.inProgress}</p>
        </div>
        
        <div class="stat-card">
          <h3>Done</h3>
          <p class="stat-value">${this.stats.tasks.done}</p>
        </div>
      </div>
    `;
  }
  
  async refresh() {
    await this.load();
  }
}

// Usage
const dashboard = new DashboardStats(document.getElementById('dashboard'));
await dashboard.load();

// Refresh every 5 minutes
setInterval(() => dashboard.refresh(), 5 * 60 * 1000);
```


## Permissions

The following permissions control access to reporting features:

| Permission | Description |
|-----------|-------------|
| all_reports | View dashboard statistics |

Note: Export permissions are typically controlled by the respective module permissions (e.g., invoice export requires invoice permissions).

## Testing

### Manual Testing Checklist

#### Dashboard Statistics

- [ ] View dashboard statistics
- [ ] Verify client count is accurate
- [ ] Verify invoiced/not invoiced task counts
- [ ] Verify user-specific task counts
- [ ] Test with different users
- [ ] Verify counts update after data changes

#### Invoice Export

- [ ] Export invoice to XML
- [ ] Verify XML format (FatturaPA 1.2)
- [ ] Verify accented characters are removed
- [ ] Verify invoice XML number increments
- [ ] Test bank account validation
- [ ] Export invoice to PDF
- [ ] Verify PDF formatting
- [ ] Test with different payment methods
- [ ] Test with multiple line items

#### Task Export

- [ ] Export all tasks
- [ ] Export with filters (status, date range, client)
- [ ] Verify Excel formatting
- [ ] Verify time format supports > 24 hours
- [ ] Verify sum row calculation
- [ ] Test with tasks having no time logs
- [ ] Test with large datasets

#### Client Payment Export

- [ ] Export client installments
- [ ] Verify main installments are included
- [ ] Verify sub-installments are included
- [ ] Verify Excel formatting
- [ ] Test with clients having no installments

### API Testing with cURL

#### Get Dashboard Statistics

```bash
curl -X GET https://accountant-api.testingelmo.com/api/v1/reports \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Export Invoice to XML

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/export-invoice-report?type=xml&invoiceIds[]=123" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Export Invoice to PDF

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/export-invoice-report?type=pdf&invoiceIds[]=123" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Export Invoice to Excel

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/export-invoice-report?type=csv&invoiceIds[]=123" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Export Tasks

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/admin-ticket-export?filter[status]=2&filter[startDate]=2026-03-01&filter[endDate]=2026-03-31" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Export Client Payments

```bash
curl -X GET https://accountant-api.testingelmo.com/api/v1/export-client-payment \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Troubleshooting

### Common Issues

#### Issue: Dashboard statistics show incorrect counts

**Cause:** Soft-deleted records or incorrect query filters.

**Solution:**
1. Check for soft-deleted records:
   ```sql
   SELECT COUNT(*) FROM clients WHERE deleted_at IS NULL;
   SELECT COUNT(*) FROM tasks WHERE deleted_at IS NULL AND invoice_id IS NOT NULL;
   ```
2. Verify query logic in ReportService
3. Clear any caching if implemented

#### Issue: XML export fails with bank account error

**Cause:** Client bank account missing CAB, ABI, or bank name.

**Solution:**
1. Check client bank account:
   ```sql
   SELECT * FROM client_bank_accounts 
   WHERE client_id = ? AND is_main = 1;
   ```
2. Verify CAB, ABI, and bank_id are set
3. Update bank account with missing information

#### Issue: Excel file shows incorrect time format

**Cause:** Time not converted to Excel decimal format or wrong number format.

**Solution:**
1. Verify convertToExcelTime() method is used
2. Check number format is set to `[h]:mm:ss`
3. Test with time > 24 hours (e.g., "39:18:23")

#### Issue: PDF generation fails

**Cause:** Missing Blade template or data issues.

**Solution:**
1. Check template exists: `resources/views/invoice_pdf_report.blade.php`
2. Verify all required data is passed to view
3. Check for PHP errors in template
4. Verify PDF library is installed

#### Issue: Accented characters appear in XML

**Cause:** removeAccents function not applied to all fields.

**Solution:**
1. Verify removeAccents function is defined
2. Check all text fields are processed:
   - Client name
   - Bank name
   - Address
   - Descriptions
3. Test with Italian characters (à, è, ò, etc.)

### Database Queries for Debugging

#### Verify dashboard statistics

```sql
-- Total clients
SELECT COUNT(*) as total_clients 
FROM clients 
WHERE deleted_at IS NULL;

-- Invoiced tasks
SELECT COUNT(*) as invoiced_tasks 
FROM tasks 
WHERE deleted_at IS NULL 
AND invoice_id IS NOT NULL;

-- Not invoiced tasks
SELECT COUNT(*) as not_invoiced_tasks 
FROM tasks 
WHERE deleted_at IS NULL 
AND invoice_id IS NULL;

-- User's tasks by status
SELECT 
    status,
    COUNT(*) as count
FROM tasks
WHERE user_id = ?
AND deleted_at IS NULL
GROUP BY status;
```

#### Check invoice export readiness

```sql
SELECT 
    i.id,
    i.number,
    c.ragione_sociale,
    cba.iban,
    cba.cab,
    cba.abi,
    pv.parameter_value as bank_name
FROM invoices i
JOIN clients c ON i.client_id = c.id
LEFT JOIN client_bank_accounts cba ON i.bank_account_id = cba.id
LEFT JOIN parameter_values pv ON cba.bank_id = pv.id
WHERE i.id = ?;
```

#### Find tasks for export

```sql
SELECT 
    t.id,
    t.number,
    t.title,
    c.ragione_sociale as client_name,
    sc.name as service_name,
    u.first_name || ' ' || u.last_name as user_name,
    t.status,
    t.created_at
FROM tasks t
JOIN clients c ON t.client_id = c.id
JOIN service_categories sc ON t.service_category_id = sc.id
JOIN users u ON t.user_id = u.id
WHERE t.deleted_at IS NULL
AND t.status = 2
AND t.created_at BETWEEN '2026-03-01' AND '2026-03-31'
ORDER BY t.created_at DESC;
```

#### Check client installments for export

```sql
SELECT 
    c.ragione_sociale,
    pv.parameter_value as description,
    cpi.amount,
    COUNT(cpis.id) as sub_count
FROM client_pay_installments cpi
JOIN clients c ON cpi.client_id = c.id
LEFT JOIN parameter_values pv ON cpi.parameter_value_id = pv.id
LEFT JOIN client_pay_installment_sub_data cpis ON cpi.id = cpis.client_pay_installment_id
WHERE cpi.deleted_at IS NULL
GROUP BY c.id, cpi.id
ORDER BY c.ragione_sociale;
```

### Performance Optimization

#### Indexing Recommendations

```sql
-- Index for invoice export queries
CREATE INDEX idx_invoices_client_bank 
ON invoices(client_id, bank_account_id);

-- Index for task export queries
CREATE INDEX idx_tasks_export 
ON tasks(status, created_at, deleted_at);

-- Index for statistics queries
CREATE INDEX idx_tasks_invoice_status 
ON tasks(invoice_id, status, user_id);
```

#### Query Optimization

- Cache dashboard statistics (refresh every 5-10 minutes)
- Use database views for complex statistics
- Implement pagination for large exports
- Use queue jobs for large file generation
- Store generated files temporarily for re-download


## Integration with Other Modules

The Reporting Module integrates with multiple modules to provide comprehensive analytics and export functionality:

### Client Module Integration

- Dashboard statistics include total client count
- Invoice XML export requires client data (ragione_sociale, VAT, fiscal code, address)
- Client bank account validation for XML export (CAB, ABI, bank name)
- Client payment installment export includes all installments and sub-installments
- Client tax configuration affects invoice calculations

### Task Module Integration

- Dashboard shows task counts by status (to work, in progress, done)
- Task export includes all task details with time tracking
- Invoice export includes tasks as line items
- Task service categories provide service codes for XML
- Time calculations support > 24 hours format

### Invoice Module Integration

- Dashboard shows invoiced vs not invoiced task counts
- Invoice XML/PDF export functionality
- Invoice numbering from parameter_values (parameter_order = 13)
- Invoice details include tasks and client installments
- Payment method determines XML structure (MP05 vs MP12)

### Service Category Module Integration

- Service categories provide service codes for invoice line items
- Extra costs (stamps) are included as separate line items
- Service category names appear in invoice descriptions

### Parameter Module Integration

- Invoice numbering uses parameter_values (parameter_order = 13)
- Payment methods from parameter_values
- Bank account information from parameter_values
- Client installment descriptions from parameter_values

### User Module Integration

- Dashboard statistics are user-specific for tasks
- Task export includes assigned user information
- Authentication required for all report endpoints


## Italian Electronic Invoicing (FatturaPA)

The module implements full support for Italian electronic invoicing (FatturaPA 1.2 format).

### XML Structure

The generated XML follows the official FatturaPA 1.2 schema with these main sections:

#### 1. Transmission Data (DatiTrasmissione)

```xml
<DatiTrasmissione>
  <IdTrasmittente>
    <IdPaese>IT</IdPaese>
    <IdCodice>00987920196</IdCodice>
  </IdTrasmittente>
  <ProgressivoInvio>1/60</ProgressivoInvio>
  <FormatoTrasmissione>FPR12</FormatoTrasmissione>
  <CodiceDestinatario>0000000</CodiceDestinatario>
</DatiTrasmissione>
```

#### 2. Supplier Data (CedentePrestatore)

```xml
<CedentePrestatore>
  <DatiAnagrafici>
    <IdFiscaleIVA>
      <IdPaese>IT</IdPaese>
      <IdCodice>00987920196</IdCodice>
    </IdFiscaleIVA>
    <Anagrafica>
      <Denominazione>ELABORAZIONI SRL</Denominazione>
    </Anagrafica>
    <RegimeFiscale>RF01</RegimeFiscale>
  </DatiAnagrafici>
  <Sede>
    <Indirizzo>VIA STAZIONE 9/B</Indirizzo>
    <CAP>26013</CAP>
    <Comune>CREMA</Comune>
    <Provincia>CR</Provincia>
    <Nazione>IT</Nazione>
  </Sede>
</CedentePrestatore>
```

#### 3. Customer Data (CessionarioCommittente)

```xml
<CessionarioCommittente>
  <DatiAnagrafici>
    <IdFiscaleIVA>
      <IdPaese>IT</IdPaese>
      <IdCodice>{client_vat}</IdCodice>
    </IdFiscaleIVA>
    <CodiceFiscale>{client_cf}</CodiceFiscale>
    <Anagrafica>
      <Denominazione>{client_name}</Denominazione>
    </Anagrafica>
  </DatiAnagrafici>
  <Sede>
    <Indirizzo>{address}</Indirizzo>
    <CAP>{cap}</CAP>
    <Comune>{city}</Comune>
    <Provincia>{province}</Provincia>
    <Nazione>IT</Nazione>
  </Sede>
</CessionarioCommittente>
```

#### 4. Invoice Body (FatturaElettronicaBody)

```xml
<FatturaElettronicaBody>
  <DatiGenerali>
    <DatiGeneraliDocumento>
      <TipoDocumento>TD01</TipoDocumento>
      <Divisa>EUR</Divisa>
      <Data>2026-03-05</Data>
      <Numero>60</Numero>
      <ImportoTotaleDocumento>1220.00</ImportoTotaleDocumento>
    </DatiGeneraliDocumento>
  </DatiGenerali>
  <DatiBeniServizi>
    <DettaglioLinee>
      <NumeroLinea>1</NumeroLinea>
      <CodiceArticolo>
        <CodiceTipo>PRESTAZIONE</CodiceTipo>
        <CodiceValore>00000001</CodiceValore>
      </CodiceArticolo>
      <Descrizione>Consulting Services</Descrizione>
      <Quantita>1.00</Quantita>
      <PrezzoUnitario>1000.00</PrezzoUnitario>
      <PrezzoTotale>1000.00</PrezzoTotale>
      <AliquotaIVA>22.00</AliquotaIVA>
    </DettaglioLinee>
    <DatiRiepilogo>
      <AliquotaIVA>22.00</AliquotaIVA>
      <ImponibileImporto>1000.00</ImponibileImporto>
      <Imposta>220.00</Imposta>
      <EsigibilitaIVA>I</EsigibilitaIVA>
    </DatiRiepilogo>
  </DatiBeniServizi>
  <DatiPagamento>
    <CondizioniPagamento>TP02</CondizioniPagamento>
    <DettaglioPagamento>
      <ModalitaPagamento>MP05</ModalitaPagamento>
      <ImportoPagamento>1220.00</ImportoPagamento>
      <IBAN>IT60X0542811101000000123456</IBAN>
    </DettaglioPagamento>
  </DatiPagamento>
</FatturaElettronicaBody>
```

### Key Features

#### Accented Character Removal

All text fields are processed to remove Italian accented characters for XML compatibility:

```php
$removeAccents = function($string) {
    $accents = [
        'à' => 'a', 'è' => 'e', 'é' => 'e', 'ì' => 'i',
        'ò' => 'o', 'ù' => 'u', 'À' => 'A', 'È' => 'E',
        'É' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U'
    ];
    return strtr($string, $accents);
};
```

#### Sequential Invoice Numbering

Invoice XML numbers are auto-incremented using database locking:

```php
DB::transaction(function () use (&$invoiceNewNumber) {
    $parameterValue = ParameterValue::where('parameter_order', 13)
        ->lockForUpdate()
        ->first();
    
    $parameterNumber = $parameterValue->parameter_value ?? '1/60';
    $parts = explode('/', $parameterNumber);
    $currentNum = (int) ($parts[1] ?? 60);
    
    $parts[1] = $currentNum + 1;
    $invoiceNewNumber = implode('/', $parts);
    
    $parameterValue->parameter_value = $invoiceNewNumber;
    $parameterValue->save();
});
```

#### Payment Method Handling

Different payment methods require different XML structures:

- MP05 (Bank Transfer): Requires IBAN and bank name
- MP12 (SEPA Direct Debit): Requires ABI, CAB, and bank name

#### Zero VAT Items

Items with 0% VAT require special handling:

```xml
<DettaglioLinee>
  <NumeroLinea>2</NumeroLinea>
  <Descrizione>Bollo</Descrizione>
  <Quantita>1.00</Quantita>
  <PrezzoUnitario>2.00</PrezzoUnitario>
  <PrezzoTotale>2.00</PrezzoTotale>
  <AliquotaIVA>0.00</AliquotaIVA>
  <Natura>N1</Natura>
</DettaglioLinee>

<DatiRiepilogo>
  <AliquotaIVA>0.00</AliquotaIVA>
  <Natura>N1</Natura>
  <ImponibileImporto>2.00</ImponibileImporto>
  <Imposta>0.00</Imposta>
  <RiferimentoNormativo>Operazione Esclusa art.15 DPR 633/72</RiferimentoNormativo>
</DatiRiepilogo>
```

#### Passepartout Integration

When client has SDI code (not '0000000'), the system uses Passepartout as intermediary:

```xml
<IdTrasmittente>
  <IdPaese>SM</IdPaese>
  <IdCodice>03473</IdCodice>
</IdTrasmittente>
<CodiceDestinatario>{client_sdi}</CodiceDestinatario>

<TerzoIntermediarioOSoggettoEmittente>
  <DatiAnagrafici>
    <IdFiscaleIVA>
      <IdPaese>SM</IdPaese>
      <IdCodice>03473</IdCodice>
    </IdFiscaleIVA>
    <Anagrafica>
      <Denominazione>Passepartout S.p.A</Denominazione>
    </Anagrafica>
  </DatiAnagrafici>
</TerzoIntermediarioOSoggettoEmittente>
<SoggettoEmittente>TZ</SoggettoEmittente>
```

### Validation Requirements

Before XML export, the system validates:

1. Client has main bank account or any bank account
2. Bank account has CAB, ABI, and bank name (bank_id)
3. Client has valid address information
4. Invoice has valid payment method
5. All line items have valid service codes

### File Naming Convention

XML files are named according to Italian standards:

```
{VAT_NUMBER}_{INVOICE_NUMBER}.xml
Example: 00987920196_00060.xml
```

The invoice number is zero-padded to 5 digits.


## Common Use Cases

### Use Case 1: Dashboard Overview

A user logs in and wants to see an overview of their work and company statistics.

```javascript
// Fetch dashboard statistics
const stats = await fetchDashboardStats();

// Display statistics
console.log(`Total clients: ${stats.clients}`);
console.log(`Tasks to work on: ${stats.tasks.toWork}`);
console.log(`Tasks in progress: ${stats.tasks.inProgress}`);
console.log(`Completed tasks: ${stats.tasks.done}`);
console.log(`Invoiced tasks: ${stats.invoices.invoiced}`);
console.log(`Not invoiced tasks: ${stats.invoices.notInvoiced}`);
```

### Use Case 2: Generate Electronic Invoice

An accountant needs to generate an electronic invoice for submission to the Italian tax authority.

```javascript
// Export invoice to XML
try {
  await exportInvoiceXml(invoiceId);
  console.log('XML generated successfully');
  // Submit to SDI (Sistema di Interscambio)
} catch (error) {
  if (error.message.includes('CAB, ABI')) {
    console.error('Client bank account incomplete');
    // Redirect to client bank account page
  }
}
```

### Use Case 3: Print Invoice for Client

An accountant needs to print an invoice to send to the client.

```javascript
// Export invoice to PDF
await exportInvoicePdf(invoiceId);
// PDF is automatically downloaded
// Print or email to client
```

### Use Case 4: Monthly Task Report

At the end of the month, export all completed tasks for analysis.

```javascript
// Export tasks for March 2026
await exportTasks({
  status: 2, // DONE
  startDate: '2026-03-01',
  endDate: '2026-03-31'
});

// Excel file opens with:
// - All completed tasks
// - Time tracking information
// - Total hours worked
// - Client and service breakdown
```

### Use Case 5: Client Payment Overview

Generate a report of all client payment installments for financial planning.

```javascript
// Export all client installments
await exportClientPayments();

// Excel file includes:
// - Main installments
// - Sub-installments
// - Payment descriptions
// - Amounts
// - Organized by client
```

### Use Case 6: Filtered Task Export

Export tasks for a specific client and service category.

```javascript
// Export tasks for specific client and service
await exportTasks({
  clientId: 123,
  serviceCategoryId: 5,
  status: 2, // DONE
  startDate: '2026-01-01',
  endDate: '2026-03-31'
});
```


## Best Practices

### Dashboard Statistics

1. Cache statistics for 5-10 minutes to reduce database load
2. Use database indexes on frequently queried columns
3. Consider using database views for complex statistics
4. Implement real-time updates using WebSockets for critical metrics
5. Display loading states while fetching statistics

### Invoice Export

1. Always validate client bank account before XML export
2. Test XML files with official FatturaPA validator
3. Store generated XML files for audit trail
4. Implement retry mechanism for failed exports
5. Log all export operations with timestamps
6. Verify invoice numbering sequence integrity
7. Remove accented characters from all text fields
8. Test with different payment methods (MP05, MP12)
9. Validate service codes are not empty (default to '..')
10. Check for zero VAT items and add Natura field

### Task Export

1. Implement pagination for large datasets (> 10,000 tasks)
2. Use queue jobs for exports that take > 30 seconds
3. Verify time format supports > 24 hours
4. Test sum formulas with various time ranges
5. Apply filters before export to reduce file size
6. Store exported files temporarily for re-download
7. Clean up old export files periodically

### Client Payment Export

1. Include both main installments and sub-installments
2. Group by client for better readability
3. Verify amounts match database totals
4. Test with clients having no installments
5. Format currency values consistently

### Performance

1. Use database indexes on:
   - tasks.invoice_id
   - tasks.status
   - tasks.user_id
   - tasks.created_at
   - invoices.client_id
   - client_pay_installments.client_id

2. Optimize queries:
   - Use select() to limit columns
   - Eager load relationships
   - Use whereHas() instead of joins when possible
   - Implement query result caching

3. File generation:
   - Use streaming for large files
   - Implement chunking for database queries
   - Store files on CDN for faster access
   - Clean up old files automatically

### Security

1. Validate user permissions before export
2. Sanitize all input parameters
3. Prevent SQL injection in filter queries
4. Limit file download rate per user
5. Implement audit logging for all exports
6. Encrypt sensitive data in XML files
7. Validate file paths to prevent directory traversal

### Error Handling

1. Provide clear error messages for validation failures
2. Log all errors with context information
3. Implement retry mechanism for transient failures
4. Return appropriate HTTP status codes
5. Handle database transaction failures gracefully
6. Validate data before file generation
7. Check disk space before writing files


## Related Modules

- [Authentication Module](MODULE_AUTHENTICATION.md) - User authentication and authorization
- [Client Management Module](MODULE_CLIENT_MANAGEMENT.md) - Client data and bank accounts
- [Task Management Module](MODULE_TASK_MANAGEMENT.md) - Task tracking and time logs
- [Invoice Module](MODULE_INVOICE.md) - Invoice creation and management
- [Service Category Module](MODULE_SERVICE_CATEGORY.md) - Service codes and pricing
- [Parameter Module](MODULE_PARAMETERS.md) - Invoice numbering and configuration
- [User Management Module](MODULE_USER_MANAGEMENT.md) - User roles and permissions


## Future Enhancements

### Planned Features

1. **Advanced Analytics**
   - Revenue trends over time
   - Client profitability analysis
   - Service category performance metrics
   - User productivity reports
   - Time tracking analytics

2. **Scheduled Reports**
   - Automatic daily/weekly/monthly reports
   - Email delivery of reports
   - Customizable report templates
   - Report scheduling interface

3. **Data Visualization**
   - Interactive charts and graphs
   - Dashboard customization
   - Real-time statistics updates
   - Drill-down capabilities

4. **Export Enhancements**
   - Additional export formats (CSV, JSON)
   - Custom column selection
   - Export templates
   - Batch export functionality

5. **FatturaPA Enhancements**
   - Automatic SDI submission
   - XML validation before export
   - Error notification system
   - Invoice status tracking

6. **Performance Improvements**
   - Background job processing for large exports
   - Incremental export for large datasets
   - Export progress indicators
   - Parallel processing for multiple exports

7. **Audit Trail**
   - Complete export history
   - User action logging
   - Data change tracking
   - Compliance reporting

8. **Integration**
   - Third-party accounting software integration
   - API for external reporting tools
   - Webhook notifications for exports
   - Cloud storage integration (S3, Google Drive)

### Technical Improvements

1. Implement caching layer for dashboard statistics
2. Add database indexes for better query performance
3. Use queue jobs for all export operations
4. Implement file compression for large exports
5. Add unit tests for all export functions
6. Implement integration tests for XML generation
7. Add API rate limiting for export endpoints
8. Implement file cleanup scheduler
9. Add monitoring and alerting for export failures
10. Optimize database queries with query builder optimization

