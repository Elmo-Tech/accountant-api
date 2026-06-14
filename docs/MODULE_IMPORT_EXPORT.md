# Import/Export Module Documentation

## Overview

The Import/Export Module provides functionality for bulk data import and export operations. It supports importing clients, service categories, and client bank accounts from Excel files, and exporting tasks, invoices, and client payments to Excel format. The module uses the Laravel Excel (Maatwebsite) package for efficient file processing.

## Module Location

```
app/
├── Http/
│   └── Controllers/
│       ├── ImportClientController.php
│       ├── ImportServiceCategoryController.php
│       └── Api/
│           └── Private/
│               ├── Client/
│               │   ├── ImportClientBankAccountController.php
│               │   └── ClientPaymentExportController.php
│               ├── Task/
│               │   └── AdminTaskExportController.php
│               └── Reports/
│                   └── InvoiceReportExportController.php
├── Imports/
│   ├── ClientImport.php
│   ├── ServiceCategoryImport.php
│   └── ClientBankAccountImport.php
└── Exports/
    └── TasksExport.php
```

## Features

### Import Features

- Bulk client import from Excel
- Service category import from Excel
- Client bank account import from Excel
- Duplicate detection and prevention
- Header row support
- Validation and error handling
- Column format preservation (text format for IVA/CF)

### Export Features

- Task export to Excel with filters
- Invoice export to XML/PDF
- Client payment installment export
- Custom formatting and styling
- Time format support (> 24 hours)
- Auto-sized columns
- Sum calculations

## Controllers

### 1. ImportClientController (app/Http/Controllers/ImportClientController.php)

Handles bulk client import from Excel files.

#### Methods:

**index(Request $request)**
- Imports clients from Excel file
- Parameter: `path` - File path in public storage
- Uses: ClientImport class
- Prevents duplicate clients (by ragione_sociale)
- Returns: No response (void)

### 2. ImportServiceCategoryController (app/Http/Controllers/ImportServiceCategoryController.php)

Handles service category import from Excel files.

#### Methods:

**index(Request $request)**
- Imports service categories from Excel file
- Parameter: `path` - File path in public storage
- Uses: ServiceCategoryImport class
- Returns: No response (void)

### 3. ImportClientBankAccountController (app/Http/Controllers/Api/Private/Client/ImportClientBankAccountController.php)

Handles client bank account import from Excel files.

#### Methods:

**import(Request $request)**
- Imports client bank accounts from Excel file
- Parameter: `file` - Uploaded Excel file
- Validation: Required, must be xlsx/xls/csv
- Uses: ClientBankAccountImport class
- Matches clients by IVA or CF
- Sets imported accounts as main (is_main = 1)
- Returns: Success message

### 4. AdminTaskExportController (app/Http/Controllers/Api/Private/Task/AdminTaskExportController.php)

Handles task export to Excel. (See [Reporting Module](MODULE_REPORTING.md) for details)

### 5. ClientPaymentExportController (app/Http/Controllers/Api/Private/Client/ClientPaymentExportController.php)

Handles client payment export to Excel. (See [Reporting Module](MODULE_REPORTING.md) for details)

### 6. InvoiceReportExportController (app/Http/Controllers/Api/Private/Reports/InvoiceReportExportController.php)

Handles invoice export to XML/PDF. (See [Reporting Module](MODULE_REPORTING.md) for details)

## Import Classes

### 1. ClientImport (app/Imports/ClientImport.php)

Processes client data from Excel rows.

#### Implementation:

- Implements: `ToModel`, `WithHeadingRow`
- Duplicate Check: Checks if client exists by `ragione_sociale`
- Returns: `null` if client exists (skips duplicate)
- Creates: New Client with ragione_sociale, iva, cf

#### Expected Excel Columns:

| Column Name | Description | Required |
|------------|-------------|----------|
| ragione_sociale | Company name | Yes |
| pivacodfi_st | VAT/Fiscal Code | Yes |

#### Logic:

