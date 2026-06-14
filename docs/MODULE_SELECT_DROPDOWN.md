# Select/Dropdown Module Documentation

## Overview

The Select/Dropdown Module provides a centralized service for fetching dropdown data across the application. It supports dynamic data loading for various entities including users, clients, roles, permissions, service categories, parameters, bank accounts, and invoices. The module uses a flexible query string format to request multiple dropdown datasets in a single API call.

## Module Location

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           └── Private/
│               └── Select/
│                   └── SelectController.php
└── Services/
    └── Select/
        ├── SelectService.php
        ├── UserSelectService.php
        ├── ClientSelectService.php
        ├── RoleSelectService.php
        ├── PermissionSelectService.php
        ├── ServiceCategorySelectService.php
        ├── Parameter/
        │   └── ParameterSelectService.php
        └── Invoice/
            └── InvoiceSelectService.php
```

## Features

- Single endpoint for multiple dropdown requests
- Dynamic parameter passing (IDs, UUIDs, placeholders)
- Consistent response format (value/label pairs)
- Support for filtered data (e.g., invoices by client)
- Efficient batch loading
- Extensible service architecture

## Controllers

### SelectController (app/Http/Controllers/Api/Private/Select/SelectController.php)

Handles dropdown data requests.

#### Methods:

**getSelects(Request $request)**
- Fetches multiple dropdown datasets in one request
- Parameter: `allSelects` (comma-separated list of select types)
- Returns: Array of dropdown data with labels and options
- Example: `allSelects=users,clients,roles`

**getAllInvoices(Request $request)**
- Fetches invoices for multiple clients
- Parameter: `clientIds` (array of client IDs)
- Returns: Array of invoice dropdowns grouped by client
- Used for bulk invoice selection

## Services

### SelectService (app/Services/Select/SelectService.php)

Main service that coordinates dropdown data fetching.

#### Methods:

**getSelects(String $selects)**
- Parses comma-separated select types
- Resolves appropriate service for each type
- Handles parameter passing
- Returns formatted dropdown data

**resolveSelectService($select)**
- Maps select types to service classes
- Extracts parameters from select strings
- Supports formats:
  - `selectType` - Simple select
  - `selectType=123` - With numeric parameter
  - `selectType={placeholder}` - With placeholder
  - `selectType=uuid` - With UUID parameter

#### Supported Select Types:

| Select Type | Service | Parameter | Description |
|------------|---------|-----------|-------------|
| users | UserSelectService | None | All users |
| clients | ClientSelectService | None | All clients |
| roles | RoleSelectService | None | All roles |
| permissions | PermissionSelectService | None | All permissions |
| parameters | ParameterSelectService | parameter_id | Parameter values by parameter ID |
| serviceCategories | ServiceCategorySelectService | None | All service categories |
| bankAccounts | ParameterSelectService | None | All bank accounts |
| invoices | InvoiceSelectService | client_id (optional) | Invoices, optionally filtered by client |

### UserSelectService (app/Services/Select/UserSelectService.php)

Provides user dropdown data.

#### Methods:

**getAllUsers()**
- Returns: All users with ID and full name
- Format: `{id: value, "First Last": label}`

### ClientSelectService (app/Services/Select/ClientSelectService.php)

Provides client dropdown data.

#### Methods:

**getAllClients()**
- Returns: All clients with ID and company name
- Format: `{id: value, "Company Name": label}`

### RoleSelectService (app/Services/Select/RoleSelectService.php)

Provides role dropdown data.

#### Methods:

**getAllRoles()**
- Returns: All roles with ID and name
- Format: `{id: value, "Role Name": label}`

### ServiceCategorySelectService (app/Services/Select/ServiceCategorySelectService.php)

Provides service category dropdown data.

#### Methods:

**getAllServiceCategories()**
- Returns: All service categories with ID and name
- Format: `{id: value, "Category Name": label}`

### ParameterSelectService (app/Services/Select/Parameter/ParameterSelectService.php)

Provides parameter value dropdown data.

#### Methods:

**getAllParameters(int $parameterId)**
- Parameter: `parameterId` - The parameter ID to filter by
- Returns: Parameter values for specified parameter
- Format: `{id: value, "Parameter Value": label}`

**getAllBankAccounts()**
- Returns: Bank accounts from parameter_values (parameter_id = 7)
- Format: `{id##is_default: value, "Bank Name": label}`
- Special format includes default flag

### InvoiceSelectService (app/Services/Select/Invoice/InvoiceSelectService.php)

Provides invoice dropdown data.

#### Methods:

**getAllInvoices(?int $clientId = null)**
- Parameter: `clientId` (optional) - Filter invoices by client
- Returns: Invoices with number and date
- Format: `{id: value, "123 - 05/03/2026": label}`
- Date is from first installment start_at or invoice created_at

## API Endpoints

### Get Multiple Selects

#### GET /api/v1/selects
Fetch multiple dropdown datasets in one request.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Query Parameters:**
```
allSelects - Comma-separated list of select types
```

**Examples:**

Simple selects:
```
GET /api/v1/selects?allSelects=users,clients,roles
```

With parameters:
```
GET /api/v1/selects?allSelects=parameters=1,parameters=2,invoices=123
```

**Response:**
```json
[
  {
    "label": "users",
    "options": [
      {"value": 1, "label": "John Doe"},
      {"value": 2, "label": "Jane Smith"}
    ]
  },
  {
    "label": "clients",
    "options": [
      {"value": 1, "label": "ABC Company"},
      {"value": 2, "label": "XYZ Corp"}
    ]
  },
  {
    "label": "roles",
    "options": [
      {"value": 1, "label": "Admin"},
      {"value": 2, "label": "Accountant"}
    ]
  }
]
```

### Get Invoices for Multiple Clients

#### GET /api/v1/selects/invoices
Fetch invoices for multiple clients.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "clientIds": [1, 2, 3]
}
```

**Response:**
```json
[
  {
    "label": "invoices-1",
    "options": [
      {"value": 10, "label": "123 - 05/03/2026"},
      {"value": 11, "label": "124 - 10/03/2026"}
    ]
  },
  {
    "label": "invoices-2",
    "options": [
      {"value": 12, "label": "125 - 15/03/2026"}
    ]
  }
]
```

## Usage Examples

### JavaScript/Frontend Integration

#### Fetching Multiple Dropdowns

```javascript
async function fetchDropdowns(selectTypes) {
  const response = await fetch(`/api/v1/selects?allSelects=${selectTypes.join(',')}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data;
}

// Usage
const dropdowns = await fetchDropdowns(['users', 'clients', 'roles']);

// Access specific dropdown
const usersDropdown = dropdowns.find(d => d.label === 'users');
console.log(usersDropdown.options);
```

#### Fetching Parameters

```javascript
async function fetchParameters(parameterIds) {
  const selects = parameterIds.map(id => `parameters=${id}`);
  const response = await fetch(`/api/v1/selects?allSelects=${selects.join(',')}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data;
}

// Usage - Fetch payment types (parameter_id = 1) and bank names (parameter_id = 2)
const parameters = await fetchParameters([1, 2]);

// Access specific parameter
const paymentTypes = parameters.find(p => p.label === 'parameters1');
console.log(paymentTypes.options);
```

#### Fetching Invoices by Client

```javascript
async function fetchInvoicesByClient(clientId) {
  const response = await fetch(`/api/v1/selects?allSelects=invoices=${clientId}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data[0].options;
}

// Usage
const invoices = await fetchInvoicesByClient(123);
console.log(invoices);
```

#### Fetching Invoices for Multiple Clients

```javascript
async function fetchInvoicesForClients(clientIds) {
  const response = await fetch('/api/v1/selects/invoices', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      clientIds: clientIds
    })
  });
  
  const data = await response.json();
  return data;
}

// Usage
const invoicesByClient = await fetchInvoicesForClients([1, 2, 3]);

// Access invoices for specific client
const client1Invoices = invoicesByClient.find(d => d.label === 'invoices-1');
console.log(client1Invoices.options);
```

#### Form Initialization

```javascript
class FormDropdowns {
  constructor() {
    this.dropdowns = {};
  }
  
  async loadAll(selectTypes) {
    const data = await fetchDropdowns(selectTypes);
    
    data.forEach(dropdown => {
      this.dropdowns[dropdown.label] = dropdown.options;
    });
    
    return this.dropdowns;
  }
  
  get(label) {
    return this.dropdowns[label] || [];
  }
  
  populateSelect(selectElement, label) {
    const options = this.get(label);
    
    selectElement.innerHTML = '<option value="">Select...</option>';
    
    options.forEach(option => {
      const optionElement = document.createElement('option');
      optionElement.value = option.value;
      optionElement.textContent = option.label;
      selectElement.appendChild(optionElement);
    });
  }
}

// Usage
const formDropdowns = new FormDropdowns();
await formDropdowns.loadAll(['users', 'clients', 'serviceCategories']);

// Populate select elements
formDropdowns.populateSelect(document.getElementById('userSelect'), 'users');
formDropdowns.populateSelect(document.getElementById('clientSelect'), 'clients');
formDropdowns.populateSelect(document.getElementById('serviceSelect'), 'serviceCategories');
```

#### Dynamic Dropdown Loading

```javascript
// Load client dropdown on page load
async function initializeClientDropdown() {
  const dropdowns = await fetchDropdowns(['clients']);
  const clientSelect = document.getElementById('clientSelect');
  
  dropdowns[0].options.forEach(option => {
    const optionElement = document.createElement('option');
    optionElement.value = option.value;
    optionElement.textContent = option.label;
    clientSelect.appendChild(optionElement);
  });
}

// Load invoices when client is selected
document.getElementById('clientSelect').addEventListener('change', async (e) => {
  const clientId = e.target.value;
  
  if (clientId) {
    const invoices = await fetchInvoicesByClient(clientId);
    const invoiceSelect = document.getElementById('invoiceSelect');
    
    invoiceSelect.innerHTML = '<option value="">Select Invoice...</option>';
    
    invoices.forEach(option => {
      const optionElement = document.createElement('option');
      optionElement.value = option.value;
      optionElement.textContent = option.label;
      invoiceSelect.appendChild(optionElement);
    });
  }
});

// Initialize on page load
await initializeClientDropdown();
```

#### Caching Dropdown Data

```javascript
class DropdownCache {
  constructor(ttl = 5 * 60 * 1000) { // 5 minutes default
    this.cache = new Map();
    this.ttl = ttl;
  }
  
  async get(selectTypes) {
    const key = selectTypes.sort().join(',');
    const cached = this.cache.get(key);
    
    if (cached && Date.now() - cached.timestamp < this.ttl) {
      return cached.data;
    }
    
    const data = await fetchDropdowns(selectTypes);
    this.cache.set(key, {
      data: data,
      timestamp: Date.now()
    });
    
    return data;
  }
  
  clear() {
    this.cache.clear();
  }
  
  clearExpired() {
    const now = Date.now();
    for (const [key, value] of this.cache.entries()) {
      if (now - value.timestamp >= this.ttl) {
        this.cache.delete(key);
      }
    }
  }
}

// Usage
const dropdownCache = new DropdownCache();

// First call - fetches from API
const dropdowns1 = await dropdownCache.get(['users', 'clients']);

// Second call within 5 minutes - returns cached data
const dropdowns2 = await dropdownCache.get(['users', 'clients']);

// Clear cache when data changes
dropdownCache.clear();
```

## Business Logic

### Select Type Resolution

The system uses a mapping approach to resolve select types to service classes:

```php
$selectServiceMap = [
    'users' => ['getAllUsers', UserSelectService::class],
    'clients' => ['getAllClients', ClientSelectService::class],
    'roles' => ['getAllRoles', RoleSelectService::class],
    'permissions' => ['getAllPermissions', PermissionSelectService::class],
    'parameters' => ['getAllParameters', ParameterSelectService::class],
    'serviceCategories' => ['getAllServiceCategories', ServiceCategorySelectService::class],
    'bankAccounts' => ['getAllBankAccounts', ParameterSelectService::class],
    'invoices' => ['getAllInvoices', InvoiceSelectService::class],
];
```

### Parameter Extraction

The system supports multiple parameter formats:

```php
// Pattern: selectType=parameter
// Examples:
// parameters=1 -> parameter_id = 1
// invoices=123 -> client_id = 123
// parameters={userId} -> placeholder = userId

preg_match('/(\w+)=(?:(\b[0-9A-Fa-f\-]{36}\b)|\{([a-zA-Z]+)\}|(\d+))/', $select, $matches);
```

### Invoice Label Format

Invoices are labeled with number and date:

```php
CONCAT(invoices.number, ' - ', DATE_FORMAT(
    COALESCE(
        MAX(client_pay_installments.start_at),
        MIN(invoices.created_at)
    ),
    '%d/%m/%Y'
)) as label
```

Priority:
1. Latest installment start_at date
2. Invoice created_at date

### Bank Account Format

Bank accounts include default flag in value:

```php
CONCAT(id, '##', is_default) as value
// Example: "5##1" means bank account ID 5 is default
```

## Testing

### Manual Testing Checklist

#### Basic Selects

- [ ] Fetch users dropdown
- [ ] Fetch clients dropdown
- [ ] Fetch roles dropdown
- [ ] Fetch service categories dropdown
- [ ] Verify all options have value and label
- [ ] Verify data is not soft-deleted

#### Parameterized Selects

- [ ] Fetch parameters with parameter_id
- [ ] Fetch invoices with client_id
- [ ] Fetch bank accounts
- [ ] Verify parameter extraction works
- [ ] Test with multiple parameters

#### Multiple Selects

- [ ] Fetch multiple select types in one request
- [ ] Verify response format
- [ ] Test with 5+ select types
- [ ] Verify performance with large datasets

#### Invoice Selects

- [ ] Fetch invoices for single client
- [ ] Fetch invoices for multiple clients
- [ ] Verify date format (dd/mm/yyyy)
- [ ] Verify invoice number format
- [ ] Test with clients having no invoices

### API Testing with cURL

#### Get Multiple Selects

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/selects?allSelects=users,clients,roles" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Get Parameters

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/selects?allSelects=parameters=1,parameters=2" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Get Invoices by Client

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/selects?allSelects=invoices=123" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Get Invoices for Multiple Clients

```bash
curl -X GET https://accountant-api.testingelmo.com/api/v1/selects/invoices \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "clientIds": [1, 2, 3]
  }'
```

## Troubleshooting

### Common Issues

#### Issue: Empty dropdown options

**Cause:** No data in database or soft-deleted records.

**Solution:**
1. Check database for records:
   ```sql
   SELECT COUNT(*) FROM users WHERE deleted_at IS NULL;
   SELECT COUNT(*) FROM clients WHERE deleted_at IS NULL;
   ```
2. Verify soft delete filters in service classes
3. Check user permissions

#### Issue: Parameter not extracted correctly

**Cause:** Invalid parameter format in select string.

**Solution:**
1. Verify format: `selectType=value`
2. Check regex pattern matches parameter
3. Test with different parameter types (numeric, UUID, placeholder)

#### Issue: Invoice dates showing incorrectly

**Cause:** Date format or timezone issues.

**Solution:**
1. Verify DATE_FORMAT in query
2. Check timezone configuration
3. Verify installment start_at dates exist

#### Issue: Bank account value format incorrect

**Cause:** Concatenation not working properly.

**Solution:**
1. Check CONCAT function in query
2. Verify is_default column exists
3. Test with different bank accounts

### Database Queries for Debugging

#### Verify dropdown data

```sql
-- Users
SELECT id, CONCAT(first_name, ' ', last_name) as full_name
FROM users
WHERE deleted_at IS NULL;

-- Clients
SELECT id, ragione_sociale
FROM clients
WHERE deleted_at IS NULL;

-- Roles
SELECT id, name
FROM roles;

-- Service Categories
SELECT id, name
FROM service_categories
WHERE deleted_at IS NULL;

-- Parameters
SELECT id, parameter_value
FROM parameter_values
WHERE parameter_id = 1
AND deleted_at IS NULL;

-- Bank Accounts
SELECT CONCAT(id, '##', is_default) as value, parameter_value as label
FROM parameter_values
WHERE parameter_id = 7
AND deleted_at IS NULL;
```

#### Check invoice dropdown data

```sql
SELECT 
    i.id,
    CONCAT(
        i.number, 
        ' - ', 
        DATE_FORMAT(
            COALESCE(
                MAX(cpi.start_at),
                MIN(i.created_at)
            ),
            '%d/%m/%Y'
        )
    ) as label
FROM invoices i
LEFT JOIN invoice_details id ON i.id = id.invoice_id 
    AND id.invoiceable_type = 'App\\Models\\Client\\ClientPayInstallment'
    AND id.deleted_at IS NULL
LEFT JOIN client_pay_installments cpi ON id.invoiceable_id = cpi.id
WHERE i.client_id = 123
AND i.deleted_at IS NULL
GROUP BY i.id, i.number;
```

### Performance Optimization

#### Indexing Recommendations

```sql
-- Index for user lookups
CREATE INDEX idx_users_name ON users(first_name, last_name, deleted_at);

-- Index for client lookups
CREATE INDEX idx_clients_name ON clients(ragione_sociale, deleted_at);

-- Index for parameter lookups
CREATE INDEX idx_parameter_values_param ON parameter_values(parameter_id, deleted_at);

-- Index for invoice lookups
CREATE INDEX idx_invoices_client ON invoices(client_id, deleted_at);
```

#### Query Optimization

- Use select() to limit columns
- Add indexes on frequently queried columns
- Cache dropdown data on frontend (5-10 minutes)
- Use eager loading for relationships
- Implement pagination for large datasets

## Best Practices

### API Usage

1. Batch multiple select requests in one API call
2. Cache dropdown data on frontend to reduce API calls
3. Implement loading states while fetching data
4. Handle empty dropdown gracefully
5. Validate dropdown values before submission

### Service Implementation

1. Keep service methods simple and focused
2. Use consistent return format (value/label)
3. Apply soft delete filters
4. Use database indexes for performance
5. Document parameter requirements

### Frontend Integration

1. Cache dropdown data with TTL
2. Implement dropdown search for large datasets
3. Show loading indicators
4. Handle API errors gracefully
5. Validate selected values

### Performance

1. Limit dropdown size (max 1000 items)
2. Implement pagination for large datasets
3. Use database indexes
4. Cache frequently accessed dropdowns
5. Lazy load dropdowns when needed

### Security

1. Validate user permissions
2. Sanitize input parameters
3. Prevent SQL injection
4. Limit API rate
5. Log suspicious activity

## Related Modules

- [Authentication Module](MODULE_AUTHENTICATION.md) - User authentication
- [Client Management Module](MODULE_CLIENT_MANAGEMENT.md) - Client data
- [Task Management Module](MODULE_TASK_MANAGEMENT.md) - Task data
- [Invoice Module](MODULE_INVOICE.md) - Invoice data
- [Service Category Module](MODULE_SERVICE_CATEGORY.md) - Service category data
- [Parameter Module](MODULE_PARAMETERS.md) - Parameter values
- [User Management Module](MODULE_USER_MANAGEMENT.md) - User and role data

## Future Enhancements

### Planned Features

1. **Search and Filtering**
   - Server-side search for large dropdowns
   - Advanced filtering options
   - Multi-select support
   - Grouped options

2. **Pagination**
   - Infinite scroll for large datasets
   - Virtual scrolling
   - Load more functionality

3. **Caching**
   - Server-side caching
   - Cache invalidation strategies
   - Distributed caching

4. **Performance**
   - Query optimization
   - Database indexing
   - Response compression
   - CDN integration

5. **Features**
   - Hierarchical dropdowns
   - Dependent dropdowns
   - Custom formatting
   - Icon support

6. **API Enhancements**
   - GraphQL support
   - Batch operations
   - Webhook notifications
   - Real-time updates

### Technical Improvements

1. Implement response caching
2. Add database indexes
3. Optimize queries
4. Add unit tests
5. Implement rate limiting
6. Add monitoring and logging
7. Support for custom formatters
8. Add validation layer
9. Implement search functionality
10. Add pagination support
