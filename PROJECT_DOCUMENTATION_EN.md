# Accountant Management System - Complete Technical Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [Project Structure Analysis](#project-structure-analysis)
3. [Application Architecture](#application-architecture)
4. [Database Structure](#database-structure)
5. [Module Documentation](#module-documentation)
6. [API Endpoints](#api-endpoints)
7. [Configuration & Execution](#configuration--execution)
8. [Business Logic Breakdown](#business-logic-breakdown)

---

## 1. Project Overview

### 1.1 Main Purpose
This is a comprehensive **Accountant Management System** built with Laravel 10 and PHP 8.1+. The application is designed to manage accounting operations for an Italian accounting firm (ELABORAZIONI SRL), including:

- Client management with full contact and banking information
- Service category management with pricing and discounts
- Task tracking and time logging for billable work
- Invoice generation (including Italian electronic invoicing - FatturaPA XML format)
- Payment installment management
- Recurring invoice automation
- Financial reporting and exports (PDF, CSV, Excel, XML)
- User management with role-based permissions

### 1.2 Problem It Solves
The system addresses the following business needs:
- **Client Relationship Management**: Centralized storage of client information, contacts, addresses, and banking details
- **Time Tracking**: Accurate tracking of billable hours for tasks and projects
- **Automated Invoicing**: Generation of compliant Italian electronic invoices (FatturaPA format)
- **Payment Management**: Tracking of payment installments and payment status
- **Financial Reporting**: Export capabilities for accounting software integration
- **Workflow Automation**: Recurring invoice generation for subscription-based services

### 1.3 Application Type
**Type**: RESTful API Backend (Web Service)
- **Architecture**: API-only Laravel application
- **Authentication**: JWT (JSON Web Token) based authentication
- **Frontend**: Separate (not included in this codebase)
- **Database**: MySQL/MariaDB
- **Deployment**: Traditional web server (Apache/Nginx + PHP-FPM)


---

## 2. Project Structure Analysis

### 2.1 Root Directory Structure

```
accountant-system/
├── app/                    # Application core code
├── bootstrap/              # Framework bootstrap files
├── config/                 # Configuration files
├── database/              # Migrations, seeders, factories
├── lang/                  # Localization files (Italian)
├── public/                # Public web root
├── resources/             # Views, assets
├── routes/                # Route definitions
├── storage/               # File storage, logs, cache
├── tests/                 # Automated tests
├── vendor/                # Composer dependencies
├── .env                   # Environment configuration
├── artisan               # CLI tool
├── composer.json         # PHP dependencies
└── package.json          # Node dependencies
```

### 2.2 App Directory Structure (Detailed)

#### 2.2.1 Console (`app/Console/`)
**Purpose**: Command-line interface commands and task scheduling

**Files**:
- `Kernel.php`: Defines scheduled tasks and custom Artisan commands

#### 2.2.2 Enums (`app/Enums/`)
**Purpose**: Type-safe enumeration classes for status values and constants

**Structure**:
```
app/Enums/
├── Client/
│   ├── AddableToBulk.php                    # Enum for bulk operation eligibility
│   ├── ClientServiceDiscountStatus.php      # Discount status (active/inactive)
│   ├── ClientServiceDiscountType.php        # Discount type (percentage/fixed)
│   ├── ClientShowStatus.php                 # Client visibility status
│   └── ServiceDiscountCategory.php          # Discount category classification
├── ServiceCategory/
│   └── ServiceCategoryAddToInvoiceStatus.php # Auto-add to invoice flag
├── Task/
│   ├── TaskStatus.php                       # Task status (pending/in-progress/completed)
│   ├── TaskTimeLogStatus.php                # Time log status
│   └── TaskTimeLogType.php                  # Time log type (manual/automatic)
└── User/
    └── UserStatus.php                       # User account status
```


#### 2.2.3 Exceptions (`app/Exceptions/`)
**Purpose**: Custom exception handling

**Files**:
- `Handler.php`: Global exception handler for the application

#### 2.2.4 Exports (`app/Exports/`)
**Purpose**: Excel export functionality using Maatwebsite/Excel

**Files**:
- `TasksExport.php`: Exports task data to Excel format

#### 2.2.5 Filters (`app/Filters/`)
**Purpose**: Query filtering classes for API endpoints (using Spatie Query Builder)

**Structure**:
```
app/Filters/
├── Category/FilterCategory.php              # Filter categories
├── Client/FilterClient.php                  # Filter clients by various criteria
├── Customer/FilterCustomer.php              # Filter customers
├── Project/
│   ├── FilterEmployee.php                   # Filter by employee
│   └── FilterProject.php                    # Filter projects
├── ServiceCategory/FilterServiceCategory.php # Filter service categories
├── Task/
│   ├── FilterTask.php                       # General task filtering
│   ├── FilterTaskDateBetween.php            # Filter tasks by date range
│   └── FilterTaskStartEndDate.php           # Filter by start/end dates
└── User/
    ├── FilterUser.php                       # Filter users
    └── FilterUserRole.php                   # Filter by user role
```

#### 2.2.6 Helpers (`app/Helpers/`)
**Purpose**: Global helper functions

**Files**:
- `CustomHelper.php`: Custom utility functions used throughout the application
  - Auto-loaded via composer.json
  - Contains reusable helper methods


#### 2.2.7 HTTP Layer (`app/Http/`)

##### Controllers (`app/Http/Controllers/`)
**Purpose**: Handle HTTP requests and return responses

**Structure**:
```
app/Http/Controllers/
├── Api/
│   ├── Public/
│   │   └── Auth/
│   │       └── AuthController.php           # Login/logout endpoints
│   └── Private/
│       ├── Client/                          # Client management endpoints
│       │   ├── ClientController.php         # CRUD for clients
│       │   ├── ClientAddressController.php  # Client addresses
│       │   ├── ClientBankAccountController.php # Bank accounts
│       │   ├── ClientContactController.php  # Client contacts
│       │   ├── ClientPayInstallmentController.php # Payment installments
│       │   ├── ClientPayInstallmentSubDataController.php # Installment details
│       │   ├── ClientPayInstallmentDividerController.php # Installment calculator
│       │   ├── ClientPayInstallmentEndDateController.php # End date calculator
│       │   ├── ClientPaymentTypeController.php # Payment type selector
│       │   ├── ClientPaymentPeriodController.php # Payment period selector
│       │   ├── ClientPaymentExportController.php # Export payment data
│       │   ├── ClientServiceCategoryDiscountController.php # Service discounts
│       │   └── ImportClientBankAccountController.php # Import bank accounts
│       ├── Invoice/                         # Invoice management
│       │   ├── InvoiceController.php        # CRUD for invoices
│       │   ├── InvoiceDetailController.php  # Invoice line items
│       │   ├── RecurringInvoiceController.php # Recurring invoices
│       │   ├── RecurringInvoiceToAllClientsController.php # Bulk recurring
│       │   ├── PayInvoiceController.php     # Mark invoice as paid
│       │   ├── AssignedInvoiceController.php # Assigned invoices
│       │   ├── SendEmailController.php      # Email invoice
│       │   ├── SendInvoiceController.php    # Send uploaded invoice
│       │   ├── ClientEmailController.php    # Client email info
│       │   └── ImageToExcelController.php   # OCR image to Excel
│       ├── Parameter/                       # System parameters
│       │   ├── ParameterController.php      # Parameter management
│       │   └── ParameterValueController.php # Parameter values CRUD
│       ├── Reports/                         # Reporting endpoints
│       │   ├── ReportController.php         # General reports
│       │   ├── InvoiceReportExportController.php # Export invoices (XML/PDF/CSV)
│       │   ├── InvoicePdfReportController.php # PDF generation
│       │   └── InvoiceCsvReportController.php # CSV generation
│       ├── Role/                            # Role management (not fully implemented)
│       ├── Select/                          # Dropdown/select data
│       │   └── SelectController.php         # Provides select options for forms
│       ├── ServiceCategory/                 # Service management
│       │   └── ServiceCategoryController.php # CRUD for services
│       ├── Task/                            # Task management
│       │   ├── TaskController.php           # CRUD for tasks
│       │   ├── AdminTaskController.php      # Admin task view
│       │   ├── ActiveTaskController.php     # Active tasks for user
│       │   ├── TaskTimeLogController.php    # Time logging
│       │   ├── ChangeTaskTimeLogController.php # Modify time logs
│       │   └── AdminTaskExportController.php # Export tasks
│       ├── Upload/                          # File uploads
│       └── User/                            # User management
│           └── UserController.php           # CRUD for users
├── Controller.php                           # Base controller
├── ImportClientController.php               # Import clients from CSV
└── ImportServiceCategoryController.php      # Import services from CSV
```


##### Middleware (`app/Http/Middleware/`)
**Purpose**: HTTP request filtering and processing

**Files**:
- `Authenticate.php`: JWT authentication verification
- `checkPermission.php`: Role-based permission checking
- `EncryptCookies.php`: Cookie encryption
- `PreventRequestsDuringMaintenance.php`: Maintenance mode handler
- `RedirectIfAuthenticated.php`: Redirect authenticated users
- `TrimStrings.php`: Trim whitespace from request data
- `TrustHosts.php`: Trusted host configuration
- `TrustProxies.php`: Proxy trust configuration
- `ValidateSignature.php`: Signed URL validation
- `VerifyCsrfToken.php`: CSRF protection

##### Requests (`app/Http/Requests/`)
**Purpose**: Form validation and authorization

**Structure**:
```
app/Http/Requests/
├── Auth/
│   ├── LoginRequest.php                     # Login validation
│   └── RegisterRequest.php                  # Registration validation
├── Client/
│   ├── CreateClientRequest.php              # Client creation validation
│   ├── UpdateClientRequest.php              # Client update validation
│   ├── BankAccount/
│   │   ├── CreateClientBankAccountRequest.php
│   │   └── UpdateClientBankAccountRequest.php
│   ├── ClientAddress/
│   │   ├── CreateClientAddressRequest.php
│   │   └── UpdateClientAddressRequest.php
│   ├── ClientContact/
│   │   ├── CreateClientContactRequest.php
│   │   └── UpdateClientContactRequest.php
│   └── ClinetServiceDiscount/
│       ├── CreateClientServiceDiscountRequest.php
│       └── UpdateClientServiceDiscountRequest.php
├── Paramter/
│   ├── CreateParameterValueRequest.php
│   └── UpdateParameterValueRequest.php
├── Role/
│   ├── CreateRoleRequest.php
│   └── UpdateRoleRequest.php
├── ServiceCategory/
│   ├── CreateServiceCategoryRequest.php
│   └── UpdateServiceCategoryRequest.php
├── Task/
│   ├── CreateTaskRequest.php
│   ├── UpdateTaskRequest.php
│   └── TaskTimeLog/
│       ├── CreateTaskTimeLogRequest.php
│       └── UpdateTaskTimeLogRequest.php
└── User/
    ├── CreateUserRequest.php
    └── UpdateUserRequest.php
```


##### Resources (`app/Http/Resources/`)
**Purpose**: API response transformation (JSON serialization)

**Structure**:
```
app/Http/Resources/
├── AdminTask/
│   ├── AllAdminTaskCollection.php           # Collection of admin tasks
│   └── AllAdminTaskResource.php             # Single admin task
├── Client/
│   ├── ClientResource.php                   # Client details
│   ├── AllClientResource.php                # Client list item
│   ├── ClientBankAccount/
│   │   └── ClientBankAccountResource.php    # Bank account details
│   └── PayInstallment/
│       ├── AllPayInstallmentResource.php    # Installment list
│       ├── AllPayInstallmentSubDataResource.php # Sub-installment list
│       └── PayInstallmentSubDataResource.php # Sub-installment details
├── Invoice/
│   ├── AllInvoiceResource.php               # Invoice list item
│   └── InvoiceResource.php                  # Invoice details
├── Parameter/
│   └── ParameterValueResource.php           # Parameter value
├── Role/
│   └── RoleResource.php                     # Role details
├── Select/
│   └── SelectResource.php                   # Select option format
├── ServiceCategory/
│   ├── ServiceCategoryResource.php          # Service details
│   └── AllServiceCategoryResource.php       # Service list item
├── Task/
│   ├── AllTaskResource.php                  # Task list item
│   ├── TaskResource.php                     # Task details
│   └── TaskTimeLogResource.php              # Time log details
└── User/
    ├── AllUserResource.php                  # User list item
    └── UserResource.php                     # User details
```

#### 2.2.8 Imports (`app/Imports/`)
**Purpose**: Excel/CSV import functionality

**Files**:
- `ClientImport.php`: Import clients from Excel/CSV
- `ClientBankAccountImport.php`: Import bank accounts
- `ServiceCategoryImport.php`: Import service categories


#### 2.2.9 Mail (`app/Mail/`)
**Purpose**: Email templates and mailing functionality

**Files**:
- `InvoiceEmail.php`: Email template for sending invoices to clients

#### 2.2.10 Models (`app/Models/`)
**Purpose**: Eloquent ORM models representing database tables

**Structure**:
```
app/Models/
├── User.php                                 # User model (authentication)
├── Client/
│   ├── Client.php                           # Client entity
│   ├── ClientAddress.php                    # Client address
│   ├── ClientBankAccount.php                # Client bank account
│   ├── ClientContact.php                    # Client contact person
│   ├── ClientPayInstallment.php             # Payment installment
│   ├── ClientPayInstallmentSubData.php      # Installment sub-data
│   └── ClientServiceDiscount.php            # Service-specific discounts
├── Invoice/
│   ├── Invoice.php                          # Invoice header
│   └── InvoiceDetail.php                    # Invoice line items
├── Parameter/
│   ├── Parameter.php                        # Parameter categories
│   └── ParameterValue.php                   # Parameter values (lookup tables)
├── ServiceCategory/
│   └── ServiceCategory.php                  # Service/product catalog
└── Task/
    ├── Task.php                             # Task/project
    └── TaskTimeLog.php                      # Time tracking entries
```

**Key Model Relationships**:
- `Client` has many: `ClientAddress`, `ClientBankAccount`, `ClientContact`, `ClientServiceDiscount`, `ClientPayInstallment`, `Invoice`, `Task`
- `Invoice` belongs to: `Client`, has many: `InvoiceDetail`
- `Task` belongs to: `Client`, `ServiceCategory`, `User` (assigned to), has many: `TaskTimeLog`
- `ServiceCategory` has many: `Task`, `ClientServiceDiscount`
- `ParameterValue` belongs to: `Parameter`


#### 2.2.11 Providers (`app/Providers/`)
**Purpose**: Service container bindings and bootstrapping

**Files**:
- `AppServiceProvider.php`: Application service provider
- `AuthServiceProvider.php`: Authentication and authorization policies
- `BroadcastServiceProvider.php`: Broadcasting configuration
- `EventServiceProvider.php`: Event listeners registration
- `RouteServiceProvider.php`: Route configuration and bindings

#### 2.2.12 Services (`app/Services/`)
**Purpose**: Business logic layer (Service Layer Pattern)

**Structure**:
```
app/Services/
├── Auth/
│   └── AuthService.php                      # Authentication logic (JWT)
├── Client/
│   ├── ClientService.php                    # Client business logic
│   ├── ClientAddressService.php             # Address management
│   ├── ClientBankAccountService.php         # Bank account management
│   ├── ClientContactService.php             # Contact management
│   └── ClientServiceDiscountService.php     # Discount management
├── Parameter/
│   └── ParameterService.php                 # Parameter management
├── Reports/
│   └── ReportService.php                    # Report generation logic
├── Role/
│   └── RoleService.php                      # Role management
├── Select/
│   ├── SelectService.php                    # Base select service
│   ├── ClientSelectService.php              # Client dropdown data
│   ├── PermissionSelectService.php          # Permission dropdown
│   ├── RoleSelectService.php                # Role dropdown
│   ├── ServiceCategorySelectService.php     # Service dropdown
│   ├── UserSelectService.php                # User dropdown
│   ├── Invoice/
│   │   └── InvoiceSelectService.php         # Invoice-related selects
│   └── Parameter/
│       └── ParameterSelectService.php       # Parameter selects
├── ServiceCategory/
│   └── ServiceCategoryService.php           # Service catalog logic
├── Task/
│   ├── TaskService.php                      # Task management logic
│   ├── TaskTimeLogService.php               # Time logging logic
│   └── ExportTaskService.php                # Task export logic
├── Upload/
│   └── UploadService.php                    # File upload handling
├── User/
│   └── UserService.php                      # User management logic
└── UserRolePremission/
    └── UserPermissionService.php            # Permission management
```


#### 2.2.13 Traits (`app/Traits/`)
**Purpose**: Reusable code snippets for models

**Files**:
- `CreatedUpdatedBy.php`: Automatically tracks who created/updated records
- `CreatedUpdatedByMigration.php`: Migration helper for audit columns

#### 2.2.14 Utils (`app/Utils/`)
**Purpose**: Utility classes

**Files**:
- `PaginateCollection.php`: Custom pagination for collections

---

## 3. Application Architecture

### 3.1 Architectural Pattern

The application follows a **Layered Architecture** with clear separation of concerns:

```
┌─────────────────────────────────────────┐
│         HTTP Layer (Routes)             │
│  - API Routes Definition                │
│  - Middleware Application               │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│      Controller Layer                   │
│  - Request Handling                     │
│  - Response Formatting                  │
│  - Validation Delegation                │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│       Service Layer                     │
│  - Business Logic                       │
│  - Transaction Management               │
│  - Complex Operations                   │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│       Model Layer (Eloquent ORM)        │
│  - Database Interaction                 │
│  - Relationships                        │
│  - Query Scopes                         │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│         Database (MySQL)                │
│  - Data Persistence                     │
└─────────────────────────────────────────┘
```

**Additional Layers**:
- **Request Layer**: Form validation and authorization
- **Resource Layer**: API response transformation
- **Filter Layer**: Query filtering for list endpoints
- **Export/Import Layer**: Data import/export functionality


### 3.2 Data Flow

#### 3.2.1 Typical Request Flow (Example: Create Invoice)

```
1. HTTP Request
   POST /api/v1/invoices/create
   Headers: Authorization: Bearer {JWT_TOKEN}
   Body: { client_id, start_at, end_at, ... }
   
2. Route Matching
   routes/api.php → InvoiceController@create
   
3. Middleware Processing
   - Authenticate (JWT verification)
   - checkPermission (role-based access)
   - TrimStrings (clean input)
   
4. Controller Method
   InvoiceController@create()
   - Receives Request object
   - Calls Service layer
   
5. Service Layer
   InvoiceService->create()
   - Validates business rules
   - Starts database transaction
   - Creates invoice record
   - Creates invoice details
   - Commits transaction
   - Returns model instance
   
6. Resource Transformation
   InvoiceResource::make($invoice)
   - Transforms model to JSON structure
   - Includes relationships
   - Formats dates/numbers
   
7. HTTP Response
   JSON response with status code
   { "data": { "id": 1, "client": {...}, ... } }
```

### 3.3 Entry Point

**Main Entry Point**: `public/index.php`

```php
// Bootstrap Laravel application
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Handle HTTP request
$kernel = $app->make(Kernel::class);
$response = $kernel->handle(
    $request = Request::capture()
);
$response->send();
```

**API Entry Point**: `routes/api.php`
- All API routes are prefixed with `/api/v1/`
- Routes are grouped by resource type
- Authentication required for all routes except login


---

## 4. Database Structure

### 4.1 Database Overview

**Database Type**: MySQL/MariaDB (Relational Database)
**Character Set**: UTF-8
**Collation**: utf8mb4_unicode_ci

### 4.2 Core Tables

#### 4.2.1 Users Table (`users`)
**Purpose**: System users (employees, administrators)

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| name | VARCHAR(255) | User full name |
| email | VARCHAR(255) | Email address (unique) |
| email_verified_at | TIMESTAMP | Email verification timestamp |
| password | VARCHAR(255) | Hashed password |
| status | TINYINT | User status (active/inactive) |
| created_by | BIGINT UNSIGNED | User who created this record |
| updated_by | BIGINT UNSIGNED | User who last updated this record |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

**Relationships**:
- Has many: Tasks (assigned tasks)
- Has many: Clients (created by)
- Uses Spatie Permission package for roles and permissions


#### 4.2.2 Clients Table (`clients`)
**Purpose**: Customer/client information

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| payment_type_id | BIGINT UNSIGNED | FK to parameter_values (payment method) |
| pay_steps_id | BIGINT UNSIGNED | FK to parameter_values (number of installments) |
| payment_type_two_id | BIGINT UNSIGNED | FK to parameter_values (secondary payment type) |
| iban | VARCHAR(255) | Client's IBAN |
| abi | VARCHAR(255) | Italian bank code (ABI) |
| cab | VARCHAR(255) | Italian branch code (CAB) |
| iva | VARCHAR(60) | VAT number (Partita IVA) |
| ragione_sociale | VARCHAR(255) | Company name |
| cf | VARCHAR(60) | Tax code (Codice Fiscale) |
| note | TEXT | Additional notes |
| email | VARCHAR(255) | Client email |
| phone | VARCHAR(255) | Client phone |
| hours_per_month | VARCHAR(20) | Monthly hours allocation |
| price | DECIMAL(8,2) | Monthly price |
| allowed_days_to_pay | INT | Payment terms in days |
| monthly_price | DECIMAL(8,2) | Monthly subscription price |
| is_company | BOOLEAN | Is company or individual |
| has_recurring_invoice | BOOLEAN | Auto-generate recurring invoices |
| proforma | BOOLEAN | Generate proforma invoices |
| total_tax | DECIMAL(5,2) | Additional tax percentage |
| total_tax_description | TEXT | Tax description |
| sdi | VARCHAR(7) | SDI code for electronic invoicing |
| sdi_code | VARCHAR(7) | Alternative SDI code |
| created_by | BIGINT UNSIGNED | User who created |
| updated_by | BIGINT UNSIGNED | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

**Relationships**:
- Belongs to: User (created_by, updated_by)
- Belongs to: ParameterValue (payment_type_id, pay_steps_id, payment_type_two_id)
- Has many: ClientAddress, ClientBankAccount, ClientContact, ClientServiceDiscount
- Has many: ClientPayInstallment, Invoice, Task


#### 4.2.3 Client Addresses Table (`client_addresses`)
**Purpose**: Client physical addresses

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| client_id | BIGINT UNSIGNED | FK to clients |
| address | TEXT | Street address |
| city | VARCHAR(255) | City name |
| province | VARCHAR(255) | Province/state |
| cap | VARCHAR(10) | Postal code (CAP) |
| created_by | BIGINT UNSIGNED | User who created |
| updated_by | BIGINT UNSIGNED | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

#### 4.2.4 Client Bank Accounts Table (`client_bank_accounts`)
**Purpose**: Client banking information

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| client_id | BIGINT UNSIGNED | FK to clients |
| bank_id | BIGINT UNSIGNED | FK to parameter_values (bank name) |
| iban | VARCHAR(255) | IBAN number |
| abi | VARCHAR(255) | Italian bank code |
| cab | VARCHAR(255) | Italian branch code |
| is_main | BOOLEAN | Is primary bank account |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

#### 4.2.5 Client Contacts Table (`client_contacts`)
**Purpose**: Contact persons for clients

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| client_id | BIGINT UNSIGNED | FK to clients |
| name | VARCHAR(255) | Contact person name |
| email | VARCHAR(255) | Contact email |
| phone | VARCHAR(255) | Contact phone |
| cf | VARCHAR(60) | Contact tax code |
| created_by | BIGINT UNSIGNED | User who created |
| updated_by | BIGINT UNSIGNED | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |


#### 4.2.6 Service Categories Table (`service_categories`)
**Purpose**: Catalog of services/products offered

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| name | VARCHAR(255) | Service name |
| code | VARCHAR(50) | Service code for invoicing |
| description | TEXT | Service description |
| price | DECIMAL(8,2) | Base price |
| service_type_id | BIGINT UNSIGNED | FK to parameter_values (service type) |
| add_to_invoice | TINYINT | Auto-add to invoice flag |
| extra_is_pricable | BOOLEAN | Has additional pricing |
| extra_price | DECIMAL(8,2) | Additional price |
| extra_price_description | TEXT | Additional price description |
| created_by | BIGINT UNSIGNED | User who created |
| updated_by | BIGINT UNSIGNED | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

**Relationships**:
- Has many: Task, ClientServiceDiscount

#### 4.2.7 Client Service Discounts Table (`client_service_discounts`)
**Purpose**: Client-specific service pricing and discounts

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| client_id | BIGINT UNSIGNED | FK to clients |
| service_category_id | BIGINT UNSIGNED | FK to service_categories |
| discount_type | TINYINT | Discount type (percentage/fixed) |
| discount_amount | DECIMAL(8,2) | Discount value |
| price | DECIMAL(8,2) | Custom price for this client |
| status | TINYINT | Active/inactive status |
| show | TINYINT | Show in client view |
| category | TINYINT | Discount category |
| created_by | BIGINT UNSIGNED | User who created |
| updated_by | BIGINT UNSIGNED | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |


#### 4.2.8 Tasks Table (`tasks`)
**Purpose**: Billable work tasks/projects

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| number | VARCHAR(255) | Task number/reference |
| title | VARCHAR(255) | Task title |
| description | TEXT | Task description |
| status | TINYINT | Task status (pending/in-progress/completed) |
| client_id | BIGINT UNSIGNED | FK to clients |
| user_id | BIGINT UNSIGNED | FK to users (assigned to) |
| service_category_id | BIGINT UNSIGNED | FK to service_categories |
| invoice_id | BIGINT UNSIGNED | FK to invoices (if invoiced) |
| connection_type_id | BIGINT UNSIGNED | FK to parameter_values |
| start_date | DATE | Task start date |
| end_date | DATE | Task end date |
| price | DECIMAL(8,2) | Task price |
| price_after_discount | DECIMAL(8,2) | Price after discount |
| created_by | BIGINT UNSIGNED | User who created |
| updated_by | BIGINT UNSIGNED | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

**Relationships**:
- Belongs to: Client, User, ServiceCategory, Invoice
- Has many: TaskTimeLog

#### 4.2.9 Task Time Logs Table (`task_time_logs`)
**Purpose**: Time tracking for tasks

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| task_id | BIGINT UNSIGNED | FK to tasks |
| user_id | BIGINT UNSIGNED | FK to users |
| start_time | TIME | Start time |
| end_time | TIME | End time |
| total_time | TIME | Calculated total time |
| date | DATE | Date of work |
| status | TINYINT | Log status |
| type | TINYINT | Log type (manual/automatic) |
| created_by | BIGINT UNSIGNED | User who created |
| updated_by | BIGINT UNSIGNED | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

**Note**: Time fields support durations exceeding 24 hours (e.g., "39:18:23")


#### 4.2.10 Invoices Table (`invoices`)
**Purpose**: Invoice headers

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| number | VARCHAR(255) | Invoice number |
| client_id | BIGINT UNSIGNED | FK to clients |
| start_at | DATE | Invoice period start |
| end_at | DATE | Invoice period end |
| discount_type | TINYINT | Discount type (percentage/fixed) |
| discount_amount | DECIMAL(8,2) | Discount value |
| payment_type_id | BIGINT UNSIGNED | FK to parameter_values (payment method) |
| bank_account_id | BIGINT UNSIGNED | FK to parameter_values (bank account) |
| category | TINYINT | Invoice category |
| invoice_xml_number | VARCHAR(50) | XML invoice number (FatturaPA) |
| pay_status | BOOLEAN | Payment status (paid/unpaid) |
| pay_date | DATE | Payment date |
| created_by | BIGINT UNSIGNED | User who created |
| updated_by | BIGINT UNSIGNED | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

**Relationships**:
- Belongs to: Client
- Has many: InvoiceDetail, Task

#### 4.2.11 Invoice Details Table (`invoice_details`)
**Purpose**: Invoice line items (polymorphic)

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| invoice_id | BIGINT UNSIGNED | FK to invoices |
| invoiceable_id | BIGINT UNSIGNED | Polymorphic ID |
| invoiceable_type | VARCHAR(255) | Polymorphic type (Task, ClientPayInstallment, etc.) |
| price | DECIMAL(8,2) | Line item price |
| price_after_discount | DECIMAL(8,2) | Price after discount |
| description | TEXT | Line item description |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

**Polymorphic Relationships**:
- Can belong to: Task, ClientPayInstallment, ClientPayInstallmentSubData


#### 4.2.12 Client Pay Installments Table (`client_pay_installments`)
**Purpose**: Payment installment plans for clients

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| client_id | BIGINT UNSIGNED | FK to clients |
| parameter_value_id | BIGINT UNSIGNED | FK to parameter_values (service type) |
| price | DECIMAL(8,2) | Total installment price |
| start_at | DATE | Installment start date |
| end_at | DATE | Installment end date |
| payment_type_id | BIGINT UNSIGNED | FK to parameter_values (payment method) |
| invoice_id | BIGINT UNSIGNED | FK to invoices (if invoiced) |
| created_by | BIGINT UNSIGNED | User who created |
| updated_by | BIGINT UNSIGNED | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

#### 4.2.13 Client Pay Installment Sub Data Table (`client_pay_installment_sub_data`)
**Purpose**: Individual installment payments

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| client_pay_installment_id | BIGINT UNSIGNED | FK to client_pay_installments |
| parameter_value_id | BIGINT UNSIGNED | FK to parameter_values (service type) |
| price | DECIMAL(8,2) | Installment amount |
| start_at | DATE | Payment due date start |
| end_at | DATE | Payment due date end |
| payment_type_id | BIGINT UNSIGNED | FK to parameter_values (payment method) |
| invoice_id | BIGINT UNSIGNED | FK to invoices (if invoiced) |
| created_by | BIGINT UNSIGNED | User who created |
| updated_by | BIGINT UNSIGNED | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |


#### 4.2.14 Parameters Table (`parameters`)
**Purpose**: Parameter categories (lookup table categories)

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| name | VARCHAR(255) | Parameter category name |
| created_by | BIGINT UNSIGNED | User who created |
| updated_by | BIGINT UNSIGNED | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

#### 4.2.15 Parameter Values Table (`parameter_values`)
**Purpose**: Lookup table values (configurable dropdowns)

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| parameter_id | BIGINT UNSIGNED | FK to parameters |
| parameter_value | VARCHAR(255) | Display value |
| parameter_order | TINYINT | Sort order / category identifier |
| description | VARCHAR(255) | Additional description |
| description2 | VARCHAR(255) | Additional field (e.g., ABI code) |
| description3 | VARCHAR(255) | Additional field (e.g., CAB code) |
| code | VARCHAR(50) | Code for invoicing |
| is_default | BOOLEAN | Is default value |
| created_by | BIGINT UNSIGNED | User who created |
| updated_by | BIGINT UNSIGNED | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

**Common Parameter Orders**:
- 1: Payment types (MP01, MP05, MP12, etc.)
- 2: Payment steps (number of installments)
- 3: Service types
- 7: Bank accounts (company bank accounts)
- 13: Invoice XML numbering sequence


### 4.3 Database Relationships Diagram

```
┌─────────────┐
│    Users    │
└──────┬──────┘
       │ created_by/updated_by (many tables)
       │ assigned tasks
       ├──────────────────────────────────┐
       │                                  │
┌──────▼──────┐                    ┌─────▼─────┐
│   Clients   │◄───────────────────┤   Tasks   │
└──────┬──────┘                    └─────┬─────┘
       │                                  │
       │ has many                         │ has many
       ├──────────────────┐              │
       │                  │              │
┌──────▼──────────┐  ┌───▼────────┐ ┌───▼──────────────┐
│ ClientAddresses │  │  Invoices  │ │  TaskTimeLogs    │
└─────────────────┘  └─────┬──────┘ └──────────────────┘
                           │
┌──────────────────┐       │ has many
│ClientBankAccounts│       │
└──────────────────┘  ┌────▼──────────┐
                      │InvoiceDetails │
┌──────────────────┐  └───────────────┘
│ ClientContacts   │       │ polymorphic
└──────────────────┘       │
                           ├─────────────────┐
┌──────────────────┐       │                 │
│ClientServiceDisc.│  ┌────▼────────────┐ ┌──▼──────────────────┐
└──────────────────┘  │ClientPayInstall.│ │ClientPayInstallSub  │
                      └─────────────────┘ └─────────────────────┘
┌──────────────────┐
│ServiceCategories │
└──────────────────┘

┌──────────────────┐
│   Parameters     │
└────────┬─────────┘
         │ has many
    ┌────▼──────────┐
    │ParameterValues│ (Lookup tables)
    └───────────────┘
```

### 4.4 Key Database Features

1. **Soft Deletes**: All tables use soft deletes (`deleted_at` column)
2. **Audit Trail**: All tables track `created_by` and `updated_by`
3. **Timestamps**: All tables have `created_at` and `updated_at`
4. **Foreign Key Constraints**: Cascade deletes for data integrity
5. **Polymorphic Relationships**: Invoice details can link to multiple entity types
6. **Flexible Parameters**: Parameter values table serves as configurable lookup tables


---

## 5. Module Documentation

### 5.1 Authentication Module

**Location**: `app/Services/Auth/`, `app/Http/Controllers/Api/Public/Auth/`

**Purpose**: User authentication using JWT tokens

**Key Components**:
- `AuthController.php`: Handles login/logout requests
- `AuthService.php`: Authentication business logic
- `LoginRequest.php`: Login validation

**Authentication Flow**:
1. User sends POST request to `/api/v1/auth/login` with email and password
2. `LoginRequest` validates input
3. `AuthService` verifies credentials
4. JWT token generated and returned
5. Token must be included in `Authorization: Bearer {token}` header for all subsequent requests

**Key Methods**:
- `login()`: Authenticate user and return JWT token
- `logout()`: Invalidate JWT token

### 5.2 Client Management Module

**Location**: `app/Models/Client/`, `app/Services/Client/`, `app/Http/Controllers/Api/Private/Client/`

**Purpose**: Complete client relationship management

#### 5.2.1 Client CRUD
**Controller**: `ClientController.php`
**Service**: `ClientService.php`
**Model**: `Client.php`

**Endpoints**:
- `GET /api/v1/clients` - List all clients with filtering
- `POST /api/v1/clients/create` - Create new client
- `GET /api/v1/clients/edit?id={id}` - Get client details for editing
- `PUT /api/v1/clients/update` - Update client
- `DELETE /api/v1/clients/delete?id={id}` - Soft delete client

**Key Features**:
- Client filtering by name, VAT, tax code
- Pagination support
- Soft delete functionality
- Audit trail (created_by, updated_by)


#### 5.2.2 Client Addresses
**Controller**: `ClientAddressController.php`
**Service**: `ClientAddressService.php`
**Model**: `ClientAddress.php`

**Purpose**: Manage client physical addresses

**Endpoints**:
- `GET /api/v1/client-addresses?client_id={id}` - List client addresses
- `POST /api/v1/client-addresses/create` - Add new address
- `GET /api/v1/client-addresses/edit?id={id}` - Get address details
- `PUT /api/v1/client-addresses/update` - Update address
- `DELETE /api/v1/client-addresses/delete?id={id}` - Delete address

#### 5.2.3 Client Bank Accounts
**Controller**: `ClientBankAccountController.php`
**Service**: `ClientBankAccountService.php`
**Model**: `ClientBankAccount.php`

**Purpose**: Manage client banking information for payments

**Endpoints**:
- `GET /api/v1/client-bank-accounts?client_id={id}` - List bank accounts
- `POST /api/v1/client-bank-accounts/create` - Add bank account
- `GET /api/v1/client-bank-accounts/edit?id={id}` - Get account details
- `PUT /api/v1/client-bank-accounts/update` - Update account
- `DELETE /api/v1/client-bank-accounts/delete?id={id}` - Delete account

**Key Features**:
- Support for multiple bank accounts per client
- `is_main` flag to mark primary account
- Automatic fallback to any account if no main account exists
- Bank name linked to parameter_values table

#### 5.2.4 Client Contacts
**Controller**: `ClientContactController.php`
**Service**: `ClientContactService.php`
**Model**: `ClientContact.php`

**Purpose**: Manage contact persons for clients

**Endpoints**:
- `GET /api/v1/client-contacts?client_id={id}` - List contacts
- `POST /api/v1/client-contacts/create` - Add contact
- `GET /api/v1/client-contacts/edit?id={id}` - Get contact details
- `PUT /api/v1/client-contacts/update` - Update contact
- `DELETE /api/v1/client-contacts/delete?id={id}` - Delete contact


#### 5.2.5 Client Service Discounts
**Controller**: `ClientServiceCategoryDiscountController.php`
**Service**: `ClientServiceDiscountService.php`
**Model**: `ClientServiceDiscount.php`

**Purpose**: Manage client-specific pricing and discounts for services

**Endpoints**:
- `GET /api/v1/client-service-discounts?client_id={id}` - List discounts
- `POST /api/v1/client-service-discounts/create` - Create discount
- `GET /api/v1/client-service-discounts/edit?id={id}` - Get discount details
- `PUT /api/v1/client-service-discounts/update` - Update discount
- `DELETE /api/v1/client-service-discounts/delete?id={id}` - Delete discount
- `POST /api/v1/client-service-discounts/changeShow` - Toggle visibility

**Key Features**:
- Percentage or fixed amount discounts
- Custom pricing per client per service
- Show/hide discounts in client view
- Active/inactive status

#### 5.2.6 Client Payment Installments
**Controller**: `ClientPayInstallmentController.php`
**Model**: `ClientPayInstallment.php`

**Purpose**: Manage payment installment plans

**Endpoints**:
- `GET /api/v1/client-pay-installments?client_id={id}` - List installments
- `POST /api/v1/client-pay-installments/create` - Create installment plan
- `GET /api/v1/client-pay-installments/edit?id={id}` - Get details
- `PUT /api/v1/client-pay-installments/update` - Update installment
- `DELETE /api/v1/client-pay-installments/delete?id={id}` - Delete installment

**Related Controllers**:
- `ClientPayInstallmentSubDataController.php`: Manage individual installment payments
- `ClientPayInstallmentDividerController.php`: Calculate installment division
- `ClientPayInstallmentEndDateController.php`: Calculate end dates

**Key Features**:
- Automatic installment calculation based on payment steps
- Date calculation considering weekends and holidays
- Support for monthly, quarterly, annual payments
- Link to invoices when billed


### 5.3 Service Category Module

**Location**: `app/Models/ServiceCategory/`, `app/Services/ServiceCategory/`, `app/Http/Controllers/Api/Private/ServiceCategory/`

**Purpose**: Manage catalog of services/products offered

**Controller**: `ServiceCategoryController.php`
**Service**: `ServiceCategoryService.php`
**Model**: `ServiceCategory.php`

**Endpoints**:
- `GET /api/v1/service-categories` - List all services
- `POST /api/v1/service-categories/create` - Create service
- `GET /api/v1/service-categories/edit?id={id}` - Get service details
- `PUT /api/v1/service-categories/update` - Update service
- `DELETE /api/v1/service-categories/delete?id={id}` - Delete service

**Key Fields**:
- `name`: Service name
- `code`: Service code for electronic invoicing (FatturaPA)
- `price`: Base price
- `service_type_id`: Service type classification
- `add_to_invoice`: Auto-add to recurring invoices
- `extra_is_pricable`: Has additional pricing component
- `extra_price`: Additional price amount
- `extra_price_description`: Description of additional charge

**Import Feature**:
- `POST /api/v1/import-service-categories` - Bulk import from Excel/CSV
- Uses `ServiceCategoryImport.php` class

### 5.4 Task Management Module

**Location**: `app/Models/Task/`, `app/Services/Task/`, `app/Http/Controllers/Api/Private/Task/`

**Purpose**: Track billable work and time

#### 5.4.1 Task CRUD
**Controller**: `TaskController.php`
**Service**: `TaskService.php`
**Model**: `Task.php`

**Endpoints**:
- `GET /api/v1/tasks` - List tasks with filtering
- `POST /api/v1/tasks/create` - Create new task
- `GET /api/v1/tasks/edit?id={id}` - Get task details
- `PUT /api/v1/tasks/update` - Update task
- `DELETE /api/v1/tasks/delete?id={id}` - Delete task
- `PUT /api/v1/tasks/change-status` - Change task status

**Task Statuses** (from `TaskStatus.php` enum):
- `TO_WORK` (1): Pending
- `IN_PROGRESS` (2): In progress
- `COMPLETED` (3): Completed


#### 5.4.2 Time Logging
**Controller**: `TaskTimeLogController.php`
**Service**: `TaskTimeLogService.php`
**Model**: `TaskTimeLog.php`

**Endpoints**:
- `GET /api/v1/task-time-logs?task_id={id}` - List time logs
- `POST /api/v1/task-time-logs/create` - Create time log
- `GET /api/v1/task-time-logs/edit?id={id}` - Get log details
- `PUT /api/v1/task-time-logs/update` - Update time log
- `DELETE /api/v1/task-time-logs/delete?id={id}` - Delete time log
- `PUT /api/v1/task-time-logs/change-time` - Modify time entry

**Key Features**:
- Support for time durations exceeding 24 hours (e.g., "39:18:23")
- Automatic time calculation (end_time - start_time)
- Manual and automatic time log types
- Date-based filtering

**Time Calculation Logic**:
The system handles time calculations manually instead of using Carbon::parse() to support durations over 24 hours:
```php
// Example from Task.php model
public function getCurrentTimeAttribute() {
    $timeParts = explode(':', $this->current_time);
    $hours = (int)($timeParts[0] ?? 0);
    $minutes = (int)($timeParts[1] ?? 0);
    $seconds = (int)($timeParts[2] ?? 0);
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}
```

#### 5.4.3 Admin Task Views
**Controller**: `AdminTaskController.php`

**Endpoints**:
- `GET /api/v1/admin-tasks` - List all tasks (admin view)
- `GET /api/v1/admin-ticket-export` - Export tasks to Excel

**Purpose**: Provides administrative overview of all tasks across all users and clients

#### 5.4.4 Active Tasks
**Controller**: `ActiveTaskController.php`

**Endpoints**:
- `GET /api/v1/user-active-tasks` - Get current user's active tasks
- `PUT /api/v1/user-active-tasks/update` - Update active task

**Purpose**: Quick access to tasks currently being worked on by the logged-in user


### 5.5 Invoice Module

**Location**: `app/Models/Invoice/`, `app/Http/Controllers/Api/Private/Invoice/`

**Purpose**: Generate and manage invoices

#### 5.5.1 Invoice CRUD
**Controller**: `InvoiceController.php`
**Model**: `Invoice.php`, `InvoiceDetail.php`

**Endpoints**:
- `GET /api/v1/invoices` - List invoices
- `POST /api/v1/invoices/create` - Create invoice
- `GET /api/v1/invoices/edit?id={id}` - Get invoice details
- `PUT /api/v1/invoices/update` - Update invoice
- `POST /api/v1/invoices/add-tasks` - Add tasks to invoice
- `POST /api/v1/invoices/generate-xml-number` - Generate XML invoice number
- `PUT /api/v1/invoices/pay-invoice` - Mark invoice as paid

**Invoice Creation Process**:
1. Create invoice header with client and date range
2. Add invoice details (line items) - can be:
   - Tasks (billable work)
   - ClientPayInstallment (installment payments)
   - ClientPayInstallmentSubData (sub-installments)
3. Apply discounts (client-level or invoice-level)
4. Calculate totals including tax
5. Generate invoice number
6. Export to PDF/XML/CSV

#### 5.5.2 Invoice Details
**Controller**: `InvoiceDetailController.php`
**Model**: `InvoiceDetail.php`

**Endpoints**:
- `GET /api/v1/invoice-details?invoice_id={id}` - List invoice items
- `POST /api/v1/invoice-details/create` - Add line item
- `GET /api/v1/invoice-details/edit?id={id}` - Get item details
- `PUT /api/v1/invoice-details/update` - Update line item
- `DELETE /api/v1/invoice-details/delete?id={id}` - Delete line item

**Polymorphic Structure**:
Invoice details use polymorphic relationships to link to different entity types:
- `invoiceable_type`: "App\Models\Task\Task", "App\Models\Client\ClientPayInstallment", etc.
- `invoiceable_id`: ID of the related entity


#### 5.5.3 Recurring Invoices
**Controller**: `RecurringInvoiceController.php`, `RecurringInvoiceToAllClientsController.php`

**Endpoints**:
- `POST /api/v1/recurring-invoices/create` - Create recurring invoice for one client
- `POST /api/v1/clients/recurring-all-invoices/create` - Create recurring invoices for all clients

**Purpose**: Automatically generate invoices for subscription-based services

**Process**:
1. Select clients with `has_recurring_invoice = true`
2. Determine date range (start_at, end_at from request)
3. Find all service categories with `add_to_invoice = true`
4. Check for client-specific discounts
5. Create invoice with all applicable services
6. Apply client-level additional taxes if configured
7. Apply invoice-level discounts if specified

**Key Features**:
- Bulk invoice generation for all clients
- Automatic service inclusion based on configuration
- Client-specific pricing and discounts
- Date range specification (monthly, quarterly, etc.)

#### 5.5.4 Payment Tracking
**Controller**: `PayInvoiceController.php`

**Endpoints**:
- `PUT /api/v1/invoices/pay-invoice` - Mark invoice as paid

**Fields Updated**:
- `pay_status`: Set to true (paid)
- `pay_date`: Date payment received

#### 5.5.5 Email Functionality
**Controllers**: `SendEmailController.php`, `ClientEmailController.php`, `SendInvoiceController.php`
**Mail**: `InvoiceEmail.php`

**Endpoints**:
- `POST /api/v1/send-invoice-email` - Email invoice to client
- `GET /api/v1/client-email/edit?client_id={id}` - Get client email
- `POST /api/v1/send-uploaded-invoice` - Send uploaded invoice file

**Purpose**: Send invoices via email to clients


### 5.6 Reporting and Export Module

**Location**: `app/Http/Controllers/Api/Private/Reports/`, `app/Services/Reports/`

#### 5.6.1 Invoice Export
**Controller**: `InvoiceReportExportController.php`
**Service**: `ReportService.php`

**Endpoint**:
- `GET /api/v1/export-invoice-report?invoiceIds[]={id}&type={format}`

**Supported Formats**:
- `pdf`: PDF invoice (Italian format)
- `csv`: CSV export for accounting software
- `xml`: FatturaPA XML (Italian electronic invoice format)

**PDF Generation**:
- Uses Blade template: `resources/views/invoice_pdf_report.blade.php`
- Uses DomPDF library
- Italian invoice format with company branding
- Includes payment details, bank information, line items

**XML Generation (FatturaPA)**:
- Compliant with Italian electronic invoicing standard
- XML structure with proper namespaces
- Includes:
  - Sender information (ELABORAZIONI SRL)
  - Client information (VAT, tax code, address)
  - Invoice details with service codes
  - Payment information (IBAN, ABI, CAB)
  - Tax calculations
- Special character handling (removes accents: à→a, è→e, etc.)
- Sequential numbering system using parameter_values (parameter_order = 13)

**XML Numbering Logic**:
```php
// Always increment from parameter_value
$parameterValue = ParameterValue::where('parameter_order', 13)->lockForUpdate()->first();
$currentNumber = $parameterValue->parameter_value; // e.g., "1/57"
// Increment second part: "1/58"
// Update parameter_value for next invoice
```

**CSV Export**:
- Exports invoice data for import into accounting software
- Includes client info, line items, totals


#### 5.6.2 Client Payment Export
**Controller**: `ClientPaymentExportController.php`

**Endpoint**:
- `GET /api/v1/export-client-payment?client_id={id}&start_date={date}&end_date={date}`

**Purpose**: Export client payment history for a date range

#### 5.6.3 Task Export
**Controller**: `AdminTaskExportController.php`
**Export**: `TasksExport.php`

**Endpoint**:
- `GET /api/v1/admin-ticket-export?filters...`

**Purpose**: Export task data to Excel with filtering options

#### 5.6.4 General Reports
**Controller**: `ReportController.php`

**Endpoint**:
- `GET /api/v1/reports?type={report_type}&filters...`

**Purpose**: Generate various financial and operational reports

### 5.7 Parameter Management Module

**Location**: `app/Models/Parameter/`, `app/Services/Parameter/`, `app/Http/Controllers/Api/Private/Parameter/`

**Purpose**: Manage system configuration and lookup tables

**Controller**: `ParameterValueController.php`
**Service**: `ParameterService.php`
**Models**: `Parameter.php`, `ParameterValue.php`

**Endpoints**:
- `GET /api/v1/parameters` - List parameter values
- `POST /api/v1/parameters/create` - Create parameter value
- `GET /api/v1/parameters/edit?id={id}` - Get parameter details
- `PUT /api/v1/parameters/update` - Update parameter value
- `DELETE /api/v1/parameters/delete?id={id}` - Delete parameter value

**Common Parameter Types** (by parameter_order):
- **1**: Payment methods (MP01=Cash, MP05=Bank transfer, MP12=Direct debit, etc.)
- **2**: Payment steps (number of installments: 1, 2, 3, 4, 6, 12)
- **3**: Service types
- **7**: Company bank accounts (for receiving payments)
- **13**: Invoice XML numbering sequence

**Key Features**:
- Flexible lookup table system
- Support for multiple description fields
- Default value marking
- Sort ordering
- Used throughout the system for dropdowns and configuration


### 5.8 User Management Module

**Location**: `app/Models/User.php`, `app/Services/User/`, `app/Http/Controllers/Api/Private/User/`

**Purpose**: Manage system users (employees)

**Controller**: `UserController.php`
**Service**: `UserService.php`
**Model**: `User.php`

**Endpoints**:
- `GET /api/v1/users` - List users
- `POST /api/v1/users/create` - Create user
- `GET /api/v1/users/edit?id={id}` - Get user details
- `PUT /api/v1/users/update` - Update user
- `DELETE /api/v1/users/delete?id={id}` - Delete user
- `PUT /api/v1/users/change-status` - Activate/deactivate user

**User Roles and Permissions**:
- Uses Spatie Laravel Permission package
- Role-based access control
- Permission checking via `checkPermission` middleware

### 5.9 Select/Dropdown Module

**Location**: `app/Services/Select/`, `app/Http/Controllers/Api/Private/Select/`

**Purpose**: Provide dropdown data for forms

**Controller**: `SelectController.php`
**Services**: Multiple select services

**Endpoints**:
- `GET /api/v1/selects?type={select_type}` - Get dropdown options
- `GET /api/v1/selects/invoices` - Get invoice list for selection

**Available Select Types**:
- `clients`: Client list
- `users`: User list
- `roles`: Role list
- `permissions`: Permission list
- `service_categories`: Service list
- `parameters`: Parameter values by type
- `invoices`: Invoice list

**Response Format**:
```json
{
  "data": [
    {"id": 1, "name": "Option 1"},
    {"id": 2, "name": "Option 2"}
  ]
}
```


### 5.10 Import Module

**Location**: `app/Imports/`, `app/Http/Controllers/`

**Purpose**: Bulk data import from Excel/CSV files

#### 5.10.1 Client Import
**Controller**: `ImportClientController.php`
**Import Class**: `ClientImport.php`

**Endpoint**:
- `POST /api/v1/import-clients` - Upload Excel/CSV file with client data

**Expected Columns**:
- Company name (ragione_sociale)
- VAT number (iva)
- Tax code (cf)
- Email, phone
- Payment information
- Other client fields

#### 5.10.2 Service Category Import
**Controller**: `ImportServiceCategoryController.php`
**Import Class**: `ServiceCategoryImport.php`

**Endpoint**:
- `POST /api/v1/import-service-categories` - Upload service catalog

#### 5.10.3 Bank Account Import
**Controller**: `ImportClientBankAccountController.php`
**Import Class**: `ClientBankAccountImport.php`

**Endpoint**:
- `POST /api/v1/import-client-bank-accounts` - Upload bank account data

**Technology**: Uses Maatwebsite/Excel package for Excel/CSV processing

### 5.11 OCR Module

**Location**: `app/Http/Controllers/Api/Private/Invoice/`

**Controller**: `ImageToExcelController.php`

**Endpoint**:
- `POST /api/v1/image-to-excel` - Convert invoice image to Excel

**Purpose**: Extract invoice data from images using OCR (Tesseract)

**Technology**: 
- Tesseract OCR for text extraction
- Image processing with Intervention Image
- PDF parsing with Smalot PDF Parser

**Use Case**: Digitize paper invoices or PDF invoices into structured data


---

## 6. API Endpoints Reference

### 6.1 Authentication Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/v1/auth/login` | User login | No |
| POST | `/api/v1/auth/logout` | User logout | Yes |

### 6.2 Client Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/v1/clients` | List clients | Yes |
| POST | `/api/v1/clients/create` | Create client | Yes |
| GET | `/api/v1/clients/edit?id={id}` | Get client | Yes |
| PUT | `/api/v1/clients/update` | Update client | Yes |
| DELETE | `/api/v1/clients/delete?id={id}` | Delete client | Yes |
| GET | `/api/v1/client-addresses` | List addresses | Yes |
| POST | `/api/v1/client-addresses/create` | Create address | Yes |
| GET | `/api/v1/client-bank-accounts` | List bank accounts | Yes |
| POST | `/api/v1/client-bank-accounts/create` | Create bank account | Yes |
| GET | `/api/v1/client-contacts` | List contacts | Yes |
| POST | `/api/v1/client-contacts/create` | Create contact | Yes |
| GET | `/api/v1/client-service-discounts` | List discounts | Yes |
| POST | `/api/v1/client-service-discounts/create` | Create discount | Yes |
| POST | `/api/v1/client-service-discounts/changeShow` | Toggle visibility | Yes |

### 6.3 Payment Installment Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/v1/client-pay-installments` | List installments | Yes |
| POST | `/api/v1/client-pay-installments/create` | Create installment | Yes |
| GET | `/api/v1/client-pay-installments/edit?id={id}` | Get installment | Yes |
| PUT | `/api/v1/client-pay-installments/update` | Update installment | Yes |
| DELETE | `/api/v1/client-pay-installments/delete?id={id}` | Delete installment | Yes |
| GET | `/api/v1/client-pay-installment-sub-data` | List sub-installments | Yes |
| POST | `/api/v1/client-pay-installment-sub-data/create` | Create sub-installment | Yes |
| GET | `/api/v1/client-pay-installment-divider` | Calculate installments | Yes |
| GET | `/api/v1/installment-end-at` | Calculate end date | Yes |
| GET | `/api/v1/client-payment-type` | Get payment types | Yes |
| GET | `/api/v1/client-payment-period` | Get payment periods | Yes |


### 6.4 Service Category Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/v1/service-categories` | List services | Yes |
| POST | `/api/v1/service-categories/create` | Create service | Yes |
| GET | `/api/v1/service-categories/edit?id={id}` | Get service | Yes |
| PUT | `/api/v1/service-categories/update` | Update service | Yes |
| DELETE | `/api/v1/service-categories/delete?id={id}` | Delete service | Yes |

### 6.5 Task Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/v1/tasks` | List tasks | Yes |
| POST | `/api/v1/tasks/create` | Create task | Yes |
| GET | `/api/v1/tasks/edit?id={id}` | Get task | Yes |
| PUT | `/api/v1/tasks/update` | Update task | Yes |
| DELETE | `/api/v1/tasks/delete?id={id}` | Delete task | Yes |
| PUT | `/api/v1/tasks/change-status` | Change status | Yes |
| GET | `/api/v1/admin-tasks` | List all tasks (admin) | Yes |
| GET | `/api/v1/user-active-tasks` | Get active tasks | Yes |
| PUT | `/api/v1/user-active-tasks/update` | Update active task | Yes |

### 6.6 Time Log Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/v1/task-time-logs` | List time logs | Yes |
| POST | `/api/v1/task-time-logs/create` | Create time log | Yes |
| GET | `/api/v1/task-time-logs/edit?id={id}` | Get time log | Yes |
| PUT | `/api/v1/task-time-logs/update` | Update time log | Yes |
| DELETE | `/api/v1/task-time-logs/delete?id={id}` | Delete time log | Yes |
| PUT | `/api/v1/task-time-logs/change-time` | Modify time | Yes |

### 6.7 Invoice Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/v1/invoices` | List invoices | Yes |
| POST | `/api/v1/invoices/create` | Create invoice | Yes |
| GET | `/api/v1/invoices/edit?id={id}` | Get invoice | Yes |
| PUT | `/api/v1/invoices/update` | Update invoice | Yes |
| POST | `/api/v1/invoices/add-tasks` | Add tasks to invoice | Yes |
| POST | `/api/v1/invoices/generate-xml-number` | Generate XML number | Yes |
| PUT | `/api/v1/invoices/pay-invoice` | Mark as paid | Yes |
| GET | `/api/v1/invoice-details` | List invoice items | Yes |
| POST | `/api/v1/invoice-details/create` | Add invoice item | Yes |
| PUT | `/api/v1/invoice-details/update` | Update item | Yes |
| DELETE | `/api/v1/invoice-details/delete?id={id}` | Delete item | Yes |
| POST | `/api/v1/recurring-invoices/create` | Create recurring invoice | Yes |
| POST | `/api/v1/clients/recurring-all-invoices/create` | Bulk recurring invoices | Yes |


### 6.8 Report and Export Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/v1/reports` | General reports | Yes |
| GET | `/api/v1/export-invoice-report?invoiceIds[]={id}&type={format}` | Export invoice (PDF/CSV/XML) | Yes |
| GET | `/api/v1/export-client-payment` | Export payment history | Yes |
| GET | `/api/v1/admin-ticket-export` | Export tasks to Excel | Yes |

**Export Type Parameter Values**:
- `pdf`: Generate PDF invoice
- `csv`: Generate CSV export
- `xml`: Generate FatturaPA XML

### 6.9 Email Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/v1/send-invoice-email` | Email invoice to client | Yes |
| GET | `/api/v1/client-email/edit?client_id={id}` | Get client email | Yes |
| POST | `/api/v1/send-uploaded-invoice` | Send uploaded invoice | Yes |

### 6.10 User Management Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/v1/users` | List users | Yes |
| POST | `/api/v1/users/create` | Create user | Yes |
| GET | `/api/v1/users/edit?id={id}` | Get user | Yes |
| PUT | `/api/v1/users/update` | Update user | Yes |
| DELETE | `/api/v1/users/delete?id={id}` | Delete user | Yes |
| PUT | `/api/v1/users/change-status` | Change user status | Yes |

### 6.11 Parameter Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/v1/parameters` | List parameters | Yes |
| POST | `/api/v1/parameters/create` | Create parameter | Yes |
| GET | `/api/v1/parameters/edit?id={id}` | Get parameter | Yes |
| PUT | `/api/v1/parameters/update` | Update parameter | Yes |
| DELETE | `/api/v1/parameters/delete?id={id}` | Delete parameter | Yes |

### 6.12 Select/Dropdown Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/v1/selects?type={type}` | Get dropdown options | Yes |
| GET | `/api/v1/selects/invoices` | Get invoice list | Yes |


### 6.13 Import Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/v1/import-clients` | Import clients from Excel/CSV | Yes |
| POST | `/api/v1/import-service-categories` | Import services from Excel/CSV | Yes |
| POST | `/api/v1/import-client-bank-accounts` | Import bank accounts | Yes |

### 6.14 OCR Endpoint

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/v1/image-to-excel` | Convert invoice image to Excel | Yes |

---

## 7. Configuration & Execution

### 7.1 Environment Configuration

**File**: `.env`

**Key Configuration Variables**:

```env
# Application
APP_NAME="Accountant System"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=accountant_db
DB_USERNAME=root
DB_PASSWORD=

# JWT Authentication
JWT_SECRET=...
JWT_TTL=60
JWT_REFRESH_TTL=20160

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# File Storage
FILESYSTEM_DISK=local
```


### 7.2 Installation Steps

1. **Clone Repository**
```bash
git clone <repository-url>
cd accountant-system
```

2. **Install Dependencies**
```bash
composer install
npm install
```

3. **Environment Setup**
```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

4. **Database Setup**
```bash
# Create database
mysql -u root -p
CREATE DATABASE accountant_db;

# Run migrations
php artisan migrate

# Seed initial data (optional)
php artisan db:seed
```

5. **Storage Setup**
```bash
php artisan storage:link
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

6. **Start Server**
```bash
# Development
php artisan serve

# Production (use web server like Apache/Nginx)
```

### 7.3 Key Configuration Files

#### 7.3.1 Database Configuration
**File**: `config/database.php`
- MySQL connection settings
- Connection pooling
- Query logging

#### 7.3.2 JWT Configuration
**File**: `config/jwt.php`
- Token TTL (time to live)
- Refresh token settings
- Secret key configuration

#### 7.3.3 CORS Configuration
**File**: `config/cors.php`
- Allowed origins for API access
- Allowed methods and headers
- Credentials support

#### 7.3.4 File Storage Configuration
**File**: `config/filesystems.php`
- Local storage for exports
- Public disk for uploaded files
- Storage paths configuration


### 7.4 Application Execution Flow

#### 7.4.1 Request Lifecycle

```
1. HTTP Request arrives at public/index.php
   ↓
2. Bootstrap Laravel application (bootstrap/app.php)
   ↓
3. Load service providers (app/Providers/)
   ↓
4. Route matching (routes/api.php)
   ↓
5. Middleware execution (app/Http/Middleware/)
   - Authenticate (JWT verification)
   - checkPermission (role-based access)
   - TrimStrings
   ↓
6. Controller method execution
   ↓
7. Request validation (app/Http/Requests/)
   ↓
8. Service layer business logic (app/Services/)
   ↓
9. Model/Database interaction (app/Models/)
   ↓
10. Resource transformation (app/Http/Resources/)
    ↓
11. JSON response returned
```

#### 7.4.2 Authentication Flow

```
1. User submits login credentials
   POST /api/v1/auth/login
   Body: { email, password }
   ↓
2. LoginRequest validates input
   ↓
3. AuthService verifies credentials
   - Check user exists
   - Verify password hash
   - Check user status (active)
   ↓
4. Generate JWT token
   - Include user ID and email in payload
   - Set expiration time (JWT_TTL)
   ↓
5. Return token to client
   Response: { token, user }
   ↓
6. Client stores token
   ↓
7. Subsequent requests include token
   Header: Authorization: Bearer {token}
   ↓
8. Authenticate middleware verifies token
   - Decode JWT
   - Verify signature
   - Check expiration
   - Load user from database
   ↓
9. Request proceeds if valid
```


---

## 8. Business Logic Breakdown

### 8.1 Invoice Generation Logic

**Location**: `app/Http/Controllers/Api/Private/Reports/InvoiceReportExportController.php`

#### 8.1.1 Invoice Data Collection (`getInvoiceData` method)

**Purpose**: Gather all data needed for invoice generation

**Process**:
1. **Fetch Invoice Header**
   - Get invoice by ID
   - Load client information
   - Get invoice date range

2. **Collect Invoice Items**
   - Query invoice_details table
   - For each item, determine type (Task, ClientPayInstallment, ClientPayInstallmentSubData)
   - Load related data based on type

3. **Get Service Codes**
   - For Tasks: Get code from ServiceCategory
   - For Installments: Get code from ParameterValue via parameter_value_id
   - Default to '..' if not found

4. **Calculate Pricing**
   - Start with base price
   - Apply discounts (client-specific or invoice-level)
   - Calculate price_after_discount
   - Handle extra pricing for services (extra_is_pricable)

5. **Apply Client-Level Taxes**
   - If client has total_tax > 0
   - Add tax line item to invoice
   - Calculate tax on subtotal

6. **Apply Invoice Discounts**
   - If invoice has discount_amount
   - Calculate discount (percentage or fixed)
   - Subtract from total

7. **Calculate Tax (IVA)**
   - Default 22% VAT on taxable items
   - Calculate total tax amount

8. **Get Bank Information**
   - For MP05 (bank transfer): Get company bank account from parameter_values
   - For MP12 (direct debit): Get client bank account
   - Prioritize main bank account, fallback to any account

9. **Format Address Data**
   - Get client address
   - Format for invoice display

10. **Return Complete Dataset**
    - All invoice items with descriptions and pricing
    - Client information
    - Bank details
    - Totals (subtotal, tax, grand total)


#### 8.1.2 XML Invoice Generation (`generateInvoiceXml` method)

**Purpose**: Generate FatturaPA-compliant XML for Italian electronic invoicing

**Process**:

1. **Initialize Helper Functions**
   ```php
   // Remove accents from text (à→a, è→e, etc.)
   $removeAccents = function($string) { ... };
   
   // Sanitize text for XML
   $safe = fn($v) => htmlspecialchars($removeAccents(trim($v)), ENT_XML1);
   
   // Parse dates to Y-m-d format
   $parseDate = function($value) { ... };
   ```

2. **Generate Invoice Number**
   - Use database transaction for thread safety
   - Get current number from parameter_values (parameter_order = 13)
   - Increment second part (e.g., "1/57" → "1/58")
   - Update parameter_value immediately
   - This ensures sequential numbering

3. **Build XML Structure**
   
   **Header Section (FatturaElettronicaHeader)**:
   - **DatiTrasmissione** (Transmission data)
     - Sender ID: IT 00987920196 (ELABORAZIONI SRL)
     - Recipient SDI code from client
     - Progressive invoice number
     - Format: FPR12
   
   - **CedentePrestatore** (Seller/Provider)
     - Company: ELABORAZIONI SRL
     - VAT: 00987920196
     - Address: VIA STAZIONE 9/B, 26013 CREMA (CR)
     - REA registration details
     - Contact info
   
   - **CessionarioCommittente** (Customer)
     - Client company name (sanitized)
     - Client VAT and tax code
     - Client address (sanitized)
   
   - **TerzoIntermediarioOSoggettoEmittente** (Third party)
     - Passepartout S.p.A (intermediary)

4. **Build Body Section (FatturaElettronicaBody)**
   
   **DatiGenerali** (General data):
   - Document type: TD01 (invoice)
   - Currency: EUR
   - Invoice date
   - Invoice number (extracted from progressive)
   - Total amount
   - Causale (description from first line item)
   
   **DatiBeniServizi** (Goods/Services):
   - For each invoice item:
     - Line number
     - Service code (CodiceArticolo)
     - Description (sanitized)
     - Quantity: 1.00
     - Unit: NR (number)
     - Unit price
     - Total price
     - VAT rate (default 22%)
   
   **DatiRiepilogo** (Summary):
   - VAT rate: 22%
   - Taxable amount
   - Tax amount
   - VAT exigibility: I (immediate)


5. **Payment Information (DatiPagamento)**
   - Payment terms: TP02 (complete payment)
   - Payment method code (MP05, MP12, etc.)
   - Due date
   - Payment amount
   
   **For MP05 (Bank Transfer)**:
   - Bank name (from company bank account)
   - IBAN
   
   **For MP12 (Direct Debit)**:
   - Bank name (from client bank account)
   - ABI code (Italian bank code)
   - CAB code (Italian branch code)

6. **Add XML Namespaces**
   - Convert SimpleXML to DOMDocument
   - Add proper namespace: `http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2`
   - Add prefix `p:` to root element
   - Add additional namespaces (ds, xsi)

7. **Save and Return**
   - Generate filename: `00987920196_{padded_number}.xml`
   - Save to storage: `storage/app/exportedInvoices/`
   - Update invoice record with XML number
   - Return XML content (converted to UTF-8)

**Key Features**:
- Thread-safe numbering with database locks
- Accent removal for compatibility
- Proper XML encoding (windows-1252)
- Validation against FatturaPA schema
- Sequential numbering system

#### 8.1.3 PDF Invoice Generation (`generateInvoicePdf` method)

**Purpose**: Generate printable PDF invoice

**Process**:
1. Get invoice data using `getInvoiceData()`
2. Load Blade template: `resources/views/invoice_pdf_report.blade.php`
3. Pass data to template
4. Render HTML
5. Convert to PDF using DomPDF
6. Return PDF file or download

**Template Features**:
- Italian invoice format
- Company branding (ELABORAZIONI SRL)
- Client information
- Line items table
- Tax calculations
- Payment details with bank information
- Professional styling


### 8.2 Recurring Invoice Logic

**Location**: `app/Http/Controllers/Api/Private/Invoice/RecurringInvoiceController.php`

**Purpose**: Automatically generate invoices for subscription-based services

#### 8.2.1 Single Client Recurring Invoice

**Endpoint**: `POST /api/v1/recurring-invoices/create`

**Process**:
1. **Receive Request Data**
   - client_id
   - start_at (invoice period start)
   - end_at (invoice period end)
   - discount_type (optional)
   - discount_amount (optional)
   - payment_type_id (optional)

2. **Use Dates Directly**
   - No recalculation of dates
   - Use start_at and end_at exactly as provided
   - No weekend/holiday adjustments

3. **Create Invoice Header**
   - Generate invoice number
   - Link to client
   - Set date range
   - Set payment method

4. **Find Applicable Services**
   - Query service_categories where add_to_invoice = true
   - These are services that should be automatically included

5. **Check Client-Specific Pricing**
   - For each service, check client_service_discounts table
   - If custom pricing exists, use it
   - If discount exists, apply it
   - Otherwise, use base service price

6. **Create Invoice Details**
   - For each applicable service:
     - Create invoice_detail record
     - Set invoiceable_type to service type
     - Set price and price_after_discount
     - Set description

7. **Apply Client-Level Taxes**
   - If client.total_tax > 0
   - Add additional tax line item
   - Use client.total_tax_description

8. **Apply Invoice Discount**
   - If discount specified in request
   - Calculate discount amount
   - Add discount line item (negative amount)

9. **Return Created Invoice**


#### 8.2.2 Bulk Recurring Invoices

**Endpoint**: `POST /api/v1/clients/recurring-all-invoices/create`

**Purpose**: Generate recurring invoices for all eligible clients at once

**Process**:
1. **Get All Eligible Clients**
   - Query clients where has_recurring_invoice = true
   - These are clients with active subscriptions

2. **For Each Client**
   - Call single client recurring invoice logic
   - Use same date range for all
   - Use client's default payment method
   - Apply client-specific pricing and discounts

3. **Transaction Handling**
   - Wrap in database transaction
   - If any invoice fails, rollback all
   - Ensures data consistency

4. **Return Summary**
   - Count of invoices created
   - List of invoice IDs
   - Any errors encountered

### 8.3 Payment Installment Calculation Logic

**Location**: `app/Http/Controllers/Api/Private/Client/ClientPayInstallmentDividerController.php`

**Purpose**: Calculate payment installment schedule

**Process**:
1. **Receive Input**
   - client_id
   - price (total amount)
   - payStepsId (number of installments from parameter_values)
   - startDate (first payment date)

2. **Get Installment Count**
   - Query parameter_values by payStepsId
   - Extract number from description field
   - Common values: 1, 2, 3, 4, 6, 12

3. **Get Client Payment Terms**
   - Get client.allowed_days_to_pay
   - Default payment period between installments

4. **Calculate Installment Amount**
   - Divide total price by number of installments
   - Round to 2 decimal places
   - Handle remainder in last installment

5. **Calculate Payment Dates**
   - Start with provided startDate
   - For each installment:
     - Add payment period (monthly, quarterly, etc.)
     - Skip weekends (move to next Monday)
     - Skip holidays (configurable)
     - Handle month-end dates (e.g., Jan 31 → Feb 28)

6. **Special Date Handling**
   - August 31: Special handling for summer
   - December 31: Year-end considerations
   - Month-end dates: Adjust to last day of target month

7. **Return Installment Schedule**
   - Array of installments with:
     - Installment number
     - Amount
     - Start date
     - End date
     - Payment due date


### 8.4 Time Tracking Logic

**Location**: `app/Services/Task/TaskService.php`, `app/Models/Task/Task.php`

**Purpose**: Track billable hours with support for durations exceeding 24 hours

#### 8.4.1 Time Calculation

**Challenge**: Standard time formats don't support durations over 24 hours (e.g., "39:18:23")

**Solution**: Manual time parsing and calculation

**Key Methods in Task Model**:

```php
// Get current time attribute (supports > 24 hours)
public function getCurrentTimeAttribute() {
    if (!$this->current_time) return '00:00:00';
    
    $timeParts = explode(':', $this->current_time);
    $hours = (int)($timeParts[0] ?? 0);
    $minutes = (int)($timeParts[1] ?? 0);
    $seconds = (int)($timeParts[2] ?? 0);
    
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

// Get total hours (supports > 24 hours)
public function getTotalHoursAttribute() {
    $totalSeconds = 0;
    
    foreach ($this->taskTimeLogs as $log) {
        if ($log->total_time) {
            $parts = explode(':', $log->total_time);
            $hours = (int)($parts[0] ?? 0);
            $minutes = (int)($parts[1] ?? 0);
            $seconds = (int)($parts[2] ?? 0);
            
            $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
        }
    }
    
    $hours = floor($totalSeconds / 3600);
    $minutes = floor(($totalSeconds % 3600) / 60);
    $seconds = $totalSeconds % 60;
    
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}
```

**Process**:
1. Parse time string into components (hours, minutes, seconds)
2. Convert to total seconds for calculations
3. Perform arithmetic operations
4. Convert back to HH:MM:SS format
5. No 24-hour limit on hours component

#### 8.4.2 Time Log Creation

**Process**:
1. User creates time log with start_time and end_time
2. System calculates total_time = end_time - start_time
3. If duration spans multiple days, hours can exceed 24
4. Store as string in HH:MM:SS format
5. Aggregate all time logs for task total


### 8.5 Client Bank Account Selection Logic

**Location**: `app/Http/Controllers/Api/Private/Reports/InvoiceReportExportController.php`

**Purpose**: Select appropriate bank account for invoice payment details

**Process**:
1. **Try to Get Main Bank Account**
   ```php
   $clientBankAccount = ClientBankAccount::with('bank')
       ->where('client_id', $client->id)
       ->where('is_main', 1)
       ->first();
   ```

2. **Fallback to Any Bank Account**
   ```php
   if($clientBankAccount == null){
       $clientBankAccount = ClientBankAccount::with('bank')
           ->where('client_id', $client->id)
           ->first();
   }
   ```

3. **Extract Bank Information**
   - IBAN: Direct from client_bank_accounts.iban
   - ABI: Direct from client_bank_accounts.abi
   - CAB: Direct from client_bank_accounts.cab
   - Bank Name: From parameter_values.parameter_value via bank_id relationship

4. **Validation for XML Export**
   - Check that CAB, ABI, and bank name are present
   - If any missing, return error: "Questo cliente non ha CAB, ABI e nome banca associati"
   - Prevents invalid XML generation

**Rationale**: 
- Prioritizes main account for consistency
- Provides fallback to ensure invoices can be generated
- Validates required fields for electronic invoicing

### 8.6 Service Code Resolution Logic

**Location**: `app/Http/Controllers/Api/Private/Reports/InvoiceReportExportController.php`

**Purpose**: Get correct service code for electronic invoicing

**Process**:
1. **Determine Invoice Item Type**
   - Check invoiceable_type field
   - Can be: Task, ClientPayInstallment, ClientPayInstallmentSubData

2. **Get Service Code Based on Type**
   
   **For Tasks**:
   ```php
   if ($invoiceItem->invoiceable_type == Task::class) {
       $serviceCode = $invoiceItemData->serviceCategory->code ?? '..';
   }
   ```
   - Get code from service_categories.code
   
   **For ClientPayInstallment**:
   ```php
   elseif ($invoiceItem->invoiceable_type == ClientPayInstallment::class) {
       $serviceCode = $invoiceItemData->parameterValue->code ?? '..';
   }
   ```
   - Get code from parameter_values.code via parameter_value_id
   
   **For ClientPayInstallmentSubData**:
   ```php
   elseif ($invoiceItem->invoiceable_type == ClientPayInstallmentSubData::class) {
       $serviceCode = $invoiceItemData->parameterValue->code ?? '..';
   }
   ```
   - Get code from parameter_values.code via parameter_value_id

3. **Default Value**
   - If code not found or null, use '..'
   - Ensures XML always has a valid code

**Rationale**: Different invoice item types store service codes in different tables based on their nature.


### 8.7 Special Character Handling for XML

**Location**: `app/Http/Controllers/Api/Private/Reports/InvoiceReportExportController.php`

**Purpose**: Ensure XML compatibility by removing accented characters

**Problem**: Italian invoice processing software rejects XML with accented characters (à, è, ò, etc.)

**Solution**: Character normalization function

```php
$removeAccents = function($string) {
    $accents = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
        // Uppercase versions
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
```

**Applied To**:
- Client names (ragione_sociale)
- Bank names (IstitutoFinanziario)
- Addresses
- Service descriptions
- All text fields in XML

**Examples**:
- "BANCA DI CREDITO COOPERATIVO" → unchanged
- "Società" → "Societa"
- "contabilità" → "contabilita"
- "Università" → "Universita"

---

## 9. Key Dependencies

### 9.1 Core Laravel Packages

| Package | Version | Purpose |
|---------|---------|---------|
| laravel/framework | ^10.0 | Core framework |
| laravel/sanctum | ^3.2 | API authentication (not actively used) |
| tymon/jwt-auth | ^2.1 | JWT authentication (primary auth) |
| spatie/laravel-permission | ^6.10 | Role-based access control |
| spatie/laravel-query-builder | ^6.2 | API filtering and sorting |


### 9.2 Document Generation Packages

| Package | Version | Purpose |
|---------|---------|---------|
| barryvdh/laravel-dompdf | ^3.1 | PDF generation |
| maatwebsite/excel | ^3.1 | Excel import/export |
| phpoffice/phpspreadsheet | ^1.29 | Excel file manipulation |
| setasign/fpdf | ^1.8 | PDF creation |
| setasign/fpdi | ^2.6 | PDF manipulation |
| smalot/pdfparser | ^2.11 | PDF parsing |

### 9.3 Image and OCR Packages

| Package | Version | Purpose |
|---------|---------|---------|
| intervention/image | ^3.11 | Image manipulation |
| intervention/image-laravel | ^1.5 | Laravel integration |
| spatie/pdf-to-image | ^1.2 | PDF to image conversion |
| thiagoalessio/tesseract_ocr | ^2.13 | OCR text extraction |

### 9.4 Utility Packages

| Package | Version | Purpose |
|---------|---------|---------|
| guzzlehttp/guzzle | ^7.2 | HTTP client |
| genericmilk/docudoodle | ^2.0 | Document generation |

---

## 10. Security Considerations

### 10.1 Authentication
- JWT-based authentication for all API endpoints
- Token expiration (configurable TTL)
- Secure password hashing (bcrypt)
- Token refresh mechanism

### 10.2 Authorization
- Role-based access control (Spatie Permission)
- Permission checking middleware
- User status verification (active/inactive)

### 10.3 Data Protection
- SQL injection prevention (Eloquent ORM)
- XSS protection (input sanitization)
- CSRF protection (for web routes)
- Soft deletes (data recovery)
- Audit trail (created_by, updated_by)

### 10.4 Input Validation
- Form Request validation classes
- Type-safe enums for status values
- Database constraints (foreign keys, NOT NULL)

### 10.5 File Security
- Controlled file upload locations
- File type validation
- Storage outside public directory
- Secure file serving


---

## 11. Common Workflows

### 11.1 Complete Client Onboarding Workflow

1. **Create Client**
   - POST `/api/v1/clients/create`
   - Provide company details, VAT, tax code

2. **Add Client Address**
   - POST `/api/v1/client-addresses/create`
   - Provide street address, city, postal code

3. **Add Client Bank Account**
   - POST `/api/v1/client-bank-accounts/create`
   - Provide IBAN, ABI, CAB, bank name
   - Mark as main account

4. **Add Client Contacts**
   - POST `/api/v1/client-contacts/create`
   - Add contact persons with email and phone

5. **Configure Service Pricing**
   - POST `/api/v1/client-service-discounts/create`
   - Set custom pricing or discounts for services

6. **Enable Recurring Invoices** (if applicable)
   - PUT `/api/v1/clients/update`
   - Set `has_recurring_invoice = true`

### 11.2 Task and Time Tracking Workflow

1. **Create Task**
   - POST `/api/v1/tasks/create`
   - Assign to user
   - Link to client and service category
   - Set status to "TO_WORK"

2. **Start Working**
   - PUT `/api/v1/tasks/change-status`
   - Change status to "IN_PROGRESS"

3. **Log Time**
   - POST `/api/v1/task-time-logs/create`
   - Record start_time and end_time
   - System calculates total_time

4. **Complete Task**
   - PUT `/api/v1/tasks/change-status`
   - Change status to "COMPLETED"

5. **Review Time Logs**
   - GET `/api/v1/task-time-logs?task_id={id}`
   - Verify hours logged


### 11.3 Manual Invoice Creation Workflow

1. **Create Invoice Header**
   - POST `/api/v1/invoices/create`
   - Specify client_id
   - Set date range (start_at, end_at)
   - Choose payment method

2. **Add Tasks to Invoice**
   - POST `/api/v1/invoices/add-tasks`
   - Select completed tasks
   - System creates invoice_details records

3. **Add Manual Line Items** (if needed)
   - POST `/api/v1/invoice-details/create`
   - Add custom charges or adjustments

4. **Apply Discounts** (if needed)
   - PUT `/api/v1/invoices/update`
   - Set discount_type and discount_amount

5. **Generate Invoice Number**
   - POST `/api/v1/invoices/generate-xml-number`
   - System assigns sequential XML number

6. **Export Invoice**
   - GET `/api/v1/export-invoice-report?invoiceIds[]={id}&type=pdf`
   - Generate PDF for printing
   - GET `/api/v1/export-invoice-report?invoiceIds[]={id}&type=xml`
   - Generate FatturaPA XML for electronic submission

7. **Send to Client**
   - POST `/api/v1/send-invoice-email`
   - Email invoice to client

8. **Mark as Paid** (when payment received)
   - PUT `/api/v1/invoices/pay-invoice`
   - Set pay_status = true and pay_date

### 11.4 Recurring Invoice Workflow

1. **Configure Services**
   - Ensure service_categories have `add_to_invoice = true`
   - Set up client-specific pricing if needed

2. **Enable for Clients**
   - Set `has_recurring_invoice = true` for subscription clients

3. **Generate Monthly Invoices**
   - POST `/api/v1/clients/recurring-all-invoices/create`
   - Specify date range (e.g., 2024-01-01 to 2024-01-31)
   - System creates invoices for all eligible clients

4. **Review Generated Invoices**
   - GET `/api/v1/invoices?start_date=2024-01-01&end_date=2024-01-31`
   - Verify all invoices created correctly

5. **Export and Send**
   - Bulk export to PDF/XML
   - Send to clients via email


### 11.5 Payment Installment Workflow

1. **Create Installment Plan**
   - POST `/api/v1/client-pay-installments/create`
   - Specify total price
   - Choose number of installments (pay_steps_id)
   - Set start date

2. **Calculate Installment Schedule**
   - GET `/api/v1/client-pay-installment-divider`
   - System calculates payment dates and amounts
   - Returns schedule with dates

3. **Create Sub-Installments**
   - POST `/api/v1/client-pay-installment-sub-data/create`
   - Create individual payment records
   - One for each installment in schedule

4. **Generate Invoices for Installments**
   - When payment due, add to invoice
   - POST `/api/v1/invoice-details/create`
   - Link to ClientPayInstallmentSubData

5. **Track Payment Status**
   - Mark invoice as paid when payment received
   - Update installment status

---

## 12. Troubleshooting Guide

### 12.1 Common Issues

#### Issue: JWT Token Invalid
**Symptoms**: 401 Unauthorized errors
**Solutions**:
- Verify JWT_SECRET is set in .env
- Check token expiration (JWT_TTL)
- Ensure token is included in Authorization header
- Regenerate token: `php artisan jwt:secret`

#### Issue: Database Connection Failed
**Symptoms**: SQLSTATE errors
**Solutions**:
- Verify database credentials in .env
- Check database server is running
- Test connection: `php artisan migrate:status`
- Verify database exists

#### Issue: Time Calculation Errors
**Symptoms**: Incorrect time totals, Carbon errors
**Solutions**:
- Ensure time format is HH:MM:SS
- Check for null values in time fields
- Verify manual time parsing is used (not Carbon::parse)
- Review TaskService.php time calculation logic

#### Issue: XML Export Rejected
**Symptoms**: Invoice software rejects XML
**Solutions**:
- Check for special characters (accents)
- Verify client has CAB, ABI, and bank name
- Validate XML against FatturaPA schema
- Check service codes are present
- Ensure sequential numbering is correct


#### Issue: PDF Generation Fails
**Symptoms**: Blank PDF or errors
**Solutions**:
- Check DomPDF configuration
- Verify Blade template exists
- Check for PHP memory limit
- Review error logs in storage/logs

#### Issue: Import Fails
**Symptoms**: Excel/CSV import errors
**Solutions**:
- Verify file format (Excel or CSV)
- Check column headers match expected format
- Ensure data types are correct
- Review import class validation rules

### 12.2 Performance Optimization

**Database Queries**:
- Use eager loading for relationships: `with('relation')`
- Add indexes on frequently queried columns
- Use query builder for complex queries
- Implement pagination for large datasets

**Caching**:
- Cache parameter values (rarely change)
- Cache select options
- Use Redis for session storage
- Cache compiled views

**File Storage**:
- Use queue for large exports
- Implement chunked file processing
- Clean up old export files regularly
- Use CDN for static assets

---

## 13. Maintenance Tasks

### 13.1 Regular Maintenance

**Daily**:
- Monitor error logs: `storage/logs/laravel.log`
- Check disk space for exports
- Verify backup completion

**Weekly**:
- Clean up old export files
- Review failed jobs queue
- Check database performance

**Monthly**:
- Update dependencies: `composer update`
- Review and archive old invoices
- Database optimization: `php artisan optimize`
- Clear old logs

### 13.2 Database Maintenance

```bash
# Backup database
mysqldump -u root -p accountant_db > backup.sql

# Optimize tables
php artisan db:optimize

# Clear old soft-deleted records (if needed)
# Be careful with this - data will be permanently deleted
```

### 13.3 Log Management

```bash
# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Optimize for production
php artisan optimize
```


---

## 14. Testing

### 14.1 Testing Structure

**Location**: `tests/`

```
tests/
├── Feature/          # Integration tests
├── Unit/            # Unit tests
├── CreatesApplication.php
└── TestCase.php
```

### 14.2 Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ClientTest.php

# Run with coverage
php artisan test --coverage

# Run specific test method
php artisan test --filter testClientCreation
```

### 14.3 Manual Testing Checklist

**Authentication**:
- [ ] Login with valid credentials
- [ ] Login with invalid credentials
- [ ] Token expiration handling
- [ ] Logout functionality

**Client Management**:
- [ ] Create client with all fields
- [ ] Update client information
- [ ] Delete client (soft delete)
- [ ] Add/edit/delete addresses
- [ ] Add/edit/delete bank accounts
- [ ] Add/edit/delete contacts
- [ ] Configure service discounts

**Task Management**:
- [ ] Create task
- [ ] Assign task to user
- [ ] Log time (including > 24 hours)
- [ ] Change task status
- [ ] Complete task
- [ ] View task history

**Invoice Generation**:
- [ ] Create manual invoice
- [ ] Add tasks to invoice
- [ ] Apply discounts
- [ ] Generate PDF
- [ ] Generate XML (FatturaPA)
- [ ] Verify XML numbering sequence
- [ ] Send invoice via email
- [ ] Mark invoice as paid

**Recurring Invoices**:
- [ ] Generate single recurring invoice
- [ ] Generate bulk recurring invoices
- [ ] Verify service inclusion
- [ ] Verify client-specific pricing
- [ ] Verify date ranges

**Payment Installments**:
- [ ] Create installment plan
- [ ] Calculate installment schedule
- [ ] Create sub-installments
- [ ] Invoice installments
- [ ] Track payment status

---

## 15. Deployment Guide

### 15.1 Production Deployment Checklist

**Pre-Deployment**:
- [ ] Set `APP_ENV=production` in .env
- [ ] Set `APP_DEBUG=false` in .env
- [ ] Generate application key: `php artisan key:generate`
- [ ] Generate JWT secret: `php artisan jwt:secret`
- [ ] Configure database credentials
- [ ] Configure mail settings
- [ ] Set proper file permissions

**Deployment Steps**:
1. Clone repository to server
2. Install dependencies: `composer install --optimize-autoloader --no-dev`
3. Copy and configure .env file
4. Run migrations: `php artisan migrate --force`
5. Link storage: `php artisan storage:link`
6. Optimize application: `php artisan optimize`
7. Set permissions:
   ```bash
   chmod -R 775 storage
   chmod -R 775 bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

**Web Server Configuration (Nginx)**:
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/project/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Post-Deployment**:
- [ ] Test API endpoints
- [ ] Verify database connection
- [ ] Test file uploads
- [ ] Test PDF generation
- [ ] Test XML generation
- [ ] Test email sending
- [ ] Monitor error logs
- [ ] Set up automated backups
- [ ] Configure SSL certificate


### 15.2 Environment-Specific Configuration

**Development**:
```env
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug
```

**Staging**:
```env
APP_ENV=staging
APP_DEBUG=true
LOG_LEVEL=info
```

**Production**:
```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
```

---

## 16. API Response Formats

### 16.1 Success Response

**Single Resource**:
```json
{
  "data": {
    "id": 1,
    "name": "Client Name",
    "email": "client@example.com",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Collection**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Client 1"
    },
    {
      "id": 2,
      "name": "Client 2"
    }
  ],
  "links": {
    "first": "http://api.example.com/clients?page=1",
    "last": "http://api.example.com/clients?page=10",
    "prev": null,
    "next": "http://api.example.com/clients?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

### 16.2 Error Response

**Validation Error (422)**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

**Authentication Error (401)**:
```json
{
  "message": "Unauthenticated."
}
```

**Authorization Error (403)**:
```json
{
  "message": "This action is unauthorized."
}
```

**Not Found Error (404)**:
```json
{
  "message": "Resource not found."
}
```

**Server Error (500)**:
```json
{
  "message": "Server Error"
}
```

---

## 17. Glossary

**ABI**: Associazione Bancaria Italiana - Italian bank identification code

**CAB**: Codice di Avviamento Bancario - Italian branch identification code

**CF**: Codice Fiscale - Italian tax identification code for individuals

**FatturaPA**: Fattura Pubblica Amministrazione - Italian electronic invoice format for public administration

**IBAN**: International Bank Account Number

**IVA**: Imposta sul Valore Aggiunto - Italian Value Added Tax (VAT)

**JWT**: JSON Web Token - Authentication token format

**MP01, MP05, MP12**: Italian payment method codes (MP01=Cash, MP05=Bank Transfer, MP12=Direct Debit)

**Partita IVA**: Italian VAT number for businesses

**Ragione Sociale**: Company name / Business name

**SDI**: Sistema di Interscambio - Italian electronic invoice exchange system

**Soft Delete**: Database record marked as deleted but not physically removed

**TD01**: Italian invoice document type code (standard invoice)

---

## 18. Support and Contact

### 18.1 Technical Support

For technical issues or questions:
- Review this documentation
- Check error logs in `storage/logs/`
- Review Laravel documentation: https://laravel.com/docs
- Check package documentation for specific features

### 18.2 System Information

**Application**: Accountant Management System
**Framework**: Laravel 10.x
**PHP Version**: 8.1+
**Database**: MySQL/MariaDB
**Company**: ELABORAZIONI SRL
**Location**: Via Stazione 9/B, 26013 Crema (CR), Italy
**VAT**: 00987920196

---

## 19. Appendix

### 19.1 Database Schema Diagram

```
users
├── id
├── name
├── email
├── password
└── status

clients
├── id
├── ragione_sociale
├── iva
├── cf
├── payment_type_id → parameter_values
├── has_recurring_invoice
└── ...

client_addresses
├── id
├── client_id → clients
├── address
├── city
└── cap

client_bank_accounts
├── id
├── client_id → clients
├── bank_id → parameter_values
├── iban
├── abi
├── cab
└── is_main

service_categories
├── id
├── name
├── code
├── price
└── add_to_invoice

tasks
├── id
├── client_id → clients
├── user_id → users
├── service_category_id → service_categories
├── invoice_id → invoices
├── status
└── ...

task_time_logs
├── id
├── task_id → tasks
├── start_time
├── end_time
└── total_time

invoices
├── id
├── client_id → clients
├── number
├── invoice_xml_number
├── pay_status
└── ...

invoice_details
├── id
├── invoice_id → invoices
├── invoiceable_id (polymorphic)
├── invoiceable_type (polymorphic)
├── price
└── description

parameter_values
├── id
├── parameter_value
├── parameter_order
├── code
└── description
```


### 19.2 Common Parameter Values

**Payment Types (parameter_order = 1)**:
- MP01: Cash (Contanti)
- MP02: Check (Assegno)
- MP03: Bank check (Assegno circolare)
- MP04: Cash at treasury (Contanti presso Tesoreria)
- MP05: Bank transfer (Bonifico)
- MP06: Promissory note (Vaglia cambiario)
- MP07: Bank payment slip (Bollettino bancario)
- MP08: Payment card (Carta di pagamento)
- MP09: RID (Direct debit)
- MP10: RID utilities (Utenze)
- MP11: RID fast (Veloce)
- MP12: RIBA (Direct debit)
- MP13: MAV (Payment slip)
- MP14: Tax receipt (Quietanza erario)
- MP15: Special procedure (Giroconto su conti di contabilità speciale)
- MP16: Direct debit (Domiciliazione bancaria)
- MP17: Direct debit postal (Domiciliazione postale)
- MP18: Postal bulletin (Bollettino di c/c postale)
- MP19: SEPA Direct Debit (Addebito diretto SEPA)
- MP20: SEPA Direct Debit CORE (Addebito diretto SEPA CORE)
- MP21: SEPA Direct Debit B2B (Addebito diretto SEPA B2B)
- MP22: Deduction (Trattenuta su somme già riscosse)
- MP23: PagoPA

**Payment Steps (parameter_order = 2)**:
- 1: Single payment
- 2: Two installments
- 3: Three installments
- 4: Four installments
- 6: Six installments (bi-monthly)
- 12: Twelve installments (monthly)

**Service Types (parameter_order = 3)**:
- Consulting services
- Accounting services
- Tax services
- Payroll services
- Administrative services

**Bank Accounts (parameter_order = 7)**:
- Company bank accounts for receiving payments
- Includes IBAN, bank name, ABI, CAB

**Invoice Numbering (parameter_order = 13)**:
- Current invoice XML number
- Format: "1/57" (prefix/sequence)
- Auto-incremented for each invoice

### 19.3 File Storage Locations

**Exported Invoices**:
- Path: `storage/app/exportedInvoices/`
- Format: `{VAT_NUMBER}_{PADDED_NUMBER}.xml`
- Example: `00987920196_00057.xml`

**Uploaded Files**:
- Path: `storage/app/public/uploads/`
- Accessible via: `public/storage/uploads/`

**Logs**:
- Path: `storage/logs/laravel.log`
- Rotation: Daily

**Cache**:
- Path: `storage/framework/cache/`

**Sessions**:
- Path: `storage/framework/sessions/`

**Views**:
- Path: `storage/framework/views/` (compiled Blade templates)

---

## 20. Conclusion

This Accountant Management System is a comprehensive solution for managing accounting operations for an Italian accounting firm. It provides:

- Complete client relationship management
- Time tracking and task management
- Automated invoice generation
- Italian electronic invoicing (FatturaPA) compliance
- Payment installment management
- Recurring invoice automation
- Multiple export formats (PDF, CSV, XML)
- Role-based access control
- Audit trail for all operations

The system is built on Laravel 10 with a clean, layered architecture that separates concerns and makes the codebase maintainable and extensible. The use of service layers, form requests, and API resources ensures that business logic is properly encapsulated and the API responses are consistent.

Key strengths of the system:
- **Compliance**: Full support for Italian electronic invoicing standards
- **Flexibility**: Configurable parameters and client-specific pricing
- **Automation**: Recurring invoices and installment calculations
- **Accuracy**: Support for time tracking exceeding 24 hours
- **Security**: JWT authentication and role-based permissions
- **Maintainability**: Clean code structure with clear separation of concerns

This documentation provides a complete reference for developers working with the system, covering everything from installation to deployment, from basic CRUD operations to complex business logic like invoice generation and payment installment calculations.

---

**Document Version**: 1.0
**Last Updated**: 2024
**Author**: Technical Documentation Team
**Status**: Complete

---

*End of Documentation*
