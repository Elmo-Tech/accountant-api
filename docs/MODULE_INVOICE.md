# Invoice Management Module Documentation

## Overview

The Invoice Management Module handles all operations related to invoicing completed tasks and recurring payments. It supports task-based invoices, recurring invoices, invoice details management, XML/PDF export, and email delivery. The module integrates with the Task Management and Client Management modules to create comprehensive billing solutions.

## Module Location

```
app/
├── Models/
│   └── Invoice/
│       ├── Invoice.php
│       └── InvoiceDetail.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── Private/
│   │           ├── Invoice/
│   │           │   ├── InvoiceController.php
│   │           │   ├── InvoiceDetailController.php
│   │           │   ├── RecurringInvoiceController.php
│   │           │   ├── RecurringInvoiceToAllClientsController.php
│   │           │   ├── PayInvoiceController.php
│   │           │   ├── SendInvoiceController.php
│   │           │   ├── SendEmailController.php
│   │           │   ├── ClientEmailController.php
│   │           │   └── ImageToExcelController.php
│   │           └── Reports/
│   │               ├── InvoiceReportExportController.php
│   │               └── ReportController.php
│   ├── Requests/
│   │   └── Invoice/
│   │       ├── CreateInvoiceRequest.php
│   │       └── UpdateInvoiceRequest.php
│   └── Resources/
│       └── Invoice/
│           ├── InvoiceResource.php
│           ├── AllInvoiceResource.php
│           └── AllInvoiceCollection.php
├── Services/
│   └── Invoice/
│       └── InvoiceService.php
└── Exports/
    └── InvoicesExport.php
```

## Database Schema

### invoices Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| number | VARCHAR(255) | Invoice number (auto-generated: IN_00001) |
| client_id | BIGINT | FK to clients |
| end_at | DATE | Invoice end date / due date |
| payment_type_id | BIGINT | FK to parameter_values (payment method) |
| discount_type | TINYINT | Discount type (0=fixed, 1=percentage) |
| discount_amount | DECIMAL(8,2) | Discount amount or percentage |
| bank_account_id | BIGINT | FK to client_bank_accounts |
| invoice_xml_number | VARCHAR(255) | XML invoice number for export |
| pay_status | TINYINT | Payment status (0=unpaid, 1=paid) |
| pay_date | DATE | Payment date |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

### invoice_details Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| invoice_id | BIGINT | FK to invoices |
| invoiceable_id | BIGINT | Polymorphic ID (task/installment) |
| invoiceable_type | VARCHAR(255) | Polymorphic type (Task/ClientPayInstallment/ClientPayInstallmentSubData) |
| price | DECIMAL(8,2) | Original price |
| price_after_discount | DECIMAL(8,2) | Price after client discount |
| extra_price | DECIMAL(8,2) | Extra costs (e.g., stamps) |
| description | TEXT | Custom description |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |


## Models

### 1. Invoice (app/Models/Invoice/Invoice.php)

Main invoice model with relationships.

#### Fillable Fields:

```php
'client_id', 'end_at', 'payment_type_id', 'discount_type', 'discount_amount', 'bank_account_id'
```

#### Relationships:

```php
client()          // belongsTo Client
invoiceDetails()  // hasMany InvoiceDetail
```

#### Boot Method:

```php
static::created(function ($model) {
    $model->number = 'IN_' . str_pad($model->id, 5, '0', STR_PAD_LEFT);
    $model->save();
});
```

Invoice numbers are auto-generated: IN_00001, IN_00002, etc.

### 2. InvoiceDetail (app/Models/Invoice/InvoiceDetail.php)

Invoice line items with polymorphic relationship.

#### Fillable Fields:

```php
'invoice_id', 'invoiceable_id', 'invoiceable_type', 'price', 'price_after_discount', 'extra_price', 'description'
```

#### Polymorphic Relationship:

InvoiceDetail can belong to:
- Task (App\Models\Task\Task)
- ClientPayInstallment (App\Models\Client\ClientPayInstallment)
- ClientPayInstallmentSubData (App\Models\Client\ClientPayInstallmentSubData)

## Controllers

### 1. InvoiceController (app/Http/Controllers/Api/Private/Invoice/InvoiceController.php)

Main controller for invoice CRUD operations.

#### Methods:

**index(Request $request)**
- Permission: `all_invoices`
- Returns: List of invoices (assigned or unassigned)
- Filters:
  - `clientId`: Filter by client
  - `unassigned`: 1=unassigned tasks, 0=assigned invoices
  - `startAt`, `endAt`: Date range filter
  - `hasXmlNumber`: Filter by XML export status
  - `hasProforma`: Filter by proforma status
- Groups tasks by invoice or client
- Calculates totals with discounts and taxes
- Response: `AllInvoiceCollection`

**create(Request $request)**
- Permission: `create_invoice`
- Creates invoices from completed tasks
- Request format:
```json
{
  "invoices": [
    {
      "clientId": 1,
      "endAt": "2026-03-31",
      "paymentTypeId": 5,
      "discountType": 0,
      "discountAmount": 50,
      "bankAccountId": 10,
      "taskIds": [123, 124, 125]
    }
  ]
}
```
- For each task:
  - Calculates price from service category
  - Applies client-specific discounts/taxes
  - Creates invoice detail
  - Links task to invoice
- Adjusts end date if 31-08 or 31-12 (adds 10 days)
- Returns: Success message

**edit(Request $request)**
- Parameters: `invoiceId`
- Returns: Invoice details with all line items
- Response includes:
  - Invoice number, dates, client info
  - Payment type, discount info, bank account
  - All invoice details with prices

**assignedInvoices(Request $request)** (private)
- Returns invoices that have been created
- Joins invoice_details with invoices
- Calculates totals including 22% VAT
- Groups by invoice ID
- Supports same filters as index()

**update(Request $request)**
- Updates invoice information
- (Method appears incomplete in source)


### 2. InvoiceDetailController (app/Http/Controllers/Api/Private/Invoice/InvoiceDetailController.php)

Manages individual invoice line items.

#### Methods:

**create(Request $request)**
- Creates new invoice detail manually
- Parameters:
  - `invoiceId`: Invoice ID
  - `price`: Original price
  - `priceAfterDiscount`: Discounted price
  - `description`: Line item description
- Returns: Success message

**edit(Request $request)**
- Parameters: `invoiceDetailId`
- Returns: Invoice detail information

**update(Request $request)**
- Updates invoice detail
- Parameters:
  - `invoiceDetailId`: Detail ID
  - `price`: Updated price
  - `priceAfterDiscount`: Updated discounted price
  - `description`: Updated description
- Returns: Success message

**delete(Request $request)**
- Parameters: `invoiceDetailId`
- Soft deletes invoice detail
- Returns: Success message

### 3. RecurringInvoiceController (app/Http/Controllers/Api/Private/Invoice/RecurringInvoiceController.php)

Creates invoices for recurring payments (installments).

#### Key Features:
- Creates invoices from ClientPayInstallment records
- Uses start and end dates directly from request
- No complex date calculations or holiday adjustments
- Links installment payments to invoices

### 4. PayInvoiceController (app/Http/Controllers/Api/Private/Invoice/PayInvoiceController.php)

Manages invoice payment status.

#### Methods:

**update(Request $request)**
- Marks invoice as paid
- Parameters:
  - `invoiceId`: Invoice ID
  - `payDate`: Payment date
- Sets `pay_status = 1` and `pay_date`
- Returns: Success message

### 5. InvoiceReportExportController (app/Http/Controllers/Api/Private/Reports/InvoiceReportExportController.php)

Exports invoices to XML and PDF formats for Italian electronic invoicing (FatturaPA).

#### Key Features:
- XML export for electronic invoicing system
- PDF generation for printing
- Removes accented characters (à→a, è→e, etc.)
- Validates bank account information
- Auto-increments invoice XML number
- Supports multiple payment methods (MP01-MP23)
- Handles task-based and recurring invoices

#### Methods:

**exportXml(Request $request)**
- Exports invoice to XML format
- Validates CAB, ABI, and bank name
- Increments invoice XML number from parameter_values
- Removes accented characters for compatibility
- Returns: XML file download

**exportPdf(Request $request)**
- Generates PDF invoice
- Uses Blade template
- Returns: PDF file download

## Business Logic

### Invoice Creation Flow

1. User selects completed tasks for invoicing
2. System groups tasks by client
3. For each task:
   - Get service category price
   - Apply client-specific discount/tax
   - Calculate extra costs (stamps, etc.)
