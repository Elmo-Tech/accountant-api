# Client Management Module Documentation

## Overview

The Client Management Module handles all operations related to clients (customers), including their basic information, addresses, bank accounts, contacts, service discounts, and payment installments.

## Module Location

```
app/
├── Models/
│   └── Client/
│       ├── Client.php
│       ├── ClientAddress.php
│       ├── ClientBankAccount.php
│       ├── ClientContact.php
│       ├── ClientServiceDiscount.php
│       ├── ClientPayInstallment.php
│       └── ClientPayInstallmentSubData.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── Private/
│   │           └── Client/
│   │               ├── ClientController.php
│   │               ├── ClientAddressController.php
│   │               ├── ClientBankAccountController.php
│   │               ├── ClientContactController.php
│   │               ├── ClientServiceCategoryDiscountController.php
│   │               ├── ClientPayInstallmentController.php
│   │               ├── ClientPayInstallmentSubDataController.php
│   │               ├── ClientPayInstallmentDividerController.php
│   │               ├── ClientPayInstallmentEndDateController.php
│   │               ├── ClientPaymentTypeController.php
│   │               ├── ClientPaymentPeriodController.php
│   │               ├── ClientPaymentExportController.php
│   │               └── ImportClientBankAccountController.php
│   ├── Requests/
│   │   └── Client/
│   │       ├── CreateClientRequest.php
│   │       ├── UpdateClientRequest.php
│   │       └── ... (other request classes)
│   └── Resources/
│       └── Client/
│           ├── ClientResource.php
│           ├── AllClientResource.php
│           └── ... (other resource classes)
├── Services/
│   └── Client/
│       ├── ClientService.php
│       ├── ClientAddressService.php
│       ├── ClientBankAccountService.php
│       ├── ClientContactService.php
│       └── ClientServiceDiscountService.php
└── Imports/
    ├── ClientImport.php
    └── ClientBankAccountImport.php
```

## Database Schema

### clients Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| payment_type_id | BIGINT | FK to parameter_values (payment method) |
| pay_steps_id | BIGINT | FK to parameter_values (installment count) |
| payment_type_two_id | BIGINT | FK to parameter_values (secondary payment) |
| iban | VARCHAR(255) | Client's IBAN |
| abi | VARCHAR(255) | Italian bank code |
| cab | VARCHAR(255) | Italian branch code |
| iva | VARCHAR(60) | VAT number (Partita IVA) |
| ragione_sociale | VARCHAR(255) | Company name |
| cf | VARCHAR(60) | Tax code (Codice Fiscale) |
| note | TEXT | Additional notes |
| email | VARCHAR(255) | Client email |
| phone | VARCHAR(255) | Client phone |
| hours_per_month | VARCHAR(20) | Monthly hours allocation |
| price | DECIMAL(8,2) | Monthly price |
| allowed_days_to_pay | INT | Payment terms in days |
| addable_to_bulk_invoice | BOOLEAN | Can be included in bulk invoicing (0=No, 1=Yes) |
| monthly_price | DECIMAL(8,2) | Monthly subscription price |
| is_company | BOOLEAN | Is company or individual |
| has_recurring_invoice | BOOLEAN | Auto-generate recurring invoices |
| proforma | BOOLEAN | Generate proforma invoices |
| total_tax | DECIMAL(10,2) | Additional tax percentage (e.g., 4.00 for 4%) |
| total_tax_description | TEXT | Description for additional tax line item |
| sdi | VARCHAR(7) | SDI code for electronic invoicing |
| sdi_code | VARCHAR(7) | Alternative SDI code |
| created_by | BIGINT | User who created |
| updated_by | BIGINT | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |


### Related Tables

#### client_addresses Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| client_id | BIGINT | FK to clients |
| address | TEXT | Street address |
| city | VARCHAR(255) | City name |
| province | VARCHAR(255) | Province/state |
| cap | VARCHAR(10) | Postal code (CAP) |
| created_by | BIGINT | User who created |
| updated_by | BIGINT | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

#### client_bank_accounts Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| client_id | BIGINT | FK to clients |
| bank_id | BIGINT | FK to parameter_values (bank name) |
| iban | VARCHAR(255) | IBAN number |
| abi | VARCHAR(255) | Italian bank code |
| cab | VARCHAR(255) | Italian branch code |
| is_main | BOOLEAN | Is primary bank account |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

#### client_contacts Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| client_id | BIGINT | FK to clients |
| name | VARCHAR(255) | Contact person name |
| email | VARCHAR(255) | Contact email |
| phone | VARCHAR(255) | Contact phone |
| cf | VARCHAR(60) | Contact tax code |
| created_by | BIGINT | User who created |
| updated_by | BIGINT | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

#### client_service_discounts Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| client_id | BIGINT | FK to clients |
| service_category_id | BIGINT | FK to service_categories |
| discount_type | TINYINT | Discount type (0=percentage, 1=fixed) |
| discount_amount | DECIMAL(8,2) | Discount value |
| price | DECIMAL(8,2) | Custom price for this client |
| status | TINYINT | Active/inactive status |
| show | TINYINT | Show in client view |
| category | TINYINT | Discount category |
| created_by | BIGINT | User who created |
| updated_by | BIGINT | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

#### client_pay_installments Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| client_id | BIGINT | FK to clients |
| parameter_value_id | BIGINT | FK to parameter_values (service type) |
| amount | DECIMAL(8,2) | Total installment amount |
| start_at | DATE | Installment start date |
| end_at | DATE | Installment end date |
| payment_type_id | BIGINT | FK to parameter_values (payment method) |
| invoice_id | BIGINT | FK to invoices (if invoiced) |
| created_by | BIGINT | User who created |
| updated_by | BIGINT | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

