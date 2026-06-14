# API Endpoints Correction Reference

## Overview
All API endpoints in the documentation use `/api/private/` but the actual routes use `/api/v1/`. This document lists all correct endpoints from `routes/api.php`.

## Correct API Endpoint Structure

**Base URL**: `https://accountant-api.testingelmo.com/api/v1/`

---

## Authentication Module

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/auth/login` | POST | User login |
| `/api/v1/auth/logout` | POST | User logout |

---

## User Management Module

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/users` | GET | List all users |
| `/api/v1/users/create` | POST | Create new user |
| `/api/v1/users/edit` | GET | Get user for editing |
| `/api/v1/users/update` | PUT | Update user |
| `/api/v1/users/delete` | DELETE | Delete user |
| `/api/v1/users/change-status` | PUT | Change user status |

---

## Client Management Module

### Clients
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/clients` | GET | List all clients |
| `/api/v1/clients/create` | POST | Create new client |
| `/api/v1/clients/edit` | GET | Get client for editing |
| `/api/v1/clients/update` | PUT | Update client |
| `/api/v1/clients/delete` | DELETE | Delete client |

### Client Addresses
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/client-addresses` | GET | List client addresses |
| `/api/v1/client-addresses/create` | POST | Create address |
| `/api/v1/client-addresses/edit` | GET | Get address for editing |
| `/api/v1/client-addresses/update` | PUT | Update address |
| `/api/v1/client-addresses/delete` | DELETE | Delete address |

### Client Contacts
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/client-contacts` | GET | List client contacts |
| `/api/v1/client-contacts/create` | POST | Create contact |
| `/api/v1/client-contacts/edit` | GET | Get contact for editing |
| `/api/v1/client-contacts/update` | PUT | Update contact |
| `/api/v1/client-contacts/delete` | DELETE | Delete contact |

### Client Bank Accounts
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/client-bank-accounts` | GET | List bank accounts |
| `/api/v1/client-bank-accounts/create` | POST | Create bank account |
| `/api/v1/client-bank-accounts/edit` | GET | Get bank account for editing |
| `/api/v1/client-bank-accounts/update` | PUT | Update bank account |
| `/api/v1/client-bank-accounts/delete` | DELETE | Delete bank account |

### Client Pay Installments
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/client-pay-installments` | GET | List installments |
| `/api/v1/client-pay-installments/create` | POST | Create installment |
| `/api/v1/client-pay-installments/edit` | GET | Get installment for editing |
| `/api/v1/client-pay-installments/update` | PUT | Update installment |
| `/api/v1/client-pay-installments/delete` | DELETE | Delete installment |

### Client Pay Installment Sub Data
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/client-pay-installment-sub-data` | GET | List sub-installments |
| `/api/v1/client-pay-installment-sub-data/create` | POST | Create sub-installment |
| `/api/v1/client-pay-installment-sub-data/edit` | GET | Get sub-installment for editing |
| `/api/v1/client-pay-installment-sub-data/update` | PUT | Update sub-installment |
| `/api/v1/client-pay-installment-sub-data/delete` | PUT | Delete sub-installment |

### Client Service Discounts
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/client-service-discounts` | GET | List service discounts |
| `/api/v1/client-service-discounts/create` | POST | Create discount |
| `/api/v1/client-service-discounts/edit` | GET | Get discount for editing |
| `/api/v1/client-service-discounts/update` | PUT | Update discount |
| `/api/v1/client-service-discounts/delete` | DELETE | Delete discount |
| `/api/v1/client-service-discounts/changeShow` | POST | Toggle discount visibility |

### Client Utilities
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/client-pay-installment-divider` | GET | Get installment divider |
| `/api/v1/client-payment-type` | GET | Get payment types |
| `/api/v1/client-payment-period` | GET | Get payment periods |
| `/api/v1/installment-end-at` | GET | Get installment end date |
| `/api/v1/client-email/edit` | GET | Get client email |

---

## Task Management Module

### Tasks
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/tasks` | GET | List all tasks |
| `/api/v1/tasks/create` | POST | Create new task |
| `/api/v1/tasks/edit` | GET | Get task for editing |
| `/api/v1/tasks/update` | PUT | Update task |
| `/api/v1/tasks/delete` | DELETE | Delete task |
| `/api/v1/tasks/change-status` | PUT | Change task status |

### Admin Tasks
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/admin-tasks` | GET | List all tasks (admin view) |

### Task Time Logs
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/task-time-logs` | GET | List time logs |
| `/api/v1/task-time-logs/create` | POST | Create time log |
| `/api/v1/task-time-logs/edit` | GET | Get time log for editing |
| `/api/v1/task-time-logs/update` | PUT | Update time log |
| `/api/v1/task-time-logs/delete` | DELETE | Delete time log |
| `/api/v1/task-time-logs/change-time` | PUT | Change time log time |

### Active Tasks
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/user-active-tasks` | GET | Get user's active tasks |
| `/api/v1/user-active-tasks/update` | PUT | Update active task |

---

## Invoice Module