4. Create invoice record
5. Create invoice details for each task
6. Link tasks to invoice
7. Calculate totals with VAT and discounts

### Price Calculation Logic

```php
// Base price from service category
$servicePrice = $task->serviceCategory->price;

// Get client discount for this service category
$clientDiscount = ClientServiceDiscount::where('client_id', $clientId)
    ->whereRaw("FIND_IN_SET(?, service_category_ids)", [$serviceCategoryId])
    ->where('is_active', 1)
    ->first();

$priceAfterDiscount = $servicePrice;

if ($clientDiscount) {
    $discountValue = $clientDiscount->discount;
    $isPercentage = $clientDiscount->type === 1; // 0=fixed, 1=percentage
    
    if ($clientDiscount->category === 1) { // TAX
        // Increase price
        $priceAfterDiscount = $isPercentage
            ? $servicePrice * (1 + $discountValue / 100)
            : $servicePrice + $discountValue;
    } else { // DISCOUNT
        // Decrease price
        $priceAfterDiscount = $isPercentage
            ? $servicePrice * (1 - $discountValue / 100)
            : max(0, $servicePrice - $discountValue);
    }
}

// Add extra costs
$extraPrice = $serviceCategory->extra_is_pricable ? $serviceCategory->extra_price : 0;
```


### Total Calculation Logic

```php
// Sum all line items
$totalPrice = sum of all prices;
$totalPriceAfterDiscount = sum of all prices after client discounts;
$totalCosts = sum of all extra prices;

// Add 22% VAT
$totalAfterVAT = $totalPriceAfterDiscount + ($totalPriceAfterDiscount * 0.22);

// Add client additional tax (if any)
if ($clientTotalTax > 0) {
    $totalAfterVAT += $totalAfterVAT * ($clientTotalTax / 100);
}

// Apply invoice-level discount
if ($invoiceDiscountType == 0) { // Fixed amount
    $totalInvoiceAfterDiscount = $totalAfterVAT - $invoiceDiscountAmount;
} else if ($invoiceDiscountType == 1) { // Percentage
    $totalInvoiceAfterDiscount = $totalAfterVAT - ($totalAfterVAT * ($invoiceDiscountAmount / 100));
}
```

### XML Export Logic

1. Validate invoice has bank account with CAB, ABI, and bank name
2. Get invoice data with all details
3. Get next invoice XML number from parameter_values (parameter_order = 13)
4. Remove accented characters from all text fields
5. Generate XML according to FatturaPA format
6. Update invoice with XML number
7. Increment parameter_value for next invoice
8. Return XML file

### Accented Character Removal

```php
$removeAccents = function($string) {
    $accents = [
        'à' => 'a', 'è' => 'e', 'é' => 'e', 'ì' => 'i', 
        'ò' => 'o', 'ù' => 'u', 'À' => 'A', 'È' => 'E', 
        'É' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U'
    ];
    return strtr($string, $accents);
};

// Apply to all text fields
$clientName = $removeAccents($client->ragione_sociale);
$bankName = $removeAccents($bankAccount->bankName);
$address = $removeAccents($clientAddress->address);
```

This ensures compatibility with Italian invoice processing software that rejects accented characters.

## API Endpoints

### Invoice Management