#### client_pay_installment_sub_data Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| client_pay_installment_id | BIGINT | FK to client_pay_installments |
| parameter_value_id | BIGINT | FK to parameter_values (service type) |
| price | DECIMAL(8,2) | Installment payment amount |
| start_at | DATE | Payment due date start |
| end_at | DATE | Payment due date end |
| payment_type_id | BIGINT | FK to parameter_values (payment method) |
| invoice_id | BIGINT | FK to invoices (if invoiced) |
| created_by | BIGINT | User who created |
| updated_by | BIGINT | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

## Components

### 1. Client CRUD Operations

#### ClientController

**File**: `app/Http/Controllers/Api/Private/Client/ClientController.php`

**Purpose**: Handle HTTP requests for client management

**Middleware**:
- `auth:api` - JWT authentication required
- Permission-based access control:
  - `all_clients` - View clients list
  - `create_client` - Create new client
  - `edit_client` - View client details
  - `update_client` - Update client
  - `delete_client` - Delete client

**Methods**:

##### index(Request $request)
- **Purpose**: List all clients with filtering and pagination
- **Query Parameters**:
  - `filter[search]` - Search by name, VAT, tax code
  - `filter[clientId]` - Filter by specific client ID
  - `pageSize` - Number of items per page (default: 10)
- **Process**:
  1. Call ClientService->allClients()
  2. Apply filters using Spatie Query Builder
  3. Paginate results
  4. Transform using AllClientCollection
- **Response**: Paginated collection of clients

##### create(CreateClientRequest $request)
- **Purpose**: Create new client with all related data
- **Transaction**: Uses database transaction for data integrity
- **Process**:
  1. Validate request data
  2. Create client record
  3. Create related addresses
  4. Create related contacts
  5. Create service discounts
  6. Create bank accounts
  7. Create payment installments with sub-data
  8. Commit transaction
- **Response**: Success message
- **Rollback**: On any error, all changes are rolled back


##### edit(Request $request)
- **Purpose**: Get client details for editing
- **Query Parameters**: `clientId` - Client ID
- **Process**:
  1. Load client with all relationships:
     - addresses
     - contacts
     - payInstallments (with sub-data and parameter values)
  2. Transform using ClientResource
- **Response**: Complete client data with all related entities

##### update(UpdateClientRequest $request)
- **Purpose**: Update client and related data
- **Transaction**: Uses database transaction
- **Special Logic**:
  - If `has_recurring_invoice` is not enabled:
    - Deletes existing payment installments
    - Creates new installments from request
  - Preserves previous `pay_steps_id` for comparison
- **Process**:
  1. Find existing client
  2. Update client data
  3. Handle payment installments based on recurring invoice flag
  4. Commit transaction
- **Response**: Success message

##### delete(Request $request)
- **Purpose**: Soft delete client
- **Query Parameters**: `clientId` - Client ID
- **Process**:
  1. Find client
  2. Soft delete (sets deleted_at timestamp)
  3. Related data remains but is hidden
- **Response**: Success message
- **Note**: Uses soft deletes, data can be recovered

#### ClientService

**File**: `app/Services/Client/ClientService.php`

**Purpose**: Business logic for client operations

**Methods**:

##### allClients()
```php
public function allClients()
```
- **Purpose**: Get all clients with filtering
- **Filters**:
  - `clientId` - Exact match on client ID
  - `search` - Custom filter (searches name, VAT, tax code)
- **Uses**: Spatie Query Builder for filtering
- **Returns**: Collection of Client models

##### createClient(array $clientData)
```php
public function createClient(array $clientData)
```
- **Purpose**: Create new client record
- **Parameters**: Array with client data (camelCase keys)
- **Special Handling**:
  - `total_tax`: Converts comma to dot for decimal (e.g., "2,5" → 2.5)
  - `addable_to_bulk_invoice`: Converts to enum value
- **Returns**: Created Client model

##### editClient(string $clientId)
```php
public function editClient(string $clientId)
```
- **Purpose**: Load client with all relationships
- **Eager Loading**:
  - addresses
  - contacts
  - payInstallments
  - payInstallments.payInstallmentSubData
  - payInstallments.payInstallmentSubData.parameterValue
  - payInstallments.parameterValue
- **Returns**: Client model with relationships

##### updateClient(array $clientData)
```php
public function updateClient(array $clientData)
```
- **Purpose**: Update existing client
- **Process**:
  1. Find client by ID
  2. Fill with new data
  3. Save changes
- **Returns**: Updated Client model

##### deleteClient(string $clientId)
```php
public function deleteClient(string $clientId)
```
- **Purpose**: Soft delete client
- **Process**: Calls delete() on model (soft delete)


### 2. Client Addresses

#### ClientAddressController

**File**: `app/Http/Controllers/Api/Private/Client/ClientAddressController.php`

**Endpoints**:
- `GET /api/v1/client-addresses?clientId={id}` - List addresses
- `POST /api/v1/client-addresses/create` - Create address
- `GET /api/v1/client-addresses/edit?id={id}` - Get address
- `PUT /api/v1/client-addresses/update` - Update address
- `DELETE /api/v1/client-addresses/delete?id={id}` - Delete address

**Purpose**: Manage client physical addresses for invoicing

#### ClientAddressService

**File**: `app/Services/Client/ClientAddressService.php`

**Key Methods**:
- `createAddress(array $data)` - Create new address
- `updateAddress(array $data)` - Update existing address
- `deleteAddress(string $id)` - Delete address

### 3. Client Bank Accounts

#### ClientBankAccountController

**File**: `app/Http/Controllers/Api/Private/Client/ClientBankAccountController.php`

**Endpoints**:
- `GET /api/v1/client-bank-accounts?clientId={id}` - List bank accounts
- `POST /api/v1/client-bank-accounts/create` - Create bank account
- `GET /api/v1/client-bank-accounts/edit?id={id}` - Get bank account
- `PUT /api/v1/client-bank-accounts/update` - Update bank account
- `DELETE /api/v1/client-bank-accounts/delete?id={id}` - Delete bank account