```php
public function model(array $row)
{
    $client = Client::where('ragione_sociale', $row['ragione_sociale'])->first();
    
    if ($client) {
        return null; // Skip duplicate
    }
    
    return new Client([
        'ragione_sociale' => $row['ragione_sociale'],
        'iva' => $row['pivacodfi_st'],
        'cf' => $row['pivacodfi_st'],
    ]);
}
```

### 2. ServiceCategoryImport (app/Imports/ServiceCategoryImport.php)

Processes service category data from Excel rows.

#### Implementation:

- Implements: `ToModel`, `WithHeadingRow`
- No duplicate check (creates all rows)
- Sets default values for optional fields

#### Expected Excel Columns:

| Column Name | Description | Required |
|------------|-------------|----------|
| name | Service category name | Yes |
| price | Service price | Yes |

#### Logic:

```php
public function model(array $row)
{
    return new ServiceCategory([
        'name' => $row['name'],
        'description' => null,
        'price' => $row['price'],
        'add_to_invoice' => 0,
        'service_type_id' => null
    ]);
}
```

### 3. ClientBankAccountImport (app/Imports/ClientBankAccountImport.php)

Processes client bank account data from Excel rows.

#### Implementation:

- Implements: `ToCollection`, `WithHeadingRow`
- Column Formatting: Preserves text format for IVA/CF columns
- Client Matching: Matches by IVA (priority) or CF
- Skips: Invalid rows (missing CF and IVA) and non-existent clients

#### Expected Excel Columns:

| Column Name | Description | Required |
|------------|-------------|----------|
| cf | Fiscal Code | One of CF/IVA required |
| iva | VAT Number | One of CF/IVA required |
| abi | ABI code | Yes |
| cab | CAB code | Yes |

#### Logic:

```php
public function collection(Collection $rows)
{
    foreach ($rows as $row) {
        $cf  = trim($row['cf'] ?? '');
        $iva = trim($row['iva'] ?? '');
        
        if (!$cf && !$iva) {
            continue; // Skip invalid rows
        }
        
        $clientQuery = Client::query();
        
        if ($iva !== '') {
            $clientQuery->where('iva', $iva);
        } else {
            $clientQuery->where('cf', $cf);
        }
        
        $client = $clientQuery->first();
        
        if (!$client) {
            continue; // Client not found, skip
        }
        
        ClientBankAccount::create([
            'client_id' => $client->id,
            'iban'      => '', // Always empty
            'abi'       => $row['abi'] ?? null,
            'cab'       => $row['cab'] ?? null,
            'is_main'   => 1,
        ]);
    }
}
```

## Export Classes

### TasksExport (app/Exports/TasksExport.php)

Exports task collection to Excel.

#### Implementation:

- Implements: `FromCollection`
- Accepts: Pre-formatted task collection
- Returns: Collection as-is

#### Logic:

```php
public function collection()
{
    return $this->tasksCollection;
}
```

Note: The actual formatting and styling is done in AdminTaskExportController.

## API Endpoints

### Import Endpoints

#### POST /api/v1/import-clients
Import clients from Excel file.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "path": "uploads/clients.xlsx"
}
```

**Response:**
```
No response (void)
```

**Excel Format:**
```
| ragione_sociale | pivacodfi_st |
|----------------|--------------|
| ABC Company    | 12345678901  |
| XYZ Corp       | 98765432109  |
```

#### POST /api/v1/import-service-categories
Import service categories from Excel file.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "path": "uploads/services.xlsx"
}
```

**Response:**
```
No response (void)
```

**Excel Format:**
```
| name              | price  |
|-------------------|--------|
| Consulting        | 100.00 |
| Development       | 150.00 |
```

#### POST /api/v1/import-client-bank-accounts
Import client bank accounts from Excel file.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body:**
```
file: [Excel file]
```

**Validation:**
- file: required, mimes:xlsx,xls,csv

**Response:**
```json
{
  "message": "Bank accounts imported successfully"
}
```