#### GET /api/v1/invoices
Get all invoices with filtering.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
filter[clientId]      - Filter by client
filter[unassigned]    - 1=unassigned tasks, 0=assigned invoices
filter[startAt]       - Start date (YYYY-MM-DD)
filter[endAt]         - End date (YYYY-MM-DD)
filter[hasXmlNumber]  - 1=exported, 0=not exported
filter[hasProforma]   - 1=proforma only, 0=regular only
pageSize              - Items per page (default: 10)
```

**Response:**
```json
{
  "data": [
    {
      "key": "123",
      "invoiceId": 123,
      "invoiceNumber": "IN_00123",
      "invoiceXmlNumber": "1/57",
      "invoiceDate": "2026-03-05",
      "clientId": 5,
      "clientName": "ABC Company",
      "clientAddableToBulkInvoice": 1,
      "tasks": [
        {
          "taskId": 456,
          "taskTitle": "Website Development",
          "taskNumber": "T_00456",
          "serviceCategoryName": "Web Development",
          "description": "Web Development",
          "serviceCategoryCode": "WD01",
          "price": 500.00,
          "priceAfterDiscount": 450.00,
          "extraPrice": 2.00,
          "taskCreatedAt": "01/03/2026"
        }
      ],
      "totalPrice": 500.00,
      "totalPriceAfterDiscount": 450.00,
      "totalCosts": 2.00,
      "additionalTax": 0,
      "totalAfterAdditionalTax": 549.00,
      "invoiceDiscountType": 0,
      "invoiceDiscountAmount": 0,
      "invoiceDiscount": 0,
      "totalInvoiceAfterDiscount": 549.00
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 25
  }
}
```

#### POST /api/v1/invoices/create
Create invoices from completed tasks.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "invoices": [
    {
      "clientId": 5,
      "endAt": "2026-03-31",
      "paymentTypeId": 10,
      "discountType": 0,
      "discountAmount": 50,
      "bankAccountId": 15,
      "taskIds": [123, 124, 125]
    },
    {
      "clientId": 6,
      "endAt": "2026-03-31",
      "paymentTypeId": 10,
      "discountType": 1,
      "discountAmount": 10,
      "bankAccountId": 16,
      "taskIds": [126, 127]
    }
  ]
}
```

**Response:**
```json
{
  "message": "Created successfully"
}
```

#### GET /api/v1/invoices/edit
Get invoice details for editing.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
invoiceId - Invoice ID (required)
```

**Response:**
```json
{
  "data": {
    "invoiceNumber": "IN_00123",
    "invoiceId": 123,
    "startAt": "01/03/2026",
    "endAt": "2026-03-31",
    "clientId": 5,
    "clientName": "ABC Company",
    "clientPiva": "12345678901",
    "clientCodeFiscale": "ABCDEF12G34H567I",
    "clientAddress": "Via Roma 123, Milano",
    "paymentTypeId": 10,
    "discountType": 0,
    "discountAmount": 50,
    "bankAccountId": 15,
    "invoiceDetails": [
      {
        "price": 500.00,
        "priceAfterDiscount": 450.00,
        "invoiceDetailId": 789,
        "invoiceableType": "task",
        "invoiceableId": 456,
        "description": "Web Development"
      }
    ]
  }
}
```


### Invoice Details

#### POST /api/v1/invoice-details/create
Create new invoice detail manually.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "invoiceId": 123,
  "price": 100.00,
  "priceAfterDiscount": 90.00,
  "description": "Additional service"
}
```

**Response:**
```json
{
  "message": "Created successfully"
}
```

#### GET /api/v1/invoice-details/edit
Get invoice detail for editing.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
invoiceDetailId - Invoice detail ID (required)
```

**Response:**
```json
{
  "data": {
    "invoiceDetailId": 789,
    "price": 100.00,
    "priceAfterDiscount": 90.00,
    "extraPrice": 0,
    "invoiceId": 123,
    "invoiceableId": 456,
    "invoiceableType": "App\\Models\\Task\\Task",
    "description": "Web Development"
  }
}
```

#### PUT /api/v1/invoice-details/update
Update invoice detail.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "invoiceDetailId": 789,
  "price": 120.00,
  "priceAfterDiscount": 108.00,
  "description": "Updated description"
}
```

**Response:**
```json
{
  "message": "Updated successfully"
}
```

#### DELETE /api/v1/invoice-details/delete
Delete invoice detail.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "invoiceDetailId": 789
}
```

**Response:**
```json
{
  "message": "Deleted successfully"
}
```

### Payment Management

#### PUT /api/v1/invoices/pay-invoice
Mark invoice as paid.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "invoiceId": 123,
  "payDate": "2026-03-15"
}
```

**Response:**
```json
{
  "message": "Updated successfully"
}
```

### Export

