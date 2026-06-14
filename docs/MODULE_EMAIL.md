# Email Module Documentation

## Overview

The Email Module provides functionality for sending emails with attachments, specifically designed for invoice delivery to clients. It supports custom email templates, multiple file attachments, and automated invoice extraction and delivery. The module integrates with Laravel's mail system and uses Mailable classes for structured email composition.

## Module Location

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           └── Private/
│               └── Invoice/
│                   ├── SendEmailController.php
│                   └── SendInvoiceController.php
├── Mail/
│   └── InvoiceEmail.php
└── resources/
    └── views/
        └── send_invoice_email.blade.php
```

## Features

- Custom email composition with subject and body
- Multiple file attachments support
- Invoice PDF extraction from uploaded files
- Automated client email delivery
- Temporary file storage and cleanup
- Transaction-based email sending
- HTML email templates
- File type validation
- Integration with external OCR service

## Controllers

### 1. SendEmailController (app/Http/Controllers/Api/Private/Invoice/SendEmailController.php)

Handles custom email sending with attachments.

#### Methods:

**index(Request $request)**
- Sends custom email with attachments
- Parameters:
  - `email` (required): Recipient email address
  - `subject` (required): Email subject (max 255 chars)
  - `content` (required): Email body content
  - `attachments` (optional): Array of files
- Validation:
  - email: required, valid email format
  - attachments: jpg, jpeg, png, pdf, doc, docx, csv
- Uses database transactions
- Automatically deletes attachments after sending
- Returns: Success/error message

#### Process Flow:

1. Validate request data
2. Begin database transaction
3. Store uploaded files temporarily
4. Send email with attachments
5. Commit transaction
6. Delete temporary files
7. Return response

### 2. SendInvoiceController (app/Http/Controllers/Api/Private/Invoice/SendInvoiceController.php)

Handles automated invoice extraction and email delivery.

#### Methods:

**index(Request $request)**
- Extracts fiscal code from PDF invoices
- Sends invoices to matched clients
- Parameters:
  - `files` (required): Array of PDF files
- Validation:
  - files: required, PDF format, max 10MB each
- Uses external OCR service (https://safa.masar-soft.com/api/v1/read-cf)
- Returns: Processing results for each file

#### Process Flow:

1. Validate uploaded PDF files
2. Store files temporarily
3. Send files to external OCR service
4. Receive fiscal code extraction results
5. Match clients by fiscal code
6. Send invoice to matched clients
7. Return processing results

**sendInvoiceToClient($email, $pdfPath)** (private)
- Sends invoice PDF to client email
- Parameters:
  - `email`: Client email address
  - `pdfPath`: Path to invoice PDF file
- Attaches PDF as "invoice.pdf"
- Uses simple text email body

## Mailable Classes

### InvoiceEmail (app/Mail/InvoiceEmail.php)

Mailable class for structured invoice emails.

#### Properties:

- `$header`: Email subject
- `$body`: Email body content
- `$files`: Array of file paths to attach

#### Methods:

**__construct($header, $body, $files = [])**
- Initializes email with subject, body, and attachments

**build()**
- Builds the email message
- Sets subject from header
- Uses `send_invoice_email` view
- Attaches all files from files array
- Returns: $this

## Email Templates

### send_invoice_email.blade.php (resources/views/send_invoice_email.blade.php)

Simple HTML email template.

#### Structure:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
</head>
<body>
    <h1>{{ $header }}</h1>
    <p>{{ $body }}</p>
</body>
</html>
```

#### Variables:

- `$header`: Email subject/heading
- `$body`: Email body content

## API Endpoints

### Send Custom Email

#### POST /api/v1/send-invoice-email
Send custom email with attachments.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body:**
```
email: recipient@example.com
subject: Invoice for March 2026
content: Please find attached your invoice for March 2026.
attachments[]: [file1.pdf]
attachments[]: [file2.pdf]
```

**Validation:**
- email: required, valid email format
- subject: required, string, max 255 characters
- content: required, string
- attachments.*: file, mimes:jpg,jpeg,png,pdf,doc,docx,csv

**Response (Success):**
```json
{
  "message": "Email Sent!"
}
```

**Response (Error):**
```json
{
  "error": "Failed to send email",
  "message": "Error details..."
}
```

### Send Uploaded Invoice

#### POST /api/v1/send-uploaded-invoice
Extract fiscal code from PDFs and send to matched clients.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body:**
```
files[]: [invoice1.pdf]
files[]: [invoice2.pdf]
```