**Purpose**: Manage client banking information for direct debit payments

**Key Features**:
- Support for multiple bank accounts per client
- `is_main` flag to mark primary account
- Bank name linked to parameter_values via `bank_id`
- Stores IBAN, ABI, CAB for Italian banking

#### ClientBankAccountService

**File**: `app/Services/Client/ClientBankAccountService.php`

**Key Methods**:
- `createClientBankAccount(array $data)` - Create bank account
- `updateClientBankAccount(array $data)` - Update bank account
- `deleteClientBankAccount(string $id)` - Delete bank account

**Bank Account Selection Logic**:
When retrieving bank account for invoicing:
1. Try to get main bank account (`is_main = 1`)
2. If not found, get any bank account for the client
3. If none exist, return empty

### 4. Client Contacts

#### ClientContactController

**File**: `app/Http/Controllers/Api/Private/Client/ClientContactController.php`

**Endpoints**:
- `GET /api/v1/client-contacts?clientId={id}` - List contacts
- `POST /api/v1/client-contacts/create` - Create contact
- `GET /api/v1/client-contacts/edit?id={id}` - Get contact
- `PUT /api/v1/client-contacts/update` - Update contact
- `DELETE /api/v1/client-contacts/delete?id={id}` - Delete contact

**Purpose**: Manage contact persons for client communication

#### ClientContactService

**File**: `app/Services/Client/ClientContactService.php`

**Key Methods**:
- `createContact(array $data)` - Create contact person
- `updateContact(array $data)` - Update contact
- `deleteContact(string $id)` - Delete contact

### 5. Client Service Discounts

#### ClientServiceCategoryDiscountController

**File**: `app/Http/Controllers/Api/Private/Client/ClientServiceCategoryDiscountController.php`

**Endpoints**:
- `GET /api/v1/client-service-discounts?clientId={id}` - List discounts
- `POST /api/v1/client-service-discounts/create` - Create discount
- `GET /api/v1/client-service-discounts/edit?id={id}` - Get discount
- `PUT /api/v1/client-service-discounts/update` - Update discount
- `DELETE /api/v1/client-service-discounts/delete?id={id}` - Delete discount
- `POST /api/v1/client-service-discounts/changeShow` - Toggle visibility

**Purpose**: Manage client-specific pricing and discounts for services

**Permissions Required**:
- `all_client_service_discounts` - View list
- `create_client_service_discount` - Create
- `edit_client_service_discount` - View details
- `update_client_service_discount` - Update
- `delete_client_service_discount` - Delete

#### ClientServiceDiscountService

**File**: `app/Services/Client/ClientServiceDiscountService.php`

**Key Methods**:

##### createClientServiceDiscount(array $data)
- Creates discount/custom pricing for a service
- Links client to service category
- Sets discount type (percentage or fixed)
- Sets custom price if applicable

##### changeShow(string $id, bool $isShow)
- Toggles visibility of discount in client view
- Used to show/hide discounts in UI

**Discount Types**:
- **Percentage** (0): Discount as percentage of base price
- **Fixed** (1): Fixed amount discount

**Use Cases**:
1. **Custom Pricing**: Set different price for specific client
2. **Percentage Discount**: Apply 10% discount on service
3. **Fixed Discount**: Reduce price by €50
4. **Visibility Control**: Show/hide discounts in client portal


### 6. Payment Installments

#### ClientPayInstallmentController

**File**: `app/Http/Controllers/Api/Private/Client/ClientPayInstallmentController.php`

**Endpoints**:
- `GET /api/v1/client-pay-installments?clientId={id}` - List installments
- `POST /api/v1/client-pay-installments/create` - Create installment plan
- `GET /api/v1/client-pay-installments/edit?id={id}` - Get installment
- `PUT /api/v1/client-pay-installments/update` - Update installment
- `DELETE /api/v1/client-pay-installments/delete?id={id}` - Delete installment

**Purpose**: Manage payment installment plans for clients

#### ClientPayInstallmentSubDataController

**File**: `app/Http/Controllers/Api/Private/Client/ClientPayInstallmentSubDataController.php`

**Endpoints**:
- `GET /api/v1/client-pay-installment-sub-data?installmentId={id}` - List sub-installments
- `POST /api/v1/client-pay-installment-sub-data/create` - Create sub-installment
- `GET /api/v1/client-pay-installment-sub-data/edit?id={id}` - Get sub-installment
- `PUT /api/v1/client-pay-installment-sub-data/update` - Update sub-installment
- `PUT /api/v1/client-pay-installment-sub-data/delete` - Delete sub-installment

**Purpose**: Manage individual installment payments within a plan

#### ClientPayInstallmentDividerController

**File**: `app/Http/Controllers/Api/Private/Client/ClientPayInstallmentDividerController.php`

**Endpoint**: `GET /api/v1/client-pay-installment-divider`

**Purpose**: Calculate installment schedule automatically

**Query Parameters**:
- `clientId` - Client ID
- `price` - Total amount to divide
- `payStepsId` - Number of installments (from parameter_values)
- `startDate` - First payment date

**Process**:
1. Get installment count from parameter_values
2. Get client payment terms (allowed_days_to_pay)
3. Calculate amount per installment
4. Calculate payment dates:
   - Add payment period between installments
   - Skip weekends (move to Monday)
   - Handle month-end dates
   - Special handling for August 31 and December 31
5. Return installment schedule