#### GET /api/v1/export-invoice-report
Export invoice to XML or PDF format.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
invoiceId - Invoice ID (required)
type      - Export format: "xml" or "pdf" (required)
```

**Response:**
- XML or PDF file download
- Filename: `invoice_{invoiceXmlNumber}.xml` or `invoice_{invoiceNumber}.pdf`

**Validation:**
- Invoice must have bank account with CAB, ABI, and bank name (for XML)
- Error if missing: "Questo cliente non ha CAB, ABI e nome banca associati"

## Usage Examples

### JavaScript/Frontend Integration

#### Fetching Unassigned Tasks for Invoicing

```javascript
async function fetchUnassignedTasks(clientId = null, startDate = null, endDate = null) {
  const params = new URLSearchParams();
  params.append('filter[unassigned]', '1');
  
  if (clientId) params.append('filter[clientId]', clientId);
  if (startDate) params.append('filter[startAt]', startDate);
  if (endDate) params.append('filter[endAt]', endDate);
  
  const response = await fetch(`/api/v1/invoices?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data;
}

// Usage
const unassignedTasks = await fetchUnassignedTasks(5, '2026-03-01', '2026-03-31');
console.log(`Found ${unassignedTasks.data.length} groups of tasks to invoice`);
```

#### Creating Invoices from Tasks

```javascript
async function createInvoices(invoicesData) {
  const response = await fetch('/api/v1/invoices/create', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      invoices: invoicesData
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage
const invoices = [
  {
    clientId: 5,
    endAt: '2026-03-31',
    paymentTypeId: 10,
    discountType: 0,
    discountAmount: 50,
    bankAccountId: 15,
    taskIds: [123, 124, 125]
  }
];

await createInvoices(invoices);
console.log('Invoices created successfully');
```

#### Fetching Assigned Invoices

```javascript
async function fetchAssignedInvoices(filters = {}) {
  const params = new URLSearchParams();
  params.append('filter[unassigned]', '0');
  
  if (filters.clientId) params.append('filter[clientId]', filters.clientId);
  if (filters.startAt) params.append('filter[startAt]', filters.startAt);
  if (filters.endAt) params.append('filter[endAt]', filters.endAt);
  if (filters.hasXmlNumber !== undefined) params.append('filter[hasXmlNumber]', filters.hasXmlNumber);
  if (filters.hasProforma !== undefined) params.append('filter[hasProforma]', filters.hasProforma);
  
  const response = await fetch(`/api/v1/invoices?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data;
}

// Usage
const invoices = await fetchAssignedInvoices({
  clientId: 5,
  startAt: '2026-03-01',
  endAt: '2026-03-31',
  hasXmlNumber: 0 // Not yet exported
});
```

#### Adding Manual Invoice Detail

```javascript
async function addInvoiceDetail(invoiceId, price, priceAfterDiscount, description) {
  const response = await fetch('/api/v1/invoice-details/create', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      invoiceId: invoiceId,
      price: price,
      priceAfterDiscount: priceAfterDiscount,
      description: description
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage
await addInvoiceDetail(123, 100.00, 90.00, 'Additional consulting service');
```

#### Marking Invoice as Paid

```javascript
async function markInvoiceAsPaid(invoiceId, payDate) {
  const response = await fetch('/api/v1/invoices/pay-invoice', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      invoiceId: invoiceId,
      payDate: payDate
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage
await markInvoiceAsPaid(123, '2026-03-15');
console.log('Invoice marked as paid');
```

#### Exporting Invoice to XML

```javascript
async function exportInvoiceXml(invoiceId) {
  const response = await fetch(`/api/v1/export-invoice-report?invoiceId=${invoiceId}&type=xml`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      invoiceId: invoiceId
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message);
  }
  
  // Download the XML file
  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `invoice_${invoiceId}.xml`;
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
  const response = await fetch(`/api/v1/export-invoice-report?invoiceId=${invoiceId}&type=pdf`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      invoiceId: invoiceId
    })
  });
  
  // Download the PDF file
  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `invoice_${invoiceId}.pdf`;
  document.body.appendChild(a);
  a.click();
  window.URL.revokeObjectURL(url);
  document.body.removeChild(a);
}

// Usage
await exportInvoicePdf(123);
```


## Permissions

The following permissions control access to invoice management features:

| Permission | Description |
|-----------|-------------|
| all_invoices | View all invoices list |
| create_invoice | Create new invoices |
| edit_invoice | View invoice details for editing |
| update_invoice | Update invoice information |
| delete_invoice | Delete invoices |

## Testing

### Manual Testing Checklist

#### Invoice Creation

- [ ] Create invoice from single task
- [ ] Create invoice from multiple tasks
- [ ] Create invoice for multiple clients at once
- [ ] Verify invoice number auto-generation (IN_00001, IN_00002, etc.)
- [ ] Verify price calculation with client discount
- [ ] Verify price calculation with client tax
- [ ] Verify extra costs are added correctly
- [ ] Verify end date adjustment for 31-08 and 31-12
- [ ] Test with different discount types (fixed/percentage)

#### Invoice Listing

- [ ] View unassigned tasks grouped by client
- [ ] View assigned invoices
- [ ] Filter by client
- [ ] Filter by date range
- [ ] Filter by XML export status
- [ ] Filter by proforma status
- [ ] Verify total calculations
- [ ] Verify VAT calculation (22%)
- [ ] Verify invoice-level discount application

#### Invoice Details

- [ ] Add manual invoice detail
- [ ] Edit invoice detail
- [ ] Delete invoice detail
- [ ] Verify polymorphic relationship (Task/ClientPayInstallment)

#### Payment Management

- [ ] Mark invoice as paid
- [ ] Verify pay_status and pay_date are set
- [ ] Filter paid/unpaid invoices

#### XML Export

- [ ] Export invoice to XML
- [ ] Verify bank account validation (CAB, ABI, bank name)
- [ ] Verify accented characters are removed
- [ ] Verify XML number auto-increment
- [ ] Verify XML format compliance (FatturaPA)
- [ ] Test with different payment methods (MP01-MP23)
- [ ] Test with task-based invoice
- [ ] Test with recurring invoice

#### PDF Export

- [ ] Export invoice to PDF
- [ ] Verify PDF formatting
- [ ] Verify all invoice details are included
- [ ] Verify bank account information
- [ ] Test with different payment methods

### API Testing with cURL

#### Create Invoice

```bash
curl -X POST https://accountant-api.testingelmo.com/api/v1/invoices/create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "invoices": [
      {
        "clientId": 5,
        "endAt": "2026-03-31",
        "paymentTypeId": 10,
        "discountType": 0,
        "discountAmount": 50,
        "bankAccountId": 15,
        "taskIds": [123, 124, 125]
      }
    ]
  }'