**Validation:**
- files.*: required, mimes:pdf, max:10240 (10MB)

**Response (Success):**
```json
{
  "message": "All files processed successfully",
  "results": [
    {
      "file": "invoice1.pdf",
      "cf": "RSSMRA80A01H501Z",
      "status": "sent"
    },
    {
      "file": "invoice2.pdf",
      "cf": "VRDGPP85M15F205X",
      "status": "sent"
    }
  ]
}
```

**Response (Error):**
```json
{
  "error": "Connection error",
  "details": "Error message..."
}
```

## Usage Examples

### JavaScript/Frontend Integration

#### Sending Custom Email

```javascript
async function sendInvoiceEmail(email, subject, content, files) {
  const formData = new FormData();
  formData.append('email', email);
  formData.append('subject', subject);
  formData.append('content', content);
  
  // Add multiple attachments
  files.forEach((file, index) => {
    formData.append(`attachments[${index}]`, file);
  });
  
  const response = await fetch('/api/v1/send-invoice-email', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  
  const result = await response.json();
  
  if (response.ok) {
    console.log(result.message);
  } else {
    console.error(result.error, result.message);
  }
}

// Usage
const files = [
  document.getElementById('file1').files[0],
  document.getElementById('file2').files[0]
];

await sendInvoiceEmail(
  'client@example.com',
  'Invoice for March 2026',
  'Please find attached your invoice for March 2026.',
  files
);
```

#### Sending Uploaded Invoices

```javascript
async function sendUploadedInvoices(pdfFiles) {
  const formData = new FormData();
  
  pdfFiles.forEach((file, index) => {
    formData.append(`files[${index}]`, file);
  });
  
  const response = await fetch('/api/v1/send-uploaded-invoice', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  
  const result = await response.json();
  
  if (response.ok) {
    console.log(result.message);
    result.results.forEach(r => {
      console.log(`${r.file}: ${r.status} (CF: ${r.cf})`);
    });
  } else {
    console.error(result.error);
  }
}

// Usage
const pdfFiles = [
  document.getElementById('invoice1').files[0],
  document.getElementById('invoice2').files[0]
];

await sendUploadedInvoices(pdfFiles);
```

#### Email Form Component

```javascript
class EmailForm {
  constructor(formElement) {
    this.form = formElement;
    this.attachments = [];
    this.init();
  }
  
  init() {
    this.form.addEventListener('submit', (e) => {
      e.preventDefault();
      this.send();
    });
    
    const fileInput = this.form.querySelector('input[type="file"]');
    fileInput.addEventListener('change', (e) => {
      this.attachments = Array.from(e.target.files);
      this.updateAttachmentList();
    });
  }
  
  updateAttachmentList() {
    const list = this.form.querySelector('.attachment-list');
    list.innerHTML = '';
    
    this.attachments.forEach((file, index) => {
      const item = document.createElement('div');
      item.textContent = file.name;
      
      const removeBtn = document.createElement('button');
      removeBtn.textContent = 'Remove';
      removeBtn.onclick = () => this.removeAttachment(index);
      
      item.appendChild(removeBtn);
      list.appendChild(item);
    });
  }
  
  removeAttachment(index) {
    this.attachments.splice(index, 1);
    this.updateAttachmentList();
  }
  
  async send() {
    const email = this.form.querySelector('[name="email"]').value;
    const subject = this.form.querySelector('[name="subject"]').value;
    const content = this.form.querySelector('[name="content"]').value;
    
    try {
      await sendInvoiceEmail(email, subject, content, this.attachments);
      alert('Email sent successfully!');
      this.form.reset();
      this.attachments = [];
      this.updateAttachmentList();
    } catch (error) {
      alert('Failed to send email: ' + error.message);
    }
  }
}

// Usage
const emailForm = new EmailForm(document.getElementById('emailForm'));
```

#### Batch Invoice Sending

```javascript
class BatchInvoiceSender {
  constructor() {
    this.queue = [];
    this.results = [];
  }
  
  addToQueue(email, subject, content, files) {
    this.queue.push({ email, subject, content, files });
  }
  
  async processQueue() {
    for (const item of this.queue) {
      try {
        await sendInvoiceEmail(
          item.email,
          item.subject,
          item.content,
          item.files
        );
        
        this.results.push({
          email: item.email,
          status: 'success'
        });
      } catch (error) {
        this.results.push({
          email: item.email,
          status: 'failed',
          error: error.message
        });
      }
    }
    
    return this.results;
  }
  
  getResults() {
    return this.results;
  }
}

// Usage
const sender = new BatchInvoiceSender();

sender.addToQueue(
  'client1@example.com',
  'Invoice #123',
  'Your invoice for March',
  [file1]
);

sender.addToQueue(
  'client2@example.com',
  'Invoice #124',
  'Your invoice for March',
  [file2]
);

const results = await sender.processQueue();
console.log('Sent:', results.filter(r => r.status === 'success').length);
console.log('Failed:', results.filter(r => r.status === 'failed').length);
```