### Invoices
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/invoices` | GET | List all invoices |
| `/api/v1/invoices/create` | POST | Create invoices from tasks |
| `/api/v1/invoices/edit` | GET | Get invoice for editing |
| `/api/v1/invoices/update` | PUT | Update invoice |
| `/api/v1/invoices/add-tasks` | POST | Add tasks to invoice |
| `/api/v1/invoices/generate-xml-number` | POST | Generate XML number |
| `/api/v1/invoices/pay-invoice` | PUT | Mark invoice as paid |

### Invoice Details
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/invoice-details` | GET | List invoice details |
| `/api/v1/invoice-details/create` | POST | Create invoice detail |
| `/api/v1/invoice-details/edit` | GET | Get invoice detail for editing |
| `/api/v1/invoice-details/update` | PUT | Update invoice detail |
| `/api/v1/invoice-details/delete` | DELETE | Delete invoice detail |

### Recurring Invoices
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/recurring-invoices/create` | POST | Create recurring invoice |
| `/api/v1/clients/recurring-all-invoices/create` | POST | Create recurring invoices for all clients |

---

## Service Category Module

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/service-categories` | GET | List all service categories |
| `/api/v1/service-categories/create` | POST | Create service category |
| `/api/v1/service-categories/edit` | GET | Get service category for editing |
| `/api/v1/service-categories/update` | PUT | Update service category |
| `/api/v1/service-categories/delete` | DELETE | Delete service category |

---

## Parameters Module

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/parameters` | GET | List parameter values |
| `/api/v1/parameters/create` | POST | Create parameter value |
| `/api/v1/parameters/edit` | GET | Get parameter value for editing |
| `/api/v1/parameters/update` | PUT | Update parameter value |
| `/api/v1/parameters/delete` | DELETE | Delete parameter value |

---

## Reporting Module

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/reports` | GET | Get dashboard statistics |
| `/api/v1/export-invoice-report` | GET | Export invoice (XML/PDF/CSV) |
| `/api/v1/export-client-payment` | GET | Export client payments |
| `/api/v1/admin-ticket-export` | GET | Export tasks to Excel |

---

## Select/Dropdown Module

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/selects` | GET | Get multiple dropdown data |
| `/api/v1/selects/invoices` | GET | Get invoices for multiple clients |

---

## Import/Export Module

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/import-clients` | POST | Import clients from Excel |
| `/api/v1/import-service-categories` | POST | Import service categories |
| `/api/v1/import-client-bank-accounts` | POST | Import client bank accounts |

---

## Email Module

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/send-invoice-email` | POST | Send custom email with attachments |
| `/api/v1/send-uploaded-invoice` | POST | Extract and send invoice PDFs |
| `/api/v1/image-to-excel` | POST | Convert image to Excel |

---

## HTTP Method Corrections

### Common Patterns:
- **List/Get**: `GET`
- **Create**: `POST`
- **Update**: `PUT` (not POST)
- **Delete**: `DELETE` (not POST)
- **Status Change**: `PUT` (not POST)

### Specific Corrections Needed:
1. All `/update` endpoints should use `PUT` method
2. All `/delete` endpoints should use `DELETE` method
3. All `/change-status` endpoints should use `PUT` method
4. Exception: `/client-pay-installment-sub-data/delete` uses `PUT` (check if this is intentional)

---

## Documentation Files to Update

1. ✅ **MODULE_USER_MANAGEMENT.md** - UPDATED
2. ❌ **MODULE_CLIENT_MANAGEMENT.md** - Needs update
3. ❌ **MODULE_TASK_MANAGEMENT.md** - Needs update
4. ❌ **MODULE_INVOICE.md** - Needs update
5. ❌ **MODULE_SERVICE_CATEGORY.md** - Needs update
6. ✅ **MODULE_PARAMETERS.md** - Already uses v1
7. ✅ **MODULE_REPORTING.md** - Already updated
8. ❌ **MODULE_SELECT_DROPDOWN.md** - Needs update
9. ❌ **MODULE_IMPORT_EXPORT.md** - Needs update
10. ❌ **MODULE_EMAIL.md** - Needs update

---

## Quick Find & Replace Guide

### Global Replacements:
```
/api/private/ → /api/v1/
```

### Method-Specific Replacements:
```
POST /api/v1/*/update → PUT /api/v1/*/update
POST /api/v1/*/delete → DELETE /api/v1/*/delete
POST /api/v1/*/change-status → PUT /api/v1/*/change-status
```

### JavaScript Examples:
```javascript
// Old
fetch('/api/private/users/update', { method: 'POST' })

// New
fetch('/api/v1/users/update', { method: 'PUT' })
```

### cURL Examples:
```bash
# Old
curl -X POST https://accountant-api.testingelmo.com/api/private/users/update

# New
curl -X PUT https://accountant-api.testingelmo.com/api/v1/users/update
```

---

## Notes

- All routes are prefixed with `v1/` for API versioning
- Authentication endpoints use `/api/v1/auth/` prefix
- Most CRUD operations follow RESTful conventions
- Some endpoints have custom actions (e.g., `changeShow`, `add-tasks`)
- Export endpoints use GET method with query parameters
- Import endpoints use POST method with file uploads

---

**Last Updated**: March 5, 2026
**Based on**: `routes/api.php`