```

#### Get Unassigned Tasks

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/invoices?filter[unassigned]=1&filter[clientId]=5" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Get Assigned Invoices

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/invoices?filter[unassigned]=0&filter[startAt]=2026-03-01&filter[endAt]=2026-03-31" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Mark Invoice as Paid

```bash
curl -X PUT https://accountant-api.testingelmo.com/api/v1/invoices/pay-invoice \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "invoiceId": 123,
    "payDate": "2026-03-15"
  }'
```

#### Export to XML

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/export-invoice-report?invoiceId=123&type=xml" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "invoiceId": 123
  }' \
  --output invoice_123.xml
```

#### Export to PDF

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/export-invoice-report?invoiceId=123&type=pdf" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "invoiceId": 123
  }' \
  --output invoice_123.pdf
```

## Troubleshooting

### Common Issues

#### Issue: XML export fails with "Questo cliente non ha CAB, ABI e nome banca associati"

**Cause:** Client bank account is missing CAB, ABI, or bank name.

**Solution:**
1. Check client bank account: `SELECT * FROM client_bank_accounts WHERE client_id = ?`
2. Verify CAB, ABI, and bank_id are set
3. Verify bank_id links to parameter_values with bank name
4. Update bank account if needed

#### Issue: Invoice total calculation is incorrect

**Cause:** Multiple factors can affect calculation:
- Client discount not applied
- VAT not calculated correctly
- Invoice-level discount not applied
- Extra costs not included

**Solution:**
1. Verify client discount exists and is active
2. Check discount type (fixed vs percentage)
3. Verify VAT is 22% (hardcoded)
4. Check invoice discount type and amount
5. Review calculation logic in InvoiceController

#### Issue: Accented characters appear in XML

**Cause:** Text fields contain Italian accented characters (à, è, ò, etc.).

**Solution:**
- Ensure `$removeAccents` function is applied to all text fields
- Check client name, bank name, address, descriptions
- Verify XML generation uses cleaned strings

#### Issue: Invoice number not auto-generated

**Cause:** Boot method not triggered or database issue.

**Solution:**
1. Check Invoice model boot method
2. Verify invoice was saved after creation
3. Check database for invoice record
4. Manually set number if needed: `UPDATE invoices SET number = 'IN_00123' WHERE id = 123`

#### Issue: Task not linking to invoice

**Cause:** Task update failed or transaction rolled back.

**Solution:**
1. Check task record: `SELECT * FROM tasks WHERE id = ?`
2. Verify invoice_id is set
3. Check invoice_details table for polymorphic relationship
4. Review transaction logs for errors

#### Issue: XML number not incrementing

**Cause:** Parameter value not updating or transaction issue.

**Solution:**
1. Check parameter_values: `SELECT * FROM parameter_values WHERE parameter_order = 13`
2. Verify parameter_value is incrementing
3. Check transaction commit in InvoiceReportExportController
4. Manually increment if needed

### Database Queries for Debugging

#### Check invoice with details

```sql
SELECT 
    i.id,
    i.number,
    i.invoice_xml_number,
    i.client_id,
    c.ragione_sociale,
    i.end_at,
    i.discount_type,
    i.discount_amount,
    COUNT(id.id) as detail_count