#### Progress Tracking

```javascript
async function sendEmailsWithProgress(emails) {
  const total = emails.length;
  let completed = 0;
  
  const progressBar = document.getElementById('progressBar');
  const progressText = document.getElementById('progressText');
  
  for (const emailData of emails) {
    try {
      await sendInvoiceEmail(
        emailData.email,
        emailData.subject,
        emailData.content,
        emailData.files
      );
      
      completed++;
      const progress = (completed / total) * 100;
      
      progressBar.style.width = `${progress}%`;
      progressText.textContent = `${completed} / ${total} emails sent`;
    } catch (error) {
      console.error(`Failed to send to ${emailData.email}:`, error);
    }
  }
  
  console.log('All emails processed');
}

// Usage
const emails = [
  {
    email: 'client1@example.com',
    subject: 'Invoice #123',
    content: 'Your invoice',
    files: [file1]
  },
  {
    email: 'client2@example.com',
    subject: 'Invoice #124',
    content: 'Your invoice',
    files: [file2]
  }
];

await sendEmailsWithProgress(emails);
```

## Business Logic

### Email Sending Process

1. **Validation**
   - Validate email address format
   - Validate subject and content
   - Validate file types and sizes

2. **File Storage**
   - Store attachments temporarily in `storage/app/public/attachments`
   - Use original file names
   - Get absolute file paths

3. **Email Composition**
   - Create InvoiceEmail mailable
   - Set subject and body
   - Attach files

4. **Sending**
   - Send email via Laravel Mail
   - Use configured mail driver (SMTP, etc.)

5. **Cleanup**
   - Delete temporary attachment files
   - Commit database transaction

### Invoice Extraction Process

1. **File Upload**
   - Validate PDF files
   - Store in `storage/app/public/uploadedInvoices`

2. **OCR Processing**
   - Send files to external OCR service
   - Service extracts fiscal code from PDF

3. **Client Matching**
   - Match fiscal code to client in database
   - Find client by `cf` field

4. **Email Delivery**
   - If client found: Send invoice to client email
   - If not found: Skip file

5. **Results**
   - Return processing results for each file
   - Include fiscal code and status

### Transaction Management

```php
DB::beginTransaction();
try {
    // Store files
    // Send email
    DB::commit();
    // Delete files
} catch (\Exception $e) {
    DB::rollBack();
    // Return error
}
```

## Testing

### Manual Testing Checklist

#### Custom Email Sending

- [ ] Send email with valid data
- [ ] Send email with single attachment
- [ ] Send email with multiple attachments
- [ ] Send email without attachments
- [ ] Test with different file types (PDF, JPG, DOC)
- [ ] Test with invalid email address
- [ ] Test with missing subject
- [ ] Test with missing content
- [ ] Test with file size > 10MB
- [ ] Verify email received
- [ ] Verify attachments received
- [ ] Verify temporary files deleted

#### Invoice Upload and Send

- [ ] Upload single PDF invoice
- [ ] Upload multiple PDF invoices
- [ ] Test with valid fiscal codes
- [ ] Test with invalid fiscal codes
- [ ] Test with non-existent clients
- [ ] Verify OCR service connection
- [ ] Verify email sent to matched clients
- [ ] Test with file size > 10MB
- [ ] Test with non-PDF files

### API Testing with cURL

#### Send Custom Email

```bash
curl -X POST https://accountant-api.testingelmo.com/api/v1/send-invoice-email \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "email=client@example.com" \
  -F "subject=Invoice for March 2026" \
  -F "content=Please find attached your invoice." \
  -F "attachments[]=@invoice.pdf" \
  -F "attachments[]=@receipt.pdf"
```

#### Send Uploaded Invoice

```bash
curl -X POST https://accountant-api.testingelmo.com/api/v1/send-uploaded-invoice \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "files[]=@invoice1.pdf" \
  -F "files[]=@invoice2.pdf"
```

## Troubleshooting

### Common Issues

#### Issue: Email not sent

**Cause:** Mail configuration incorrect or SMTP server unavailable.