**Excel Format:**
```
| cf          | iva         | abi   | cab   |
|-------------|-------------|-------|-------|
| RSSMRA80A01 | 12345678901 | 03069 | 09400 |
|             | 98765432109 | 05034 | 11300 |
```

### Export Endpoints

See [Reporting Module](MODULE_REPORTING.md) for export endpoint documentation:
- GET /api/v1/export-invoice-report?invoiceId={id}&type=xml
- GET /api/v1/export-invoice-report?invoiceId={id}&type=pdf
- GET /api/v1/admin-ticket-export
- GET /api/v1/export-client-payment

## Usage Examples

### JavaScript/Frontend Integration

#### Importing Clients

```javascript
async function importClients(filePath) {
  const response = await fetch('/api/v1/import-clients', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      path: filePath
    })
  });
  
  if (response.ok) {
    console.log('Clients imported successfully');
  }
}

// Usage
await importClients('uploads/clients.xlsx');
```

#### Importing Service Categories

```javascript
async function importServiceCategories(filePath) {
  const response = await fetch('/api/v1/import-service-categories', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      path: filePath
    })
  });
  
  if (response.ok) {
    console.log('Service categories imported successfully');
  }
}

// Usage
await importServiceCategories('uploads/services.xlsx');
```

#### Importing Client Bank Accounts

```javascript
async function importClientBankAccounts(file) {
  const formData = new FormData();
  formData.append('file', file);
  
  const response = await fetch('/api/v1/import-client-bank-accounts', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  
  const result = await response.json();
  console.log(result.message);
}

// Usage with file input
const fileInput = document.getElementById('bankAccountFile');
fileInput.addEventListener('change', async (e) => {
  const file = e.target.files[0];
  if (file) {
    await importClientBankAccounts(file);
  }
});
```

#### File Upload Component

```javascript
class ImportUploader {
  constructor(endpoint, onSuccess, onError) {
    this.endpoint = endpoint;
    this.onSuccess = onSuccess;
    this.onError = onError;
  }
  
  async upload(file) {
    const formData = new FormData();
    formData.append('file', file);
    
    try {
      const response = await fetch(this.endpoint, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        },
        body: formData
      });
      
      if (!response.ok) {
        throw new Error('Upload failed');
      }
      
      const result = await response.json();
      this.onSuccess(result);
    } catch (error) {
      this.onError(error);
    }
  }
}

// Usage
const bankAccountUploader = new ImportUploader(
  '/api/v1/import-client-bank-accounts',
  (result) => console.log('Success:', result.message),
  (error) => console.error('Error:', error.message)
);

// Upload file
const file = document.getElementById('fileInput').files[0];
await bankAccountUploader.upload(file);
```

#### Batch Import with Progress

```javascript
class BatchImporter {
  constructor() {
    this.queue = [];
    this.progress = 0;
  }
  
  addToQueue(endpoint, filePath) {
    this.queue.push({ endpoint, filePath });
  }
  
  async processQueue() {
    const total = this.queue.length;
    
    for (let i = 0; i < total; i++) {
      const { endpoint, filePath } = this.queue[i];
      
      await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ path: filePath })
      });
      
      this.progress = ((i + 1) / total) * 100;
      console.log(`Progress: ${this.progress}%`);
    }
    
    console.log('All imports completed');
  }
}

// Usage
const importer = new BatchImporter();
importer.addToQueue('/api/v1/import-clients', 'uploads/clients.xlsx');
importer.addToQueue('/api/v1/import-service-categories', 'uploads/services.xlsx');
await importer.processQueue();
```

## Business Logic

### Client Import Logic

1. Read Excel file with header row
2. For each row:
   - Check if client exists by `ragione_sociale`
   - If exists: Skip row
   - If not exists: Create new client
3. Set both `iva` and `cf` to same value from `pivacodfi_st`

### Service Category Import Logic

1. Read Excel file with header row
2. For each row:
   - Create new service category
   - Set default values for optional fields
3. No duplicate checking

### Client Bank Account Import Logic