FROM invoices i
JOIN clients c ON i.client_id = c.id
LEFT JOIN invoice_details id ON i.id = id.invoice_id
WHERE i.id = 123
GROUP BY i.id;
```

#### Find invoices without XML number

```sql
SELECT 
    i.id,
    i.number,
    c.ragione_sociale,
    i.created_at
FROM invoices i
JOIN clients c ON i.client_id = c.id
WHERE i.invoice_xml_number IS NULL
AND i.deleted_at IS NULL
ORDER BY i.created_at DESC;
```

#### Check invoice details with polymorphic relationships

```sql
SELECT 
    id.id,
    id.invoice_id,
    id.invoiceable_type,
    id.invoiceable_id,
    id.price,
    id.price_after_discount,
    id.extra_price,
    id.description,
    CASE 
        WHEN id.invoiceable_type = 'App\\Models\\Task\\Task' THEN t.title
        WHEN id.invoiceable_type = 'App\\Models\\Client\\ClientPayInstallment' THEN 'Recurring Payment'
        ELSE 'Other'
    END as item_description
FROM invoice_details id
LEFT JOIN tasks t ON id.invoiceable_id = t.id AND id.invoiceable_type = 'App\\Models\\Task\\Task'
WHERE id.invoice_id = 123;
```

#### Find unpaid invoices

```sql
SELECT 
    i.id,
    i.number,
    i.invoice_xml_number,
    c.ragione_sociale,
    i.end_at,
    SUM(id.price_after_discount) as total
FROM invoices i
JOIN clients c ON i.client_id = c.id
JOIN invoice_details id ON i.id = id.invoice_id
WHERE (i.pay_status IS NULL OR i.pay_status = 0)
AND i.deleted_at IS NULL
GROUP BY i.id
ORDER BY i.end_at ASC;
```

#### Calculate invoice total manually

```sql
SELECT 
    i.id,
    i.number,
    SUM(id.price) as total_price,
    SUM(id.price_after_discount) as total_after_discount,
    SUM(id.extra_price) as total_extra,
    SUM(id.price_after_discount) * 1.22 as total_with_vat,
    i.discount_type,
    i.discount_amount,
    CASE 
        WHEN i.discount_type = 0 THEN (SUM(id.price_after_discount) * 1.22) - i.discount_amount
        WHEN i.discount_type = 1 THEN (SUM(id.price_after_discount) * 1.22) * (1 - i.discount_amount / 100)
        ELSE SUM(id.price_after_discount) * 1.22
    END as final_total
FROM invoices i
JOIN invoice_details id ON i.id = id.invoice_id
WHERE i.id = 123
GROUP BY i.id;
```


### Performance Optimization

#### Indexing Recommendations

```sql
-- Index for invoice lookups
CREATE INDEX idx_invoices_client_date 
ON invoices(client_id, created_at);

-- Index for XML number lookups
CREATE INDEX idx_invoices_xml_number 
ON invoices(invoice_xml_number);

-- Index for payment status
CREATE INDEX idx_invoices_pay_status 
ON invoices(pay_status, pay_date);

-- Index for invoice details
CREATE INDEX idx_invoice_details_invoice 
ON invoice_details(invoice_id);

