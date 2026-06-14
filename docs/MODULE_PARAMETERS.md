# Parameters Module Documentation

## Overview

The Parameters Module provides a flexible configuration system for managing application-wide settings and lookup values. It uses a two-level structure: Parameters (categories) and Parameter Values (individual options). This module is used throughout the application for dropdowns, configuration values, and system settings.

## Module Location

```
app/
├── Models/
│   └── Parameter/
│       ├── Parameter.php
│       └── ParameterValue.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── Private/
│   │           └── Parameter/
│   │               └── ParameterValueController.php
│   ├── Requests/
│   │   └── Paramter/
│   │       ├── CreateParameterValueRequest.php
│   │       └── UpdateParameterValueRequest.php
│   └── Resources/
│       └── Parameter/
│           ├── ParameterValueResource.php
│           └── AllParameterValueCollection.php
└── Services/
    └── Parameter/
        └── ParameterService.php
```

## Database Schema

### parameters Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| parameter_name | VARCHAR(255) | Parameter category name |
| parameter_order | INT | Unique identifier for parameter type |
| created_by | BIGINT | User who created |
| updated_by | BIGINT | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

### parameter_values Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| parameter_id | BIGINT | FK to parameters |
| parameter_order | INT | Parameter type identifier (same as parent) |
| parameter_value | VARCHAR(255) | The actual value |
| description | TEXT | Primary description |
| description2 | TEXT | Secondary description |
| description3 | TEXT | Tertiary description |
| code | VARCHAR(255) | Optional code for the value |
| is_default | BOOLEAN | Whether this is the default value |
| created_by | BIGINT | User who created |
| updated_by | BIGINT | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

## Parameter Types (parameter_order)

The system uses `parameter_order` to identify different parameter categories:

| Order | Parameter Name | Description | Usage |
|-------|---------------|-------------|-------|
| 1 | Client Categories | Client classification types | Client management |
| 2 | Connection Types | Task connection methods | Task management |
| 3 | Payment Types | Invoice payment methods | Invoice management |
| 4 | Service Types | Service category types | Service categorization |
| 5 | User Roles | User role definitions | User management |
| 10 | Bank Names | Bank institution names | Bank account management |
| 11 | Holidays | Public holidays (d/m format) | Date calculations |
| 13 | Invoice XML Number | Current XML invoice number | Invoice export |


## Models

### 1. Parameter (app/Models/Parameter/Parameter.php)

Parameter category model.

#### Fillable Fields:

```php
'parameter_name', 'parameter_order'
```

Parameters define categories of configuration values. Each parameter has a unique `parameter_order` that identifies its type.

### 2. ParameterValue (app/Models/Parameter/ParameterValue.php)

Individual parameter value model.

#### Fillable Fields:

```php
'parameter_id', 'parameter_value', 'description', 'parameter_order', 
'is_default', 'code', 'description2', 'description3'
```

#### Scopes:

**parameterOrder($paraOrder)**
- Filters parameter values by parameter_order
- Usage: `ParameterValue::parameterOrder(10)->get()`

```php
public function scopeParameterOrder($query, $paraOrder)
{
    return $query->where('parameter_id', $paraOrder);
}
```

## Controllers

### ParameterValueController (app/Http/Controllers/Api/Private/Parameter/ParameterValueController.php)

Main controller for parameter value CRUD operations.

#### Methods:

**index(Request $request)**
- Permission: `all_parameters`
- Parameters: `parameterOrder` (required)
- Returns: Paginated list of parameter values for specified parameter type
- Query Parameters:
  - `parameterOrder`: Parameter type identifier (required)
  - `pageSize`: Items per page (default: 10)
- Response: `AllParameterValueCollection`

**create(CreateParameterValueRequest $request)**
- Permission: `create_parameter`
- Creates new parameter value
- If `isDefault` is true, unsets default flag on other values in same parameter
- Returns: Success message

**edit(Request $request)**
- Permission: `edit_parameter`
- Parameters: `parameterValueId`
- Returns: ParameterValueResource with value details

**update(UpdateParameterValueRequest $request)**
- Permission: `update_parameter`
- Updates parameter value
- If `isDefault` is changed to true, unsets default flag on other values
- Returns: Success message

**delete(Request $request)**
- Permission: `delete_parameter`
- Parameters: `parameterValueId`
- Soft deletes the parameter value
- Returns: Success message

## Services

### ParameterService (app/Services/Parameter/ParameterService.php)

Business logic for parameter operations.