**Solution:**
1. Check `.env` mail configuration:
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=your-email@gmail.com
   MAIL_FROM_NAME="${APP_NAME}"
   ```
2. Test SMTP connection
3. Check mail logs: `storage/logs/laravel.log`
4. Verify firewall allows SMTP port

#### Issue: Attachments not received

**Cause:** File path incorrect or file deleted before sending.

**Solution:**
1. Verify file exists before sending
2. Check file permissions
3. Verify absolute path is used
4. Check file size limits

#### Issue: OCR service fails

**Cause:** External service unavailable or network error.

**Solution:**
1. Check service URL: https://safa.masar-soft.com/api/v1/read-cf
2. Verify network connectivity
3. Check API credentials if required
4. Review service response in logs

#### Issue: Client not found by fiscal code

**Cause:** Fiscal code doesn't match or OCR extraction failed.

**Solution:**
1. Verify fiscal code in database:
   ```sql
   SELECT * FROM clients WHERE cf = 'RSSMRA80A01H501Z';
   ```
2. Check OCR extraction accuracy
3. Verify PDF quality
4. Manual verification of fiscal code

#### Issue: Temporary files not deleted

**Cause:** Exception thrown before cleanup or permission issues.

**Solution:**
1. Check file permissions
2. Verify cleanup code executes
3. Manually delete old files:
   ```bash
   rm storage/app/public/attachments/*
   ```
4. Implement scheduled cleanup task

### Database Queries for Debugging

#### Find clients by fiscal code

```sql
SELECT id, ragione_sociale, email, cf
FROM clients
WHERE cf = 'RSSMRA80A01H501Z';
```

#### Check recent email activity

```sql
-- If you have email logs table
SELECT * FROM email_logs
WHERE created_at > NOW() - INTERVAL 1 HOUR
ORDER BY created_at DESC;
```

### Performance Optimization

#### Email Sending

- Use queue jobs for bulk email sending
- Implement rate limiting
- Use email service with high throughput
- Cache email templates
- Optimize attachment sizes

#### File Processing

- Implement chunk upload for large files
- Use background jobs for OCR processing
- Implement file compression
- Clean up old files regularly

## Best Practices

### Email Composition

1. Use clear, concise subject lines
2. Include professional email body
3. Personalize email content
4. Include company branding
5. Add unsubscribe option for bulk emails

### File Attachments

1. Validate file types before upload
2. Limit file sizes (max 10MB per file)
3. Scan files for malware
4. Compress large files
5. Use descriptive file names

### Error Handling

1. Log all email sending attempts
2. Implement retry mechanism for failed sends
3. Notify admins of failures
4. Provide user-friendly error messages
5. Track email delivery status

### Security

1. Validate email addresses
2. Sanitize email content
3. Prevent email injection attacks
4. Implement rate limiting
5. Use secure SMTP connection (TLS/SSL)
6. Validate file types and sizes
7. Scan attachments for malware

### Performance

1. Use queue jobs for email sending
2. Implement batch processing
3. Optimize email templates
4. Use CDN for static assets
5. Implement caching where appropriate

## Related Modules

- [Invoice Module](MODULE_INVOICE.md) - Invoice data and management
- [Client Management Module](MODULE_CLIENT_MANAGEMENT.md) - Client email addresses
- [Reporting Module](MODULE_REPORTING.md) - Invoice PDF generation
- [Authentication Module](MODULE_AUTHENTICATION.md) - User authentication

## Future Enhancements

### Planned Features

1. **Email Templates**
   - Multiple email templates
   - Template customization
   - Dynamic content insertion
   - Template preview

2. **Scheduling**
   - Schedule email sending
   - Recurring email campaigns
   - Time zone support
   - Delivery time optimization

3. **Tracking**
   - Email open tracking
   - Link click tracking
   - Delivery status tracking
   - Bounce handling

4. **Bulk Operations**
   - Bulk email sending
   - Email list management
   - Segmentation
   - A/B testing

5. **Integration**
   - Third-party email services (SendGrid, Mailgun)
   - CRM integration
   - Marketing automation
   - Analytics integration

6. **Advanced Features**
   - Email signatures
   - CC/BCC support
   - Reply-to configuration
   - Email threading
   - Rich text editor

### Technical Improvements

1. Implement queue jobs for all email sending
2. Add comprehensive email logging
3. Implement email delivery tracking
4. Add unit tests for email functionality
5. Implement email template engine
6. Add monitoring and alerting
7. Optimize file storage and cleanup
8. Implement email validation service
9. Add spam prevention measures
10. Implement email analytics dashboard