**Response**:
```json
{
  "data": {
    "payInstallments": [
      {
        "startAt": "2024-01-01",
        "endAt": "2024-01-31",
        "parameterValueName": "",
        "amount": 100.00,
        "paymentTypeId": 1,
        "payInstallmentSubData": []
      },
      {
        "startAt": "2024-02-01",
        "endAt": "2024-02-29",
        "parameterValueName": "",
        "amount": 100.00,
        "paymentTypeId": 1,
        "payInstallmentSubData": []
      }
    ]
  }
}
```

#### ClientPayInstallmentEndDateController

**File**: `app/Http/Controllers/Api/Private/Client/ClientPayInstallmentEndDateController.php`

**Endpoint**: `GET /api/v1/installment-end-at`

**Purpose**: Calculate end date for installment based on start date and payment terms

### 7. Additional Controllers

#### ClientPaymentTypeController

**File**: `app/Http/Controllers/Api/Private/Client/ClientPaymentTypeController.php`

**Endpoint**: `GET /api/v1/client-payment-type`

**Purpose**: Get available payment types for dropdowns

#### ClientPaymentPeriodController

**File**: `app/Http/Controllers/Api/Private/Client/ClientPaymentPeriodController.php`

**Endpoint**: `GET /api/v1/client-payment-period`

**Purpose**: Get available payment periods for dropdowns

#### ClientPaymentExportController

**File**: `app/Http/Controllers/Api/Private/Client/ClientPaymentExportController.php`

**Endpoint**: `GET /api/v1/export-client-payment`

**Purpose**: Export client payment history to Excel/CSV

**Query Parameters**:
- `client_id` - Client ID
- `start_date` - Period start
- `end_date` - Period end

#### ImportClientBankAccountController

**File**: `app/Http/Controllers/Api/Private/Client/ImportClientBankAccountController.php`

**Endpoint**: `POST /api/v1/import-client-bank-accounts`

**Purpose**: Bulk import bank accounts from Excel/CSV

**Uses**: `ClientBankAccountImport` class

## API Endpoints Reference

### Client CRUD

**List Clients**
```http
GET /api/v1/clients?filter[search]=company&pageSize=20
Authorization: Bearer {token}
```

**Response**:
```json
{
  "result": {
    "clients": [
      {
        "clientId": 1,
        "ragioneSociale": "Company Name SRL",
        "iva": "12345678901",
        "cf": "12345678901",
        "email": "info@company.com",
        "phone": "+39 123 456 7890",
        "hasRecurringInvoice": true,
        "createdAt": "2024-01-01T00:00:00.000000Z"
      }
    ]
  },
  "pagination": {
    "total": 150,
    "count": 20,
    "per_page": 20,
    "current_page": 1,
    "total_pages": 8
  }
}
```

**Create Client**
```http
POST /api/v1/clients/create
Authorization: Bearer {token}
Content-Type: application/json

{
  "ragioneSociale": "New Company SRL",
  "iva": "12345678901",
  "cf": "12345678901",
  "email": "info@newcompany.com",
  "phone": "+39 123 456 7890",
  "note": "Important client",
  "monthlyPrice": 500.00,
  "paymentTypeId": 1,
  "payStepsId": 2,
  "allowedDaysToPay": 30,
  "isCompany": true,
  "totalTax": "2,5",
  "totalTaxDescription": "Additional tax",
  "sdi": "ABC1234",
  "proforma": false,
  "addresses": [
    {
      "address": "Via Roma 123",
      "city": "Milano",
      "province": "MI",
      "cap": "20100"
    }
  ],
  "contacts": [
    {
      "name": "John Doe",
      "email": "john@company.com",
      "phone": "+39 123 456 7890",
      "cf": "RSSMRA80A01H501U"
    }
  ],
  "bankAccounts": [
    {
      "bankId": 5,
      "iban": "IT60X0542811101000000123456",
      "abi": "05428",
      "cab": "11101",
      "isMain": true
    }
  ],
  "discounts": [
    {
      "serviceCategoryId": 1,
      "discountType": 0,
      "discountAmount": 10.00,
      "price": 450.00,
      "status": 1,
      "show": 1
    }
  ],
  "payInstallments": []
}
```

**Response**:
```json
{
  "message": "Created successfully"
}
```


**Get Client for Editing**
```http
GET /api/v1/clients/edit?clientId=1
Authorization: Bearer {token}
```

**Response**:
```json
{
  "data": {
    "clientId": 1,
    "ragioneSociale": "Company Name SRL",
    "iva": "12345678901",
    "cf": "12345678901",
    "email": "info@company.com",
    "phone": "+39 123 456 7890",
    "note": "Important client",
    "monthlyPrice": 500.00,
    "paymentTypeId": 1,
    "payStepsId": 2,
    "allowedDaysToPay": 30,
    "isCompany": true,
    "hasRecurringInvoice": true,
    "totalTax": 2.5,
    "totalTaxDescription": "Additional tax",
    "sdi": "ABC1234",
    "proforma": false,
    "addresses": [
      {
        "clientAddressId": 1,
        "address": "Via Roma 123",
        "city": "Milano",
        "province": "MI",
        "cap": "20100"
      }
    ],
    "contacts": [
      {
        "clientContactId": 1,
        "name": "John Doe",
        "email": "john@company.com",
        "phone": "+39 123 456 7890",
        "cf": "RSSMRA80A01H501U"
      }
    ],
    "bankAccounts": [
      {
        "clientBankAccountId": 1,
        "bankId": 5,
        "bankName": "Intesa Sanpaolo",
        "iban": "IT60X0542811101000000123456",
        "abi": "05428",
        "cab": "11101",
        "isMain": true
      }
    ],
    "payInstallments": [
      {
        "payInstallmentId": 1,
        "amount": 1000.00,
        "startAt": "2024-01-01",
        "endAt": "2024-12-31",
        "parameterValue": {
          "parameterValueId": 10,
          "parameterValue": "Annual Service"
        },
        "payInstallmentSubData": [
          {
            "payInstallmentSubDataId": 1,
            "price": 500.00,
            "startAt": "2024-01-01",
            "endAt": "2024-06-30"
          },
          {
            "payInstallmentSubDataId": 2,
            "price": 500.00,
            "startAt": "2024-07-01",
            "endAt": "2024-12-31"
          }
        ]
      }
    ]
  }
}
```