1. Read Excel file with header row
2. Preserve text format for IVA/CF columns
3. For each row:
   - Validate CF or IVA exists
   - Find client by IVA (priority) or CF
   - If client not found: Skip row
   - Create bank account with ABI, CAB
   - Set as main account (is_main = 1)
   - Leave IBAN empty

### Export Logic

See [Reporting Module](MODULE_REPORTING.md) for export logic details.

## Testing

### Manual Testing Checklist

#### Client Import

- [ ] Import Excel with valid clients
- [ ] Import Excel with duplicate clients
- [ ] Verify duplicates are skipped
- [ ] Import Excel with missing columns
- [ ] Verify error handling
- [ ] Check client data in database

#### Service Category Import

- [ ] Import Excel with valid service categories
- [ ] Import Excel with missing price
- [ ] Verify all rows are imported
- [ ] Check service category data in database

#### Client Bank Account Import

- [ ] Import Excel with valid bank accounts
- [ ] Import Excel with IVA only
- [ ] Import Excel with CF only
- [ ] Import Excel with non-existent clients
- [ ] Verify non-existent clients are skipped
- [ ] Verify is_main is set to 1
- [ ] Verify IBAN is empty
- [ ] Check bank account data in database

#### Export Testing

See [Reporting Module](MODULE_REPORTING.md) for export testing.

### API Testing with cURL

#### Import Clients

```bash
curl -X POST https://accountant-api.testingelmo.com/api/v1/import-clients \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "path": "uploads/clients.xlsx"
  }'
```

#### Import Service Categories

```bash
curl -X POST https://accountant-api.testingelmo.com/api/v1/import-service-categories \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "path": "uploads/services.xlsx"
  }'
```

#### Import Client Bank Accounts

```bash
curl -X POST https://accountant-api.testingelmo.com/api/v1/import-client-bank-accounts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@bank_accounts.xlsx"
```

## Troubleshooting

### Common Issues

#### Issue: Import fails silently

**Cause:** Invalid file path or file not found.

**Solution:**
1. Verify file exists in storage:
   ```bash
   ls storage/app/public/uploads/
   ```
2. Check file path is correct
3. Verify file permissions

#### Issue: Duplicate clients not detected

**Cause:** ragione_sociale doesn't match exactly.

**Solution:**
1. Check for extra spaces or special characters
2. Verify case sensitivity
3. Query database:
   ```sql
   SELECT ragione_sociale FROM clients WHERE ragione_sociale LIKE '%ABC%';
   ```

#### Issue: Bank accounts not imported

**Cause:** Client not found by IVA or CF.

**Solution:**
1. Verify client exists:
   ```sql
   SELECT * FROM clients WHERE iva = '12345678901' OR cf = '12345678901';
   ```
2. Check IVA/CF format in Excel (text format, not number)
3. Verify no leading/trailing spaces

#### Issue: Excel columns not recognized

**Cause:** Header row missing or incorrect column names.

**Solution:**
1. Verify first row contains headers
2. Check column names match exactly (case-sensitive)
3. Remove any special characters from headers

#### Issue: IVA/CF imported as scientific notation

**Cause:** Excel formatting IVA/CF as numbers.