#### Methods:

**allParameters($parameterOrder)**
- Retrieves all parameter values for specified parameter type
- Parameters:
  - `$parameterOrder`: Parameter type identifier
- Returns: Collection of ParameterValue

**createParameter(array $parameterData)**
- Creates new parameter value
- Looks up parameter by parameter_order
- If isDefault is true, unsets default on other values
- Parameters:
  - `parameterOrder`: Parameter type (required)
  - `parameterValue`: The value (required)
  - `description`: Description (optional)
  - `isDefault`: Is default value (optional, default: 0)
  - `code`: Code (optional)
  - `descriptionTwo`: Secondary description (optional)
  - `descriptionThree`: Tertiary description (optional)
- Returns: ParameterValue model

**editParameter(string $parameterValueId)**
- Retrieves parameter value by ID
- Returns: ParameterValue model

**updateParameter(array $parameterData)**
- Updates parameter value
- If isDefault is changed, updates other values in same parameter
- Same parameters as create plus `parameterValueId`
- Returns: ParameterValue model

**deleteParameter(string $parameterValueId)**
- Soft deletes parameter value

## Business Logic

### Default Value Management

Only one parameter value can be marked as default within each parameter type. When setting a new default:

```php
// Set new default
$parameterValue->is_default = 1;
$parameterValue->save();

// Unset other defaults in same parameter
ParameterValue::whereNot('id', $parameterValue->id)
    ->where('parameter_order', $parameterValue->parameter_order)
    ->where('is_default', 1)
    ->update(['is_default' => 0]);
```

### Parameter Order System

The `parameter_order` field serves as both:
1. A link between Parameter and ParameterValue
2. A unique identifier for parameter types throughout the application

This allows direct queries without joining tables:
```php
// Get all bank names
$banks = ParameterValue::where('parameter_order', 10)->get();

// Get current invoice XML number
$xmlNumber = ParameterValue::where('parameter_order', 13)->first();
```

### Special Parameter Types

#### Holidays (parameter_order = 11)

Holidays are stored in d/m format (e.g., "1/3" for March 1st):

```php
$holidays = ParameterValue::where('parameter_order', 11)->get();

foreach ($holidays as $holiday) {
    // $holiday->parameter_value = "1/3" (March 1st)
    // $holiday->description = "Holiday name"
}
```

Used for date calculations in recurring invoices and installment payments.

#### Invoice XML Number (parameter_order = 13)

Stores the current XML invoice number in format "1/60":

```php
DB::transaction(function () use (&$invoiceNewNumber) {
    $parameterValue = ParameterValue::where('parameter_order', 13)
        ->lockForUpdate()
        ->first();
    
    $currentNumber = $parameterValue->parameter_value; // "1/60"
    
    // Increment
    $parts = explode('/', $currentNumber);
    $parts[1] = (int)$parts[1] + 1;
    $newNumber = implode('/', $parts); // "1/61"
    
    // Update
    $parameterValue->parameter_value = $newNumber;
    $parameterValue->save();
    
    $invoiceNewNumber = $newNumber;
});
```

This ensures sequential invoice numbering with database locking.


## API Endpoints

### Parameter Value Management

#### GET /api/v1/parameter-values
Get all parameter values for a specific parameter type.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
parameterOrder - Parameter type identifier (required)
pageSize       - Items per page (default: 10)
```

**Response:**
```json
{
  "result": {
    "parameters": [
      {
        "parameterValueId": 1,
        "parameterValue": "BANCO BPM SPA",
        "description": "Banco BPM",
        "isDefault": 0,
        "code": "BPM",
        "descriptionTwo": "",
        "descriptionThree": ""
      },
      {
        "parameterValueId": 2,
        "parameterValue": "INTESA SANPAOLO",
        "description": "Intesa Sanpaolo",
        "isDefault": 1,
        "code": "ISP",
        "descriptionTwo": "",
        "descriptionThree": ""
      }
    ]
  },
  "pagination": {
    "total": 25,
    "count": 10,
    "per_page": 10,
    "current_page": 1,
    "total_pages": 3
  }
}
```

#### POST /api/v1/parameter-values/create
Create new parameter value.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "parameterOrder": 10,
  "parameterValue": "UNICREDIT",
  "description": "UniCredit Bank",
  "isDefault": 0,
  "code": "UC",
  "descriptionTwo": "Additional info",
  "descriptionThree": "More details"
}
```

**Response:**
```json
{
  "message": "Created successfully"
}
```