**Update Client**
```http
PUT /api/v1/clients/update
Authorization: Bearer {token}
Content-Type: application/json

{
  "clientId": 1,
  "ragioneSociale": "Updated Company SRL",
  "email": "newemail@company.com",
  "monthlyPrice": 600.00,
  "payInstallments": []
}
```

**Delete Client**
```http
DELETE /api/v1/clients/delete?clientId=1
Authorization: Bearer {token}
```

### Service Discounts

**List Client Discounts**
```http
GET /api/v1/client-service-discounts?clientId=1
Authorization: Bearer {token}
```

**Create Discount**
```http
POST /api/v1/client-service-discounts/create
Authorization: Bearer {token}
Content-Type: application/json

{
  "clientId": 1,
  "serviceCategoryId": 5,
  "discountType": 0,
  "discountAmount": 15.00,
  "price": 425.00,
  "status": 1,
  "show": 1,
  "category": 1
}
```

**Toggle Discount Visibility**
```http
POST /api/v1/client-service-discounts/changeShow
Authorization: Bearer {token}
Content-Type: application/json

{
  "ClientDiscountId": 1,
  "isShow": true
}
```

### Payment Installments

**Calculate Installment Schedule**
```http
GET /api/v1/client-pay-installment-divider?clientId=1&price=1200&payStepsId=3&startDate=2024-01-01
Authorization: Bearer {token}
```

**Response**:
```json
{
  "data": {
    "payInstallments": [
      {
        "startAt": "2024-01-01",
        "endAt": "2024-04-30",
        "parameterValueName": "",
        "amount": 400.00,
        "paymentTypeId": 1,
        "payInstallmentSubData": []
      },
      {
        "startAt": "2024-05-01",
        "endAt": "2024-08-31",
        "parameterValueName": "",
        "amount": 400.00,
        "paymentTypeId": 1,
        "payInstallmentSubData": []
      },
      {
        "startAt": "2024-09-01",
        "endAt": "2024-12-31",
        "parameterValueName": "",
        "amount": 400.00,
        "paymentTypeId": 1,
        "payInstallmentSubData": []
      }
    ]
  }
}
```

## Business Logic

### Special Client Fields

#### addable_to_bulk_invoice

**Purpose**: Controls whether a client can be included in bulk invoice generation operations.

**Values**:
- `0` (NOTADDABLE): Client is excluded from bulk invoicing
- `1` (ADDABLE): Client can be included in bulk invoicing (default)

**Enum**: `App\Enums\Client\AddableToBulk`

**Use Cases**:
1. **Bulk Invoice Generation**: When generating invoices for multiple clients at once, only clients with `addable_to_bulk_invoice = 1` are included
2. **Special Billing Arrangements**: Clients with custom billing cycles or special arrangements can be excluded from bulk operations
3. **Manual Processing**: Clients requiring manual review before invoicing can be marked as not addable

**Example**:
```php
// Check if client can be added to bulk invoice
if ($client->addable_to_bulk_invoice === AddableToBulk::ADDABLE) {
    // Include in bulk invoice generation
}
```

**Database**:
- Column: `addable_to_bulk_invoice`
- Type: BOOLEAN (0 or 1)
- Default: 1 (ADDABLE)
- Migration: `2025_01_28_113620_add_allowed_days_to_pay_to_clients.php`

#### total_tax

**Purpose**: Applies an additional tax percentage to the invoice total before calculating VAT. This is used for clients who require an extra charge (e.g., withholding tax, additional fees, or special levies).

**Values**:
- Decimal value representing percentage (e.g., `4.00` for 4%)
- Default: `0` (no additional tax)
- Can be positive (additional charge) or theoretically negative (discount)

**Related Field**: `total_tax_description` - Description text for the tax line item

**Calculation Flow**:
```
1. Calculate base invoice total from all line items
2. If total_tax > 0:
   - Calculate tax amount: invoiceTaxableTotal × (total_tax / 100)
   - Add new line item with description from total_tax_description
   - Add tax amount to invoice total
   - Apply 22% VAT to the tax amount
3. Continue with final invoice calculations
```

**Example Calculation**:
```
Base Invoice Total: €1,000.00
Client total_tax: 4.00 (4%)
Client total_tax_description: "Contributo integrativo" (Supplementary contribution)

Calculation:
- Tax Amount: €1,000.00 × 4% = €40.00
- New Invoice Total: €1,000.00 + €40.00 = €1,040.00
- VAT on Tax: €40.00 × 22% = €8.80
- Total VAT: (€1,000.00 × 22%) + €8.80 = €228.80
- Final Total: €1,040.00 + €228.80 = €1,268.80
```

**Code Implementation**:
```php
// In InvoiceReportExportController.php
if ($client->total_tax > 0) {
    $clientTaxAmount = $invoiceTaxableTotal * ($client->total_tax / 100);
    
    $invoiceItemsData[] = [
        'description' => $client->total_tax_description ?? '',
        'price' => $clientTaxAmount,
        'priceAfterDiscount' => $clientTaxAmount,
        'additionalTaxPercentage' => 22,
        'serviceCode' => '00000001'
    ];
    
    $invoiceTotal += $clientTaxAmount;
    $invoiceTotalToCalcTax += $invoiceTotalToCalcTax * ($client->total_tax / 100);
    $invoiceTaxableTotal += $clientTaxAmount;
}
```

