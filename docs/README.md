# Laravel Accounting ERP System - Documentation Index

## Overview

This documentation provides comprehensive technical information about the Laravel-based accounting ERP system. The system is designed for Italian accounting firms and includes features for client management, task tracking, invoicing, time logging, and Italian electronic invoicing (FatturaPA) support.

## System Architecture

- **Backend**: PHP 8.x with Laravel Framework
- **Database**: MySQL/MariaDB
- **Authentication**: JWT (JSON Web Tokens)
- **Authorization**: Spatie Laravel Permission
- **File Processing**: Laravel Excel (Maatwebsite)
- **PDF Generation**: DomPDF
- **Email**: Laravel Mail with Mailable classes

## Module Documentation

### Core Modules

1. **[Authentication Module](MODULE_AUTHENTICATION.md)**
   - JWT-based authentication
   - Login and registration
   - Token management
   - Password reset
   - User profile management

2. **[User Management Module](MODULE_USER_MANAGEMENT.md)**
   - User CRUD operations
   - Role and permission management (Spatie)
   - Avatar upload
   - User status management
   - Access control

3. **[Client Management Module](MODULE_CLIENT_MANAGEMENT.md)**
   - Client CRUD operations
   - Bank account management
   - Address management
   - Contact management
   - Service discounts
   - Payment installments
   - 6 related database tables
   - 15+ controllers

### Business Operations

4. **[Task Management Module](MODULE_TASK_MANAGEMENT.md)**
   - Task CRUD operations
   - Time tracking (supports > 24 hours)
   - Time log management
   - Task status workflow
   - User assignment
   - Service category integration

5. **[Invoice Module](MODULE_INVOICE.md)**
   - Invoice creation from tasks
   - Invoice details management
   - XML export (FatturaPA format)
   - PDF generation
   - Payment tracking
   - Recurring invoices
   - Italian electronic invoicing

6. **[Service Category Module](MODULE_SERVICE_CATEGORY.md)**
   - Service category CRUD
   - Pricing management
   - Extra costs (stamps)
   - Service codes
   - Invoice integration

### Configuration & Data

7. **[Parameters Module](MODULE_PARAMETERS.md)**
   - Parameter values system
   - Dropdown configuration
   - Bank names
   - Holidays
   - Payment types
   - Invoice XML numbering
   - System configuration

8. **[Select/Dropdown Module](MODULE_SELECT_DROPDOWN.md)**
   - Centralized dropdown service
   - Dynamic data loading
   - Multiple entity support
   - Batch loading
   - Filtered data support

### Reporting & Analytics

9. **[Reporting Module](MODULE_REPORTING.md)**
   - Dashboard statistics
   - Invoice exports (XML/PDF)
   - Task exports (Excel)
   - Client payment exports
   - Italian electronic invoicing (FatturaPA)
   - Analytics and metrics

### Data Management

10. **[Import/Export Module](MODULE_IMPORT_EXPORT.md)**
    - Client import from Excel
    - Service category import
    - Bank account import
    - Task export to Excel
    - Invoice export (XML/PDF)
    - Client payment export

11. **[Email Module](MODULE_EMAIL.md)**
    - Custom email composition
    - Multiple attachments
    - Invoice delivery
    - PDF extraction and OCR
    - Email templates
    - Automated client notifications

## Quick Start Guide

### For New Developers

1. Start with [Authentication Module](MODULE_AUTHENTICATION.md) to understand user access
2. Review [Client Management Module](MODULE_CLIENT_MANAGEMENT.md) for core business entities
3. Study [Task Management Module](MODULE_TASK_MANAGEMENT.md) for workflow understanding
4. Learn [Invoice Module](MODULE_INVOICE.md) for billing operations
5. Explore [Reporting Module](MODULE_REPORTING.md) for data export and analytics

### For System Administrators

1. [User Management Module](MODULE_USER_MANAGEMENT.md) - User and role setup
2. [Parameters Module](MODULE_PARAMETERS.md) - System configuration
3. [Import/Export Module](MODULE_IMPORT_EXPORT.md) - Data migration

### For API Integration

1. [Authentication Module](MODULE_AUTHENTICATION.md) - API authentication
2. [Select/Dropdown Module](MODULE_SELECT_DROPDOWN.md) - Dropdown data
3. Each module's API Endpoints section

## Key Features

### Italian Electronic Invoicing (FatturaPA)
- Full FatturaPA 1.2 format support
- XML generation with validation
- Accented character removal
- Sequential invoice numbering
- Multiple payment methods (MP05, MP12)
- Passepartout integration
- See: [Invoice Module](MODULE_INVOICE.md) and [Reporting Module](MODULE_REPORTING.md)

### Time Tracking
- Support for time > 24 hours (e.g., 39:18:23)
- Manual time entry
- Time log management
- Excel export with proper formatting
- See: [Task Management Module](MODULE_TASK_MANAGEMENT.md)

### Multi-Entity Management
- Clients with multiple addresses, contacts, bank accounts
- Service discounts per client
- Payment installments with sub-installments
- See: [Client Management Module](MODULE_CLIENT_MANAGEMENT.md)

### Comprehensive Reporting
- Dashboard statistics
- Task exports with filters
- Invoice exports (XML/PDF)
- Client payment reports
- See: [Reporting Module](MODULE_REPORTING.md)

## API Documentation

All modules include:
- API endpoint specifications
- Request/response examples
- JavaScript integration examples
- cURL testing examples
- Validation rules
- Error handling

## Database Schema

Each module documentation includes:
- Database table structures
- Relationships
- Indexes
- Migrations
- Seeders

## Testing

Each module includes:
- Manual testing checklists
- API testing examples
- Database query examples
- Troubleshooting guides

## Best Practices

Each module documents:
- Security considerations
- Performance optimization
- Error handling
- Code organization
- API usage patterns

## Technology Stack

- **Framework**: Laravel 10.x
- **PHP**: 8.1+
- **Database**: MySQL 8.0+
- **Authentication**: JWT (tymon/jwt-auth)
- **Permissions**: Spatie Laravel Permission
- **Excel**: Laravel Excel (Maatwebsite)
- **PDF**: DomPDF (barryvdh/laravel-dompdf)
- **XML**: SimpleXML, DOMDocument

## Environment Configuration

Key environment variables:
```
APP_URL=https://accountant-api.testingelmo.com
DB_CONNECTION=mysql
JWT_SECRET=your-secret-key
MAIL_MAILER=smtp
```

See individual modules for specific configuration requirements.

## Support and Maintenance

For questions or issues:
1. Check the relevant module documentation
2. Review the Troubleshooting section
3. Check database queries for debugging
4. Review API testing examples

## Future Enhancements

Each module includes a "Future Enhancements" section with:
- Planned features
- Technical improvements
- Performance optimizations
- Integration possibilities

## Contributing

When adding new features:
1. Follow existing code patterns
2. Update relevant module documentation
3. Add API examples
4. Include testing guidelines
5. Document database changes

## Version History

- **v1.0** - Initial documentation (March 2026)
  - 11 complete module documentations
  - API specifications
  - Testing guidelines
  - Best practices

---

**Last Updated**: March 5, 2026
**Documentation Version**: 1.0
**System Version**: Laravel 10.x