#### GET /api/v1/parameter-values/edit
Get parameter value details for editing.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
parameterValueId - Parameter value ID (required)
```

**Response:**
```json
{
  "parameterValueId": 1,
  "parameterValue": "BANCO BPM SPA",
  "description": "Banco BPM",
  "isDefault": 0,
  "code": "BPM",
  "descriptionTwo": "",
  "descriptionThree": ""
}
```

#### PUT /api/v1/parameter-values/update
Update parameter value.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "parameterValueId": 1,
  "parameterValue": "BANCO BPM SPA",
  "description": "Banco BPM - Updated",
  "isDefault": 1,
  "code": "BPM",
  "descriptionTwo": "Updated info",
  "descriptionThree": "Updated details"
}
```

**Response:**
```json
{
  "message": "Updated successfully"
}
```

#### DELETE /api/v1/parameter-values/delete
Delete parameter value (soft delete).

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "parameterValueId": 1
}
```

**Response:**
```json
{
  "message": "Deleted successfully"
}
```

## Validation Rules

### CreateParameterValueRequest

```php
'parameterOrder' => 'required',
'parameterValue' => 'required',
'description' => 'nullable',
'isDefault' => 'nullable',
'code' => 'nullable',
'descriptionTwo' => 'nullable',
'descriptionThree' => 'nullable'
```

### UpdateParameterValueRequest

```php
'parameterValueId' => 'required',
'parameterValue' => 'required',
'description' => 'nullable',
'isDefault' => 'nullable',
'code' => 'nullable',
'descriptionTwo' => 'nullable',
'descriptionThree' => 'nullable'
```

## Usage Examples

### JavaScript/Frontend Integration

#### Fetching Parameter Values

```javascript
async function fetchParameterValues(parameterOrder, pageSize = 100) {
  const params = new URLSearchParams();
  params.append('parameterOrder', parameterOrder);
  params.append('pageSize', pageSize);
  
  const response = await fetch(`/api/v1/parameter-values?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data;
}

// Usage - Get all bank names
const banks = await fetchParameterValues(10);
console.log(`Found ${banks.data.length} banks`);

// Usage - Get payment types
const paymentTypes = await fetchParameterValues(3);
```

#### Creating a Parameter Value

```javascript
async function createParameterValue(parameterData) {
  const response = await fetch('/api/v1/parameter-values/create', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      parameterOrder: parameterData.parameterOrder,
      parameterValue: parameterData.parameterValue,
      description: parameterData.description,
      isDefault: parameterData.isDefault || 0,
      code: parameterData.code,
      descriptionTwo: parameterData.descriptionTwo,
      descriptionThree: parameterData.descriptionThree
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage - Add new bank
await createParameterValue({
  parameterOrder: 10,
  parameterValue: 'UNICREDIT',
  description: 'UniCredit Bank',
  code: 'UC',
  isDefault: 0
});
```

#### Updating a Parameter Value

```javascript
async function updateParameterValue(parameterData) {
  const response = await fetch('/api/v1/parameter-values/update', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      parameterValueId: parameterData.parameterValueId,
      parameterValue: parameterData.parameterValue,
      description: parameterData.description,
      isDefault: parameterData.isDefault,
      code: parameterData.code,
      descriptionTwo: parameterData.descriptionTwo,
      descriptionThree: parameterData.descriptionThree
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage
await updateParameterValue({
  parameterValueId: 1,
  parameterValue: 'BANCO BPM SPA',
  description: 'Banco BPM - Updated',
  isDefault: 1,
  code: 'BPM'
});
```

#### Parameter Dropdown Component

```javascript
class ParameterDropdown {
  constructor(selectElement, parameterOrder) {
    this.selectElement = selectElement;
    this.parameterOrder = parameterOrder;
    this.values = [];
  }
  
  async load() {
    const response = await fetchParameterValues(this.parameterOrder);
    this.values = response.result.parameters;
    this.render();
  }
  
  render() {
    this.selectElement.innerHTML = '<option value="">Select...</option>';
    
    this.values.forEach(value => {
      const option = document.createElement('option');
      option.value = value.parameterValueId;
      option.textContent = value.description || value.parameterValue;
      option.dataset.code = value.code;
      option.dataset.value = value.parameterValue;
      
      if (value.isDefault) {
        option.selected = true;
      }
      
      this.selectElement.appendChild(option);
    });
  }
  
  getSelected() {
    const selectedOption = this.selectElement.selectedOptions[0];
    if (!selectedOption || !selectedOption.value) return null;
    
    return {
      id: parseInt(selectedOption.value),
      value: selectedOption.dataset.value,
      code: selectedOption.dataset.code,
      description: selectedOption.textContent
    };
  }
}

// Usage - Bank selector
const bankDropdown = new ParameterDropdown(
  document.getElementById('bank-select'),
  10 // Bank names
);
await bankDropdown.load();

// Usage - Payment type selector
const paymentDropdown = new ParameterDropdown(
  document.getElementById('payment-select'),
  3 // Payment types
);
await paymentDropdown.load();
```

#### Getting Default Value

```javascript
async function getDefaultParameterValue(parameterOrder) {
  const response = await fetchParameterValues(parameterOrder);
  const defaultValue = response.result.parameters.find(v => v.isDefault === 1);
  return defaultValue;
}

// Usage
const defaultBank = await getDefaultParameterValue(10);
if (defaultBank) {
  console.log(`Default bank: ${defaultBank.description}`);
}
```


#### Managing Holidays

```javascript
async function getHolidays() {
  const response = await fetchParameterValues(11); // Holidays
  return response.result.parameters.map(h => ({
    id: h.parameterValueId,
    date: h.parameterValue, // Format: "d/m" (e.g., "1/3")
    name: h.description
  }));
}

async function addHoliday(date, name) {
  // date format: "d/m" (e.g., "25/12" for December 25)
  await createParameterValue({
    parameterOrder: 11,
    parameterValue: date,
    description: name,
    isDefault: 0
  });
}

// Usage
const holidays = await getHolidays();
console.log('Holidays:', holidays);

// Add new holiday
await addHoliday('1/1', 'New Year\'s Day');
await addHoliday('25/12', 'Christmas Day');
```

#### Getting Current Invoice XML Number

```javascript
async function getCurrentInvoiceXmlNumber() {
  const response = await fetchParameterValues(13); // Invoice XML number
  if (response.result.parameters.length > 0) {
    return response.result.parameters[0].parameterValue; // e.g., "1/60"
  }
  return null;
}

// Usage
const currentNumber = await getCurrentInvoiceXmlNumber();
console.log(`Current XML invoice number: ${currentNumber}`);
```

## Permissions

The following permissions control access to parameter features:

| Permission | Description |
|-----------|-------------|
| all_parameters | View all parameter values list |
| create_parameter | Create new parameter values |
| edit_parameter | View parameter value details for editing |
| update_parameter | Update parameter value information |
| delete_parameter | Delete parameter values |

## Testing

### Manual Testing Checklist

#### Parameter Value CRUD

- [ ] Get parameter values for each parameter type
- [ ] Create new parameter value
- [ ] Verify unique parameter_order validation
- [ ] Edit parameter value
- [ ] Update parameter value
- [ ] Delete parameter value
- [ ] Verify soft delete

#### Default Value Management

- [ ] Set parameter value as default
- [ ] Verify other defaults are unset
- [ ] Change default to another value
- [ ] Verify only one default exists per parameter type

#### Special Parameter Types

- [ ] Add/edit bank names (parameter_order = 10)
- [ ] Add/edit holidays (parameter_order = 11)
- [ ] Verify holiday date format (d/m)
- [ ] Test invoice XML number increment (parameter_order = 13)
- [ ] Verify XML number locking in transaction

### API Testing with cURL

#### Get Parameter Values

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/parameter-values?parameterOrder=10&pageSize=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Create Parameter Value

```bash
curl -X POST https://accountant-api.testingelmo.com/api/v1/parameter-values/create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "parameterOrder": 10,
    "parameterValue": "UNICREDIT",
    "description": "UniCredit Bank",
    "isDefault": 0,
    "code": "UC"
  }'
```

#### Update Parameter Value

```bash
curl -X PUT https://accountant-api.testingelmo.com/api/v1/parameter-values/update \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "parameterValueId": 1,
    "parameterValue": "BANCO BPM SPA",
    "description": "Banco BPM - Updated",
    "isDefault": 1,
    "code": "BPM"
  }'
```

#### Delete Parameter Value

```bash
curl -X DELETE https://accountant-api.testingelmo.com/api/v1/parameter-values/delete \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "parameterValueId": 1
  }'
```

## Troubleshooting

### Common Issues

#### Issue: Multiple default values in same parameter

**Cause:** Default value logic not working correctly.

**Solution:**
1. Check parameter values:
   ```sql
   SELECT * FROM parameter_values 
   WHERE parameter_order = 10 
   AND is_default = 1;
   ```
2. Manually fix if needed:
   ```sql
   UPDATE parameter_values 
   SET is_default = 0 
   WHERE parameter_order = 10 
   AND id != {desired_default_id};
   ```

#### Issue: Invoice XML number not incrementing

**Cause:** Transaction not committing or locking issue.

**Solution:**
1. Check current value:
   ```sql
   SELECT * FROM parameter_values WHERE parameter_order = 13;
   ```
2. Verify transaction is committed
3. Check for database locks
4. Manually increment if needed:
   ```sql
   UPDATE parameter_values 
   SET parameter_value = '1/61' 
   WHERE parameter_order = 13;
   ```

#### Issue: Holiday date format incorrect

**Cause:** Date stored in wrong format.

**Solution:**
1. Holidays must be in d/m format (e.g., "1/3" for March 1st)
2. Check existing holidays:
   ```sql
   SELECT * FROM parameter_values WHERE parameter_order = 11;
   ```
3. Fix incorrect formats:
   ```sql
   UPDATE parameter_values 
   SET parameter_value = '25/12' 
   WHERE parameter_order = 11 
   AND parameter_value = '2024-12-25';
   ```

#### Issue: Parameter value not appearing in dropdown

**Cause:** Soft deleted or wrong parameter_order.

**Solution:**
1. Check if soft deleted:
   ```sql
   SELECT * FROM parameter_values 
   WHERE id = ? 
   AND deleted_at IS NULL;
   ```
2. Verify parameter_order matches:
   ```sql
   SELECT * FROM parameter_values 
   WHERE parameter_order = 10;
   ```
3. Restore if soft deleted:
   ```sql
   UPDATE parameter_values 
   SET deleted_at = NULL 
   WHERE id = ?;
   ```

### Database Queries for Debugging

#### Check all parameter types

```sql
SELECT 
    p.id,
    p.parameter_name,
    p.parameter_order,
    COUNT(pv.id) as value_count
FROM parameters p
LEFT JOIN parameter_values pv ON p.id = pv.parameter_id 
    AND pv.deleted_at IS NULL
WHERE p.deleted_at IS NULL
GROUP BY p.id
ORDER BY p.parameter_order;
```

#### Find parameter values with defaults

```sql
SELECT 
    p.parameter_name,
    p.parameter_order,
    pv.id,
    pv.parameter_value,
    pv.description,
    pv.is_default
FROM parameter_values pv
JOIN parameters p ON pv.parameter_id = p.id
WHERE pv.is_default = 1
AND pv.deleted_at IS NULL
ORDER BY p.parameter_order;
```

#### Check for duplicate defaults

```sql
SELECT 
    parameter_order,
    COUNT(*) as default_count
FROM parameter_values
WHERE is_default = 1
AND deleted_at IS NULL
GROUP BY parameter_order
HAVING COUNT(*) > 1;
```

#### Find unused parameter values

```sql
-- Check bank names not used in client_bank_accounts
SELECT 
    pv.id,
    pv.parameter_value,
    pv.description
FROM parameter_values pv
WHERE pv.parameter_order = 10
AND pv.deleted_at IS NULL
AND NOT EXISTS (
    SELECT 1 FROM client_bank_accounts cba 
    WHERE cba.bank_id = pv.id 
    AND cba.deleted_at IS NULL
)
ORDER BY pv.parameter_value;
```

#### Get parameter values by code

```sql
SELECT 
    pv.id,
    pv.parameter_value,
    pv.description,
    pv.code,
    p.parameter_name
FROM parameter_values pv
JOIN parameters p ON pv.parameter_id = p.id
WHERE pv.code = 'BPM'
AND pv.deleted_at IS NULL;
```


### Performance Optimization

#### Indexing Recommendations

```sql
-- Index for parameter_order lookups
CREATE INDEX idx_parameter_values_order 
ON parameter_values(parameter_order);

-- Index for default values
CREATE INDEX idx_parameter_values_default 
ON parameter_values(parameter_order, is_default);

-- Index for code lookups
CREATE INDEX idx_parameter_values_code 
ON parameter_values(code);

-- Index for parameter_id
CREATE INDEX idx_parameter_values_parameter 
ON parameter_values(parameter_id);
```

#### Query Optimization

- Cache frequently used parameter values (banks, payment types)
- Use parameter_order directly instead of joining with parameters table
- Preload parameter values for dropdowns on page load
- Consider Redis caching for static parameter values

## Integration with Other Modules

### Client Management Module
- Bank names (parameter_order = 10) used in client bank accounts
- Client categories (parameter_order = 1) for client classification

### Task Management Module
- Connection types (parameter_order = 2) for task connection methods
- Service types (parameter_order = 4) for service categorization

### Invoice Management Module
- Payment types (parameter_order = 3) for invoice payment methods
- Invoice XML number (parameter_order = 13) for sequential numbering
- Holidays (parameter_order = 11) for date calculations

### User Management Module
- User roles (parameter_order = 5) for role definitions

### Service Category Module
- Service types (parameter_order = 4) for categorizing services

## Common Use Cases

### 1. Adding a New Bank

```json
{
  "parameterOrder": 10,
  "parameterValue": "UNICREDIT",
  "description": "UniCredit Bank",
  "code": "UC",
  "isDefault": 0
}
```

### 2. Adding a Holiday

```json
{
  "parameterOrder": 11,
  "parameterValue": "25/12",
  "description": "Christmas Day",
  "isDefault": 0
}
```

### 3. Adding a Payment Type

```json
{
  "parameterOrder": 3,
  "parameterValue": "MP05",
  "description": "Bonifico (Bank Transfer)",
  "code": "MP05",
  "isDefault": 1
}
```

### 4. Setting Default Value

```json
{
  "parameterValueId": 5,
  "parameterValue": "INTESA SANPAOLO",
  "description": "Intesa Sanpaolo",
  "code": "ISP",
  "isDefault": 1
}
```

## Best Practices

### Parameter Value Naming

1. **Use Clear Names**: Make parameter values self-explanatory
   - Good: "INTESA SANPAOLO", "Bonifico", "Web Development"
   - Bad: "Bank1", "Type A", "Service"

2. **Consistent Formatting**: Use consistent case and format
   - Bank names: UPPERCASE
   - Descriptions: Title Case
   - Codes: UPPERCASE abbreviations

3. **Meaningful Codes**: Use standard codes when available
   - Payment types: Use Italian standard codes (MP01, MP05, etc.)
   - Bank codes: Use official abbreviations

### Default Value Management

1. **Always Have a Default**: Each parameter type should have one default value
2. **Choose Wisely**: Default should be the most commonly used option
3. **Update Carefully**: Changing defaults affects new records

### Holiday Management

1. **Use Correct Format**: Always use d/m format (e.g., "25/12")
2. **Include All Holidays**: Add all public holidays for accurate date calculations
3. **Update Annually**: Review and update holidays each year
4. **Clear Descriptions**: Use full holiday names

### Invoice XML Number

1. **Never Manually Edit**: Let the system increment automatically
2. **Use Transactions**: Always use database locking when incrementing
3. **Backup Regularly**: This is critical for invoice numbering
4. **Monitor Sequence**: Check for gaps or duplicates

## System Configuration

### Initial Setup

When setting up a new system, ensure these parameter types are configured:

1. **Bank Names (10)**: Add all banks used by clients
2. **Payment Types (3)**: Add all payment methods (MP01-MP23)
3. **Holidays (11)**: Add all public holidays
4. **Invoice XML Number (13)**: Set starting number (e.g., "1/1")
5. **Client Categories (1)**: Add client classification types
6. **Connection Types (2)**: Add task connection methods
7. **Service Types (4)**: Add service category types
8. **User Roles (5)**: Add user role definitions

### Maintenance Tasks

Regular maintenance tasks for parameters:

1. **Monthly**: Review and clean up unused parameter values
2. **Quarterly**: Update default values based on usage patterns
3. **Annually**: Update holidays for new year
4. **As Needed**: Add new banks, payment types, etc.

## Related Modules

- **Client Management**: Uses bank names and client categories
- **Task Management**: Uses connection types and service types
- **Invoice Management**: Uses payment types, XML numbers, holidays
- **User Management**: Uses user roles
- **Service Category**: Uses service types

## Future Enhancements

- Parameter value versioning
- Bulk import/export of parameter values
- Parameter value usage analytics
- Custom parameter types
- Parameter value dependencies
- Multi-language support for descriptions
- Parameter value validation rules
- Audit trail for parameter changes
- Parameter value approval workflow
- API for external parameter management
- Parameter value search and filtering
- Parameter value tags and categories
- Automated holiday updates from external source
- Parameter value expiration dates

---

**Last Updated:** March 5, 2026  
**Module Version:** 1.0  
**Documentation Status:** Complete