**Use Cases**:
1. **Professional Withholding Tax**: Italian professionals (e.g., accountants, lawyers) often apply a 4% "contributo integrativo" (supplementary contribution)
2. **Additional Fees**: Clients requiring special handling or additional services
3. **Regulatory Charges**: Industry-specific charges or levies
4. **Pension Fund Contributions**: Mandatory contributions for certain professions

**Database**:
- Column: `total_tax`
- Type: DECIMAL(10,2)
- Default: 0
- Related Column: `total_tax_description` (VARCHAR)
- Migration: `2025_03_03_140151_add_is_company_to_clients_table.php`

**Important Notes**:
- The tax is applied to the taxable total (before VAT)
- The tax amount itself is subject to 22% VAT
- The description appears as a separate line item in the invoice
- Common in Italian accounting for professional services
- Must be included in FatturaPA XML export

### Client Creation Flow

```
1. Validate Request Data
   - CreateClientRequest validates all fields
   - Checks required fields
   - Validates email format, VAT format
   │
   ▼
2. Start Database Transaction
   - Ensures all-or-nothing operation
   │
   ▼
3. Create Client Record
   - ClientService->createClient()
   - Converts camelCase to snake_case
   - Handles special fields (total_tax conversion)
   │
   ▼
4. Create Related Addresses
   - Loop through addresses array
   - ClientAddressService->createAddress()
   - Link to client via client_id
   │
   ▼
5. Create Related Contacts
   - Loop through contacts array
   - ClientContactService->createContact()
   - Link to client via client_id
   │
   ▼
6. Create Service Discounts
   - Loop through discounts array
   - ClientServiceDiscountService->createClientServiceDiscount()
   - Link to client and service category
   │
   ▼
7. Create Bank Accounts
   - Loop through bankAccounts array
   - ClientBankAccountService->createClientBankAccount()
   - Link to client via client_id
   - Mark one as main (is_main = true)
   │
   ▼
8. Create Payment Installments
   - Loop through payInstallments array
   - Create ClientPayInstallment record
   - For each installment, create sub-data records
   - Link sub-data to parent installment
   │
   ▼
9. Commit Transaction
   - All changes saved to database
   │
   ▼
10. Return Success Response
    - Message: "Created successfully"
```

### Client Update Flow

```
1. Validate Request Data
   - UpdateClientRequest validates all fields
   │
   ▼
2. Start Database Transaction
   │
   ▼
3. Find Existing Client
   - Load client by ID
   - Store previous pay_steps_id
   │
   ▼
4. Update Client Data
   - ClientService->updateClient()
   - Update all client fields
   │
   ▼
5. Handle Payment Installments
   - Check if has_recurring_invoice is enabled
   │
   ├─ If NOT enabled:
   │  ├─ Delete existing installments (force delete)
   │  ├─ Create new installments from request
   │  └─ Create sub-data for each installment
   │
   └─ If enabled:
      └─ Keep existing installments unchanged
   │
   ▼
6. Commit Transaction
   │
   ▼
7. Return Success Response
```


### Installment Calculation Logic

**Purpose**: Automatically calculate payment schedule based on total amount and number of installments

**Input**:
- Total price (e.g., €1,200)
- Number of installments (e.g., 4)
- Start date (e.g., 2024-01-01)
- Client payment terms (allowed_days_to_pay)

**Process**:

1. **Get Installment Count**
   ```php
   $installmentNumbers = ParameterValue::where('id', $payStepsId)
       ->pluck('description')
       ->first();
   // e.g., "4" for quarterly payments
   ```

2. **Calculate Amount Per Installment**
   ```php
   $amountPerInstallment = $totalPrice / $installmentNumbers;
   // e.g., €1,200 / 4 = €300 per installment
   ```

3. **Calculate Payment Dates**
   - Start with provided start date
   - For each installment:
     - Calculate period (monthly, quarterly, etc.)
     - Add period to previous date
     - Adjust for weekends (move to Monday)
     - Handle month-end dates properly
     - Special handling for August 31 and December 31

4. **Weekend Adjustment**
   ```php
   if ($date->isSaturday()) {
       $date->addDays(2); // Move to Monday
   } elseif ($date->isSunday()) {
       $date->addDay(); // Move to Monday
   }
   ```

5. **Month-End Handling**
   ```php
   // If original date is month-end, keep it month-end
   if ($startDate->day == $startDate->daysInMonth) {
       $date->endOfMonth();
   }
   ```

6. **Return Schedule**
   - Array of installments with:
     - Number (1, 2, 3, ...)
     - Amount
     - Start date
     - End date
     - Due date

### Service Discount Logic

**Purpose**: Apply client-specific pricing or discounts to services

**Discount Types**:

1. **Percentage Discount** (type = 0)
   ```php
   $finalPrice = $basePrice * (1 - $discountAmount / 100);
   // Example: €500 with 10% discount = €450
   ```

2. **Fixed Amount Discount** (type = 1)
   ```php
   $finalPrice = $basePrice - $discountAmount;
   // Example: €500 with €50 discount = €450
   ```

3. **Custom Price** (price field set)
   ```php
   $finalPrice = $customPrice;
   // Example: Custom price of €425 regardless of base price
   ```

**Application Priority**:
1. Check if client has custom price for service
2. If yes, use custom price
3. If no, check for discount
4. If discount exists, apply to base price
5. If no discount, use base service price

**Visibility Control**:
- `show` field controls visibility in client portal
- `status` field controls if discount is active
- Both must be true for discount to apply

## Data Validation

### CreateClientRequest

**File**: `app/Http/Requests/Client/CreateClientRequest.php`