**Solution:**
1. Format columns as text in Excel before import
2. Use `WithColumnFormatting` in import class
3. Add apostrophe before number in Excel ('12345678901)

### Database Queries for Debugging

#### Check imported clients

```sql
SELECT * FROM clients 
WHERE created_at > NOW() - INTERVAL 1 HOUR
ORDER BY created_at DESC;
```

#### Check imported service categories

```sql
SELECT * FROM service_categories 
WHERE created_at > NOW() - INTERVAL 1 HOUR
ORDER BY created_at DESC;
```

#### Check imported bank accounts

```sql
SELECT 
    cba.*,
    c.ragione_sociale,
    c.iva,
    c.cf
FROM client_bank_accounts cba
JOIN clients c ON cba.client_id = c.id
WHERE cba.created_at > NOW() - INTERVAL 1 HOUR
ORDER BY cba.created_at DESC;
```

#### Find clients without bank accounts

```sql
SELECT c.*
FROM clients c
LEFT JOIN client_bank_accounts cba ON c.id = cba.client_id
WHERE cba.id IS NULL;
```

### Performance Optimization

#### Indexing Recommendations

```sql
-- Index for client duplicate check
CREATE INDEX idx_clients_ragione ON clients(ragione_sociale);

-- Index for bank account client matching
CREATE INDEX idx_clients_iva_cf ON clients(iva, cf);

-- Index for bank account lookups
CREATE INDEX idx_bank_accounts_client ON client_bank_accounts(client_id, is_main);
```

#### Import Optimization

- Use batch inserts for large datasets
- Disable foreign key checks during import
- Use database transactions
- Implement chunk processing for large files
- Add progress tracking for user feedback

## Best Practices

### Import Best Practices

1. **File Validation**
   - Validate file format before import
   - Check file size limits
   - Verify header row exists
   - Validate required columns

2. **Data Validation**
   - Validate data types
   - Check for required fields
   - Sanitize input data
   - Handle special characters

3. **Error Handling**
   - Log import errors
   - Provide detailed error messages
   - Implement rollback on failure
   - Track import statistics

4. **Performance**
   - Use chunk processing for large files
   - Implement batch inserts
   - Add progress indicators
   - Optimize database queries

5. **User Experience**
   - Show import progress
   - Provide import summary
   - Display error details
   - Allow import preview

### Export Best Practices

See [Reporting Module](MODULE_REPORTING.md) for export best practices.

### Excel File Preparation

1. **Client Import File**
   - Include header row: ragione_sociale, pivacodfi_st
   - Remove empty rows
   - Trim whitespace
   - Check for duplicates

2. **Service Category Import File**
   - Include header row: name, price
   - Validate price format (numeric)
   - Remove empty rows

3. **Bank Account Import File**
   - Include header row: cf, iva, abi, cab
   - Format IVA/CF as text (not number)
   - Validate ABI/CAB format (5 digits)
   - Remove empty rows
   - Verify client exists before import

### Security

1. Validate file types (xlsx, xls, csv only)
2. Limit file size (max 10MB)
3. Sanitize file names
4. Validate user permissions
5. Log all import operations
6. Implement rate limiting
7. Scan files for malware

## Related Modules

- [Client Management Module](MODULE_CLIENT_MANAGEMENT.md) - Client data structure
- [Service Category Module](MODULE_SERVICE_CATEGORY.md) - Service category data
- [Reporting Module](MODULE_REPORTING.md) - Export functionality
- [Authentication Module](MODULE_AUTHENTICATION.md) - User authentication

## Future Enhancements

### Planned Features

1. **Import Enhancements**
   - CSV format support
   - Import validation preview
   - Duplicate resolution options
   - Bulk update support
   - Import templates download
   - Column mapping interface

2. **Export Enhancements**
   - Additional export formats (CSV, JSON)
   - Custom column selection
   - Export templates
   - Scheduled exports
   - Email export results

3. **Error Handling**
   - Detailed error reports
   - Row-level error tracking
   - Import rollback option
   - Partial import support
   - Error notification system

4. **Performance**
   - Background job processing
   - Chunk processing for large files
   - Progress tracking
   - Queue management
   - Parallel processing

5. **User Interface**
   - Drag-and-drop file upload
   - Import preview
   - Column mapping wizard
   - Import history
   - Export scheduler

6. **Validation**
   - Advanced data validation
   - Custom validation rules
   - Data transformation
   - Duplicate detection options
   - Data cleansing

### Technical Improvements

1. Implement queue jobs for large imports
2. Add comprehensive error logging
3. Implement import/export history tracking
4. Add unit tests for import classes
5. Implement data validation layer
6. Add import preview functionality
7. Implement rollback mechanism
8. Add monitoring and alerting
9. Optimize database queries
10. Implement caching for lookups