-- Index for polymorphic relationships
CREATE INDEX idx_invoice_details_invoiceable 
ON invoice_details(invoiceable_type, invoiceable_id);
```

#### Query Optimization

- Use eager loading for relationships: `Invoice::with('invoiceDetails', 'client')`
- Avoid N+1 queries when loading invoice lists
- Use database transactions for invoice creation
- Cache invoice totals for completed invoices
- Use raw queries for complex aggregations

## Integration with Other Modules

### Task Management Module
- Invoices are created from completed tasks (status = DONE)
- Tasks are linked to invoices via invoice_id
- Task prices are calculated from service categories
- Client discounts are applied per service category

### Client Management Module
- Invoices belong to clients
- Client bank accounts are used for payment information
- Client discounts/taxes affect invoice pricing
- Client addresses are included in invoices

### Service Category Module
- Service prices come from service categories
- Extra costs (stamps) are defined in service categories
- Service codes are used in XML export

### Parameter Module
- Payment types are stored in parameter_values
- Bank names are stored in parameter_values
- Invoice XML numbers are tracked in parameter_values (parameter_order = 13)

### Reporting Module
- InvoiceReportExportController handles XML/PDF generation
- Uses invoice data for electronic invoicing
- Integrates with Italian FatturaPA system

## Italian Electronic Invoicing (FatturaPA)

### Overview

The system supports Italian electronic invoicing (Fatturazione Elettronica) through XML export in FatturaPA format.

### Payment Methods (Modalità di Pagamento)

| Code | Description |
|------|-------------|
| MP01 | Contanti (Cash) |
| MP02 | Assegno (Check) |
| MP03 | Assegno circolare (Cashier's check) |
| MP04 | Contanti presso Tesoreria (Cash at Treasury) |
| MP05 | Bonifico (Bank transfer) |
| MP06 | Vaglia cambiario (Money order) |
| MP07 | Bollettino bancario (Bank slip) |
| MP08 | Carta di pagamento (Payment card) |
| MP09 | RID (Direct debit) |
| MP10 | RID utenze (Utility direct debit) |
| MP11 | RID veloce (Fast direct debit) |
| MP12 | RIBA (Bank receipt) |
| MP13 | MAV (Payment slip) |
| MP14 | Quietanza erario (Tax receipt) |
| MP15 | Giroconto su conti di contabilità speciale (Special account transfer) |
| MP16 | Domiciliazione bancaria (Bank domiciliation) |
| MP17 | Domiciliazione postale (Postal domiciliation) |
| MP18 | Bollettino di c/c postale (Postal account slip) |
| MP19 | SEPA Direct Debit |
| MP20 | SEPA Direct Debit CORE |
| MP21 | SEPA Direct Debit B2B |
| MP22 | Trattenuta su somme già riscosse (Withholding on amounts already collected) |
| MP23 | PagoPA |

### XML Structure

The XML export follows the FatturaPA 1.2 format with the following key sections:

1. **FatturaElettronicaHeader** (Invoice Header)
   - DatiTrasmissione (Transmission data)
   - CedentePrestatore (Supplier data)
   - CessionarioCommittente (Customer data)

2. **FatturaElettronicaBody** (Invoice Body)
   - DatiGenerali (General data)
   - DatiBeniServizi (Goods/Services data)
   - DatiPagamento (Payment data)

### Character Encoding

All text fields must use ASCII characters only. Accented characters are automatically converted:
- à → a
- è → e
- é → e
- ì → i
- ò → o
- ù → u

### Bank Account Information

For MP05 (Bonifico/Bank transfer), the following information is required:
- IBAN
- Bank name (from parameter_values)
- CAB and ABI codes

### Validation Rules

1. Client must have valid VAT number (P.IVA) or fiscal code (Codice Fiscale)
2. Client must have address
3. Bank account must have CAB, ABI, and bank name for XML export
4. Invoice must have at least one detail line
5. All prices must be positive
6. Payment type must be valid (MP01-MP23)

## Related Modules

- **Task Management**: Source of billable work
- **Client Management**: Invoice recipients and payment info
- **Service Category**: Pricing and service definitions
- **Parameter Management**: Payment types, bank names, XML numbering
- **Reporting**: XML/PDF generation and export

## Future Enhancements

- Bulk invoice creation for multiple clients
- Invoice templates
- Recurring invoice automation
- Invoice reminders and notifications
- Payment tracking and reconciliation
- Credit notes and refunds
- Multi-currency support
- Invoice approval workflow
- Integration with accounting software
- Advanced reporting and analytics
- Invoice preview before creation
- Batch XML export
- Email delivery automation
- Payment gateway integration

---

**Last Updated:** March 5, 2026  
**Module Version:** 1.0  
**Documentation Status:** Complete