**Validation Rules**:
```php
[
    'ragioneSociale' => 'required|string|max:255',
    'iva' => 'nullable|string|max:60',
    'cf' => 'nullable|string|max:60',
    'email' => 'nullable|email',
    'phone' => 'nullable|string',
    'monthlyPrice' => 'nullable|numeric',
    'paymentTypeId' => 'nullable|exists:parameter_values,id',
    'payStepsId' => 'nullable|exists:parameter_values,id',
    'allowedDaysToPay' => 'nullable|integer',
    'isCompany' => 'nullable|boolean',
    'totalTax' => 'nullable|string',
    'sdi' => 'nullable|string|max:7',
    'addresses' => 'nullable|array',
    'addresses.*.address' => 'required|string',
    'addresses.*.city' => 'required|string',
    'addresses.*.cap' => 'required|string',
    'contacts' => 'nullable|array',
    'contacts.*.name' => 'required|string',
    'contacts.*.email' => 'nullable|email',
    'bankAccounts' => 'nullable|array',
    'bankAccounts.*.iban' => 'required|string',
    'bankAccounts.*.bankId' => 'required|exists:parameter_values,id',
    'discounts' => 'nullable|array',
    'discounts.*.serviceCategoryId' => 'required|exists:service_categories,id',
    'discounts.*.discountType' => 'required|integer|in:0,1',
    'discounts.*.discountAmount' => 'required|numeric',
]
```

### UpdateClientRequest

**File**: `app/Http/Requests/Client/UpdateClientRequest.php`

**Additional Rules**:
```php
[
    'clientId' => 'required|exists:clients,id',
    // ... same as CreateClientRequest
]
```

## Models and Relationships

### Client Model

**File**: `app/Models/Client/Client.php`

**Relationships**:
```php
// One-to-Many
public function addresses() {
    return $this->hasMany(ClientAddress::class);
}

public function contacts() {
    return $this->hasMany(ClientContact::class);
}

public function bankAccounts() {
    return $this->hasMany(ClientBankAccount::class);
}

public function serviceDiscounts() {
    return $this->hasMany(ClientServiceDiscount::class);
}

public function payInstallments() {
    return $this->hasMany(ClientPayInstallment::class);
}

public function tasks() {
    return $this->hasMany(Task::class);
}

public function invoices() {
    return $this->hasMany(Invoice::class);
}

// Belongs To
public function paymentType() {
    return $this->belongsTo(ParameterValue::class, 'payment_type_id');
}

public function paySteps() {
    return $this->belongsTo(ParameterValue::class, 'pay_steps_id');
}
```

**Key Methods**:

```php
// Get client-specific discount for a service
public function getClientDiscount($serviceCategoryId) {
    $discount = $this->serviceDiscounts()
        ->where('service_category_id', $serviceCategoryId)
        ->where('status', 1)
        ->first();
    
    if ($discount) {
        if ($discount->price) {
            return $discount->price;
        }
        
        $basePrice = ServiceCategory::find($serviceCategoryId)->price;
        
        if ($discount->discount_type == 0) {
            // Percentage
            return $basePrice * (1 - $discount->discount_amount / 100);
        } else {
            // Fixed
            return $basePrice - $discount->discount_amount;
        }
    }
    
    return ServiceCategory::find($serviceCategoryId)->price;
}
```


## Usage Examples

### Complete Client Onboarding

```javascript
// Create a new client with all related data
const createClient = async () => {
  const clientData = {
    ragioneSociale: "Acme Corporation SRL",
    iva: "12345678901",
    cf: "12345678901",
    email: "info@acme.com",
    phone: "+39 02 1234567",
    note: "VIP client - priority support",
    monthlyPrice: 1500.00,
    paymentTypeId: 5, // Bank transfer
    payStepsId: 12, // Monthly payments
    allowedDaysToPay: 30,
    isCompany: true,
    totalTax: "2,5", // 2.5% additional tax
    totalTaxDescription: "Contributo integrativo",
    sdi: "ABC1234",
    proforma: false,
    addableToBulkInvoice: 1,
    
    // Addresses
    addresses: [
      {
        address: "Via Milano 123",
        city: "Milano",
        province: "MI",
        cap: "20100"
      },
      {
        address: "Via Roma 456",
        city: "Roma",
        province: "RM",
        cap: "00100"
      }
    ],
    
    // Contacts
    contacts: [
      {
        name: "Mario Rossi",
        email: "mario.rossi@acme.com",
        phone: "+39 333 1234567",
        cf: "RSSMRA80A01H501U"
      },
      {
        name: "Laura Bianchi",
        email: "laura.bianchi@acme.com",
        phone: "+39 333 7654321",
        cf: "BNCLRA85M45F205X"
      }
    ],
    
    // Bank Accounts
    bankAccounts: [
      {
        bankId: 5, // Intesa Sanpaolo
        iban: "IT60X0542811101000000123456",
        abi: "05428",
        cab: "11101",
        isMain: true
      }
    ],
    
    // Service Discounts
    discounts: [
      {
        serviceCategoryId: 1, // Accounting services
        discountType: 0, // Percentage
        discountAmount: 10.00,
        price: null,
        status: 1,
        show: 1,
        category: 1
      },
      {
        serviceCategoryId: 2, // Tax services
        discountType: 1, // Fixed amount
        discountAmount: 50.00,
        price: null,
        status: 1,
        show: 1,
        category: 1
      }
    ],
    
    // Payment Installments (if not recurring)
    payInstallments: []
  };
  
  try {
    const response = await fetch('http://api.example.com/api/v1/clients/create', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(clientData)
    });
    
    const result = await response.json();
    console.log('Client created:', result.message);
  } catch (error) {
    console.error('Error creating client:', error);
  }
};
```

### Calculate Installment Schedule

```javascript
// Calculate payment schedule for a client
const calculateInstallments = async (clientId, totalPrice, payStepsId, startDate) => {
  try {
    const response = await fetch(
      `http://api.example.com/api/v1/client-pay-installment-divider?` +
      `clientId=${clientId}&price=${totalPrice}&payStepsId=${payStepsId}&startDate=${startDate}`,
      {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      }
    );
    
    const result = await response.json();
    
    console.log('Installment Schedule:');
    result.data.payInstallments.forEach((inst, index) => {
      console.log(`Installment ${index + 1}:`);
      console.log(`  Amount: €${inst.amount}`);
      console.log(`  Period: ${inst.startAt} to ${inst.endAt}`);
      console.log(`  Payment Type ID: ${inst.paymentTypeId}`);
    });
    
    return result.data.payInstallments;
  } catch (error) {
    console.error('Error calculating installments:', error);
  }
};

// Usage
calculateInstallments(1, 1200, 4, '2024-01-01');
```

### Manage Service Discounts

```javascript
// Add a discount for a specific service
const addServiceDiscount = async (clientId, serviceCategoryId) => {
  const discountData = {
    clientId: clientId,
    serviceCategoryId: serviceCategoryId,
    discountType: 0, // Percentage
    discountAmount: 15.00, // 15% discount
    price: null, // Use discount instead of custom price
    status: 1, // Active
    show: 1, // Visible
    category: 1
  };
  
  try {
    const response = await fetch('http://api.example.com/api/v1/client-service-discounts/create', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(discountData)
    });
    
    const result = await response.json();
    console.log('Discount created:', result.message);
  } catch (error) {
    console.error('Error creating discount:', error);
  }
};

// Toggle discount visibility
const toggleDiscountVisibility = async (discountId, isVisible) => {
  try {
    const response = await fetch('http://api.example.com/api/v1/client-service-discounts/changeShow', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        ClientDiscountId: discountId,
        isShow: isVisible
      })
    });
    
    const result = await response.json();
    console.log('Visibility updated:', result.message);
  } catch (error) {
    console.error('Error updating visibility:', error);
  }
};
```

## Import/Export

### Import Clients from CSV

**Endpoint**: `POST /api/v1/import-clients`

**File Format**: Excel or CSV

**Required Columns**:
- ragione_sociale (Company name)
- iva (VAT number)
- cf (Tax code)
- email
- phone
- monthly_price
- payment_type_id
- allowed_days_to_pay

**Example**:
```csv
ragione_sociale,iva,cf,email,phone,monthly_price,payment_type_id
"Acme SRL","12345678901","12345678901","info@acme.com","+39 02 1234567",1500.00,5
"Beta SPA","98765432109","98765432109","info@beta.com","+39 06 7654321",2000.00,5
```

### Import Bank Accounts

**Endpoint**: `POST /api/v1/import-client-bank-accounts`

**File Format**: Excel or CSV

**Required Columns**:
- client_id
- bank_id
- iban
- abi
- cab
- is_main

### Export Client Payments

**Endpoint**: `GET /api/v1/export-client-payment`

**Query Parameters**:
- `client_id` - Client ID
- `start_date` - Period start (YYYY-MM-DD)
- `end_date` - Period end (YYYY-MM-DD)

**Response**: Excel file with payment history

## Troubleshooting

### Common Issues

#### Issue: "Client not found"
**Cause**: Invalid client ID or client deleted
**Solution**:
- Verify client ID exists
- Check if client is soft-deleted
- Use correct client ID from database

#### Issue: "Validation failed for addresses"
**Cause**: Missing required address fields
**Solution**:
- Ensure all addresses have: address, city, cap
- Check array structure in request
- Verify field names match (camelCase)

#### Issue: "Bank account not found for invoice"
**Cause**: Client has no bank accounts
**Solution**:
- Add at least one bank account to client
- Mark one account as main (is_main = true)
- Verify bank_id references valid parameter_value

#### Issue: "Installment calculation fails"
**Cause**: Invalid payment steps or start date
**Solution**:
- Verify payStepsId exists in parameter_values
- Check start date format (YYYY-MM-DD)
- Ensure client has allowed_days_to_pay set

#### Issue: "Discount not applying"
**Cause**: Discount inactive or not visible
**Solution**:
- Check discount status = 1 (active)
- Verify show = 1 (visible)
- Ensure service_category_id matches
- Check discount dates if applicable

## Testing

### Manual Testing Checklist

**Client CRUD**:
- [ ] Create client with all related data
- [ ] Create client with minimal data
- [ ] List clients with filtering
- [ ] Search clients by name/VAT
- [ ] Edit client details
- [ ] Update client and installments
- [ ] Delete client (soft delete)
- [ ] Verify soft-deleted client hidden

**Addresses**:
- [ ] Add multiple addresses
- [ ] Update address
- [ ] Delete address
- [ ] Verify address in invoice

**Bank Accounts**:
- [ ] Add bank account with main flag
- [ ] Add multiple bank accounts
- [ ] Update bank account
- [ ] Delete bank account
- [ ] Verify main account selection

**Contacts**:
- [ ] Add contact person
- [ ] Update contact
- [ ] Delete contact
- [ ] Verify contact in communications

**Service Discounts**:
- [ ] Create percentage discount
- [ ] Create fixed discount
- [ ] Set custom price
- [ ] Toggle visibility
- [ ] Verify discount in invoice

**Payment Installments**:
- [ ] Calculate installment schedule
- [ ] Create installment plan
- [ ] Update installments
- [ ] Delete installments
- [ ] Verify dates calculation

## Related Documentation

- [Invoice Module](MODULE_INVOICE.md) - Invoice generation using client data
- [Task Module](MODULE_TASK_MANAGEMENT.md) - Tasks linked to clients
- [Reporting Module](MODULE_REPORTING.md) - Client reports and exports
- [Parameter Module](MODULE_PARAMETERS.md) - Payment types and bank configuration

---

**Last Updated**: 2024
**Module Version**: 1.0
