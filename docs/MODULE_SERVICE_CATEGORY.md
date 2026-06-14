# Service Category Module Documentation

## Overview

The Service Category Module manages service types and their pricing. Service categories define the billable services offered to clients, including base prices, service codes for invoicing, and optional extra costs (like stamps). This module is central to task pricing and invoice generation.

## Module Location

```
app/
├── Models/
│   └── ServiceCategory/
│       └── ServiceCategory.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── Private/
│   │           └── ServiceCategory/
│   │               └── ServiceCategoryController.php
│   ├── Requests/
│   │   └── ServiceCategory/
│   │       ├── CreateServiceCategoryRequest.php
│   │       └── UpdateServiceCategoryRequest.php
│   └── Resources/
│       └── ServiceCategory/
│           ├── ServiceCategoryResource.php
│           └── AllServiceCategoryCollection.php
├── Services/
│   └── ServiceCategory/
│       └── ServiceCategoryService.php
├── Enums/
│   └── ServiceCategory/
│       └── ServiceCategoryAddToInvoiceStatus.php
└── Filters/
    └── ServiceCategory/
        └── FilterServiceCategory.php
```

## Database Schema

### service_categories Table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| name | VARCHAR(255) | Service category name (unique) |
| code | VARCHAR(255) | Service code for invoicing (e.g., WD01) |
| description | TEXT | Service description |
| price | DECIMAL(8,2) | Base price for this service |
| add_to_invoice | TINYINT | Include in invoice (1=ADD, 0=REMOVE) |
| service_type_id | BIGINT | FK to parameter_values (service type) |
| extra_is_pricable | BOOLEAN | Whether extra cost applies |
| extra_code | VARCHAR(255) | Extra cost code (e.g., stamp code) |
| extra_price | DECIMAL(8,2) | Extra cost amount |
| extra_price_description | TEXT | Extra cost description |
| created_by | BIGINT | User who created |
| updated_by | BIGINT | User who updated |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

## Enums

### ServiceCategoryAddToInvoiceStatus (app/Enums/ServiceCategory/ServiceCategoryAddToInvoiceStatus.php)

```php
enum ServiceCategoryAddToInvoiceStatus: int
{
    case ADD = 1;     // Include price in invoice
    case REMOVE = 0;  // Exclude price from invoice (free service)
}
```

This enum controls whether a service category's price is included when creating invoices. Services with REMOVE status have a price of 0 in invoices.

## Models

### ServiceCategory (app/Models/ServiceCategory/ServiceCategory.php)

Main service category model.

#### Fillable Fields:

```php
'name', 'code', 'description', 'price', 'add_to_invoice', 'service_type_id',
'extra_is_pricable', 'extra_code', 'extra_price', 'extra_price_description'
```

#### Casts:

```php
'add_to_invoice' => ServiceCategoryAddToInvoiceStatus::class
```

#### Relationships:

```php
serviceType()  // belongsTo ParameterValue (service type classification)
```

#### Methods:

**getPrice()**
- Returns the effective price for invoicing
- Returns 0 if add_to_invoice is REMOVE
- Returns actual price if add_to_invoice is ADD

```php
public function getPrice()
{
    if ($this->add_to_invoice == ServiceCategoryAddToInvoiceStatus::REMOVE) {
        return 0;
    }
    return $this->price;
}
```


## Controllers

### ServiceCategoryController (app/Http/Controllers/Api/Private/ServiceCategory/ServiceCategoryController.php)

Main controller for service category CRUD operations.

#### Methods:

**index(Request $request)**
- Permission: `all_service_categories`
- Returns: Paginated list of all service categories
- Supports search filtering by name
- Query Parameters:
  - `filter[search]`: Search by service name
  - `pageSize`: Items per page (default: 10)
- Response: `AllServiceCategoryCollection`

**create(CreateServiceCategoryRequest $request)**
- Permission: `create_service_category`
- Creates new service category
- Validates unique name
- Returns: Success message

**edit(Request $request)**
- Permission: `edit_service_category`
- Parameters: `serviceCategoryId`
- Returns: ServiceCategoryResource with category details

**update(UpdateServiceCategoryRequest $request)**
- Permission: `update_service_category`
- Updates service category
- Validates unique name (excluding current record)
- Returns: Success message

**delete(Request $request)**
- Permission: `delete_service_category`
- Parameters: `serviceCategoryId`
- Soft deletes the service category
- Returns: Success message

## Services

### ServiceCategoryService (app/Services/ServiceCategory/ServiceCategoryService.php)

Business logic for service category operations.

#### Methods:

**allServiceCategories()**
- Retrieves all service categories
- Supports search filtering
- Returns: Collection of ServiceCategory

**createServiceCategory(array $serviceCategoryData)**
- Creates new service category
- Parameters:
  - `name`: Service name (required, unique)
  - `code`: Service code (optional)
  - `description`: Description (optional)
  - `serviceTypeId`: Service type ID (optional)
  - `addToInvoice`: Include in invoice (required, 0 or 1)
  - `price`: Base price (required)
  - `extraIsPricable`: Has extra cost (optional, default: 0)
  - `extraCode`: Extra cost code (optional)
  - `extraPrice`: Extra cost amount (optional, default: 0)
  - `extraPriceDescription`: Extra cost description (optional)
- Returns: ServiceCategory model

**editServiceCategory(string $serviceCategoryId)**
- Retrieves service category by ID
- Returns: ServiceCategory model

**updateServiceCategory(array $serviceCategoryData)**
- Updates service category
- Same parameters as create plus `serviceCategoryId`
- Returns: ServiceCategory model

**deleteServiceCategory(string $serviceCategoryId)**
- Soft deletes service category

## Business Logic

### Service Pricing

Service categories define the base price for services. The actual price used in invoices depends on:

1. **add_to_invoice status**:
   - ADD (1): Use the defined price
   - REMOVE (0): Price is 0 (free service)

2. **Client-specific discounts/taxes**:
   - Applied on top of base price
   - Defined in ClientServiceDiscount
   - Can be percentage or fixed amount
   - Can be discount (decrease) or tax (increase)

3. **Extra costs**:
   - Optional additional charges (e.g., stamps)
   - Controlled by `extra_is_pricable` flag
   - Added to invoice if enabled

### Price Calculation Example

```php
// Get service category
$serviceCategory = ServiceCategory::find($serviceCategoryId);

// Base price
$basePrice = $serviceCategory->getPrice(); // Returns 0 if REMOVE, actual price if ADD

// Apply client discount/tax
$clientDiscount = ClientServiceDiscount::where('client_id', $clientId)
    ->whereRaw("FIND_IN_SET(?, service_category_ids)", [$serviceCategoryId])
    ->where('is_active', 1)
    ->first();

$finalPrice = $basePrice;

if ($clientDiscount) {
    $discountValue = $clientDiscount->discount;
    $isPercentage = $clientDiscount->type === 1;
    
    if ($clientDiscount->category === 1) { // TAX
        $finalPrice = $isPercentage
            ? $basePrice * (1 + $discountValue / 100)
            : $basePrice + $discountValue;
    } else { // DISCOUNT
        $finalPrice = $isPercentage
            ? $basePrice * (1 - $discountValue / 100)
            : max(0, $basePrice - $discountValue);
    }
}

// Add extra costs
$extraCost = 0;
if ($serviceCategory->extra_is_pricable) {
    $extraCost = $serviceCategory->extra_price;
}

$totalPrice = $finalPrice + $extraCost;
```

### Extra Costs (Stamps)

Extra costs are typically used for mandatory charges like revenue stamps (bollo) in Italian invoicing:

- `extra_is_pricable`: Enable/disable extra cost
- `extra_code`: Code for the extra cost (e.g., "BOLLO")
- `extra_price`: Amount (e.g., 2.00 EUR)
- `extra_price_description`: Description (e.g., "Marca da bollo")

These are added to invoices separately from the service price.

## API Endpoints

### Service Category Management

#### GET /api/v1/service-categories
Get all service categories with filtering.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
filter[search]  - Search by service name
pageSize        - Items per page (default: 10)
```

**Response:**
```json
{
  "data": [
    {
      "serviceCategoryId": 1,
      "name": "Web Development",
      "code": "WD01",
      "description": "Website development and maintenance",
      "addToInvoice": 1,
      "serviceTypeId": 5,
      "price": 500.00,
      "extraIsPricable": 1,
      "extraPrice": 2.00,
      "extraCode": "BOLLO",
      "extraPriceDescription": "Marca da bollo"
    },
    {
      "serviceCategoryId": 2,
      "name": "Consulting",
      "code": "CONS01",
      "description": "Business consulting services",
      "addToInvoice": 1,
      "serviceTypeId": 6,
      "price": 300.00,
      "extraIsPricable": 0,
      "extraPrice": 0,
      "extraCode": "",
      "extraPriceDescription": ""
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 15
  }
}
```

#### POST /api/v1/service-categories/create
Create new service category.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Web Development",
  "code": "WD01",
  "description": "Website development and maintenance",
  "addToInvoice": 1,
  "serviceTypeId": 5,
  "price": 500.00,
  "extraIsPricable": 1,
  "extraPrice": 2.00,
  "extraCode": "BOLLO",
  "extraPriceDescription": "Marca da bollo"
}
```

**Response:**
```json
{
  "message": "Created successfully"
}
```

#### GET /api/v1/service-categories/edit
Get service category details for editing.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
```
serviceCategoryId - Service category ID (required)
```

**Response:**
```json
{
  "serviceCategoryId": 1,
  "name": "Web Development",
  "code": "WD01",
  "description": "Website development and maintenance",
  "addToInvoice": 1,
  "serviceTypeId": 5,
  "price": 500.00,
  "extraIsPricable": 1,
  "extraPrice": 2.00,
  "extraCode": "BOLLO",
  "extraPriceDescription": "Marca da bollo"
}
```

#### PUT /api/v1/service-categories/update
Update service category.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "serviceCategoryId": 1,
  "name": "Web Development",
  "code": "WD01",
  "description": "Website development and maintenance - Updated",
  "addToInvoice": 1,
  "serviceTypeId": 5,
  "price": 550.00,
  "extraIsPricable": 1,
  "extraPrice": 2.00,
  "extraCode": "BOLLO",
  "extraPriceDescription": "Marca da bollo"
}
```

**Response:**
```json
{
  "message": "Updated successfully"
}
```

#### DELETE /api/v1/service-categories/delete
Delete service category (soft delete).

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "serviceCategoryId": 1
}
```

**Response:**
```json
{
  "message": "Deleted successfully"
}
```


## Validation Rules

### CreateServiceCategoryRequest

```php
'name' => 'required|unique:service_categories,name',
'code' => 'nullable|string',
'description' => 'nullable|string',
'addToInvoice' => 'required|enum:ServiceCategoryAddToInvoiceStatus',
'price' => 'required',
'serviceTypeId' => 'nullable',
'extraIsPricable' => 'nullable',
'extraCode' => 'nullable',
'extraPriceDescription' => 'nullable',
'extraPrice' => 'nullable'
```

### UpdateServiceCategoryRequest

```php
'serviceCategoryId' => 'required',
'name' => 'required|unique:service_categories,name,{serviceCategoryId}',
'code' => 'nullable|string',
'description' => 'nullable|string',
'addToInvoice' => 'required|enum:ServiceCategoryAddToInvoiceStatus',
'price' => 'required',
'serviceTypeId' => 'nullable',
'extraIsPricable' => 'nullable',
'extraCode' => 'nullable',
'extraPriceDescription' => 'nullable',
'extraPrice' => 'nullable'
```

## Usage Examples

### JavaScript/Frontend Integration

#### Fetching All Service Categories

```javascript
async function fetchServiceCategories(searchTerm = '', pageSize = 10) {
  const params = new URLSearchParams();
  
  if (searchTerm) params.append('filter[search]', searchTerm);
  params.append('pageSize', pageSize);
  
  const response = await fetch(`/api/v1/service-categories?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  const data = await response.json();
  return data;
}

// Usage
const categories = await fetchServiceCategories('Web', 20);
console.log(`Found ${categories.data.length} service categories`);
```

#### Creating a Service Category

```javascript
async function createServiceCategory(categoryData) {
  const response = await fetch('/api/v1/service-categories/create', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      name: categoryData.name,
      code: categoryData.code,
      description: categoryData.description,
      addToInvoice: categoryData.addToInvoice,
      serviceTypeId: categoryData.serviceTypeId,
      price: categoryData.price,
      extraIsPricable: categoryData.extraIsPricable,
      extraPrice: categoryData.extraPrice,
      extraCode: categoryData.extraCode,
      extraPriceDescription: categoryData.extraPriceDescription
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage
const newCategory = await createServiceCategory({
  name: 'Web Development',
  code: 'WD01',
  description: 'Website development and maintenance',
  addToInvoice: 1,
  serviceTypeId: 5,
  price: 500.00,
  extraIsPricable: 1,
  extraPrice: 2.00,
  extraCode: 'BOLLO',
  extraPriceDescription: 'Marca da bollo'
});

console.log('Service category created successfully');
```

#### Updating a Service Category

```javascript
async function updateServiceCategory(categoryData) {
  const response = await fetch('/api/v1/service-categories/update', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      serviceCategoryId: categoryData.serviceCategoryId,
      name: categoryData.name,
      code: categoryData.code,
      description: categoryData.description,
      addToInvoice: categoryData.addToInvoice,
      serviceTypeId: categoryData.serviceTypeId,
      price: categoryData.price,
      extraIsPricable: categoryData.extraIsPricable,
      extraPrice: categoryData.extraPrice,
      extraCode: categoryData.extraCode,
      extraPriceDescription: categoryData.extraPriceDescription
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage
await updateServiceCategory({
  serviceCategoryId: 1,
  name: 'Web Development',
  code: 'WD01',
  description: 'Website development and maintenance - Updated',
  addToInvoice: 1,
  serviceTypeId: 5,
  price: 550.00,
  extraIsPricable: 1,
  extraPrice: 2.00,
  extraCode: 'BOLLO',
  extraPriceDescription: 'Marca da bollo'
});
```

#### Deleting a Service Category

```javascript
async function deleteServiceCategory(serviceCategoryId) {
  const response = await fetch('/api/v1/service-categories/delete', {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      serviceCategoryId: serviceCategoryId
    })
  });
  
  const result = await response.json();
  return result;
}

// Usage
await deleteServiceCategory(1);
console.log('Service category deleted successfully');
```

#### Service Category Selector Component

```javascript
class ServiceCategorySelector {
  constructor(selectElement) {
    this.selectElement = selectElement;
    this.categories = [];
  }
  
  async loadCategories() {
    const response = await fetchServiceCategories('', 100);
    this.categories = response.data;
    this.render();
  }
  
  render() {
    this.selectElement.innerHTML = '<option value="">Select a service...</option>';
    
    this.categories.forEach(category => {
      const option = document.createElement('option');
      option.value = category.serviceCategoryId;
      option.textContent = `${category.name} - €${category.price}`;
      option.dataset.price = category.price;
      option.dataset.code = category.code;
      option.dataset.extraPrice = category.extraPrice;
      this.selectElement.appendChild(option);
    });
  }
  
  getSelectedCategory() {
    const selectedOption = this.selectElement.selectedOptions[0];
    if (!selectedOption || !selectedOption.value) return null;
    
    return {
      id: parseInt(selectedOption.value),
      name: selectedOption.textContent.split(' - ')[0],
      price: parseFloat(selectedOption.dataset.price),
      code: selectedOption.dataset.code,
      extraPrice: parseFloat(selectedOption.dataset.extraPrice)
    };
  }
}

// Usage
const selector = new ServiceCategorySelector(document.getElementById('service-select'));
await selector.loadCategories();

// Get selected category
const selected = selector.getSelectedCategory();
if (selected) {
  console.log(`Selected: ${selected.name} at €${selected.price}`);
}
```

#### Calculating Task Price with Service Category

```javascript
async function calculateTaskPrice(serviceCategoryId, clientId) {
  // Get service category
  const categoryResponse = await fetch(
    `/api/v1/service-categories/edit?serviceCategoryId=${serviceCategoryId}`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    }
  );
  const category = await categoryResponse.json();
  
  // Base price (0 if REMOVE, actual price if ADD)
  let basePrice = category.addToInvoice === 1 ? category.price : 0;
  
  // Get client discount (if any)
  const discountResponse = await fetch(
    `/api/private/client-service-discounts?clientId=${clientId}&serviceCategoryId=${serviceCategoryId}`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    }
  );
  
  let finalPrice = basePrice;
  
  if (discountResponse.ok) {
    const discount = await discountResponse.json();
    
    if (discount) {
      const discountValue = discount.discount;
      const isPercentage = discount.type === 1;
      
      if (discount.category === 1) { // TAX
        finalPrice = isPercentage
          ? basePrice * (1 + discountValue / 100)
          : basePrice + discountValue;
      } else { // DISCOUNT
        finalPrice = isPercentage
          ? basePrice * (1 - discountValue / 100)
          : Math.max(0, basePrice - discountValue);
      }
    }
  }
  
  // Add extra costs
  const extraCost = category.extraIsPricable ? category.extraPrice : 0;
  
  return {
    basePrice: basePrice,
    finalPrice: finalPrice,
    extraCost: extraCost,
    totalPrice: finalPrice + extraCost
  };
}

// Usage
const pricing = await calculateTaskPrice(1, 5);
console.log(`Base: €${pricing.basePrice}`);
console.log(`After discount: €${pricing.finalPrice}`);
console.log(`Extra costs: €${pricing.extraCost}`);
console.log(`Total: €${pricing.totalPrice}`);
```


## Permissions

The following permissions control access to service category features:

| Permission | Description |
|-----------|-------------|
| all_service_categories | View all service categories list |
| create_service_category | Create new service categories |
| edit_service_category | View service category details for editing |
| update_service_category | Update service category information |
| delete_service_category | Delete service categories |

## Testing

### Manual Testing Checklist

#### Service Category CRUD

- [ ] Create service category with all fields
- [ ] Create service category with minimal fields
- [ ] Verify unique name validation
- [ ] View service categories list
- [ ] Search service categories by name
- [ ] Edit service category
- [ ] Update service category
- [ ] Delete service category
- [ ] Verify soft delete

#### Pricing Configuration

- [ ] Create service with ADD status (price included)
- [ ] Create service with REMOVE status (price = 0)
- [ ] Verify getPrice() method returns correct value
- [ ] Test with extra costs enabled
- [ ] Test with extra costs disabled
- [ ] Verify extra cost fields

#### Integration Testing

- [ ] Create task with service category
- [ ] Verify task price matches service price
- [ ] Create invoice with service category
- [ ] Verify invoice includes service code
- [ ] Verify extra costs are added to invoice
- [ ] Test with client-specific discounts
- [ ] Test with client-specific taxes

### API Testing with cURL

#### Create Service Category

```bash
curl -X POST https://accountant-api.testingelmo.com/api/v1/service-categories/create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Web Development",
    "code": "WD01",
    "description": "Website development and maintenance",
    "addToInvoice": 1,
    "serviceTypeId": 5,
    "price": 500.00,
    "extraIsPricable": 1,
    "extraPrice": 2.00,
    "extraCode": "BOLLO",
    "extraPriceDescription": "Marca da bollo"
  }'
```

#### Get All Service Categories

```bash
curl -X GET "https://accountant-api.testingelmo.com/api/v1/service-categories?filter[search]=Web&pageSize=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Update Service Category

```bash
curl -X PUT https://accountant-api.testingelmo.com/api/v1/service-categories/update \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "serviceCategoryId": 1,
    "name": "Web Development",
    "code": "WD01",
    "description": "Website development and maintenance - Updated",
    "addToInvoice": 1,
    "serviceTypeId": 5,
    "price": 550.00,
    "extraIsPricable": 1,
    "extraPrice": 2.00,
    "extraCode": "BOLLO",
    "extraPriceDescription": "Marca da bollo"
  }'
```

#### Delete Service Category

```bash
curl -X DELETE https://accountant-api.testingelmo.com/api/v1/service-categories/delete \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "serviceCategoryId": 1
  }'
```

## Troubleshooting

### Common Issues

#### Issue: Duplicate service category name

**Cause:** Service category name must be unique.

**Solution:**
1. Check existing categories: `SELECT * FROM service_categories WHERE name = ?`
2. Use a different name or update the existing category
3. Consider soft-deleted records that may have the same name

#### Issue: Service price not appearing in invoice

**Cause:** Service category has `add_to_invoice = 0` (REMOVE status).

**Solution:**
1. Check service category: `SELECT * FROM service_categories WHERE id = ?`
2. Verify `add_to_invoice` field value
3. Update to ADD (1) if price should be included
4. Use REMOVE (0) only for free services

#### Issue: Extra costs not added to invoice

**Cause:** `extra_is_pricable` is 0 or extra_price is 0.

**Solution:**
1. Check service category extra fields
2. Set `extra_is_pricable = 1` to enable
3. Set `extra_price` to desired amount
4. Verify invoice generation includes extra costs

#### Issue: Service code not appearing in XML export

**Cause:** Service category code field is empty.

**Solution:**
1. Check service category: `SELECT code FROM service_categories WHERE id = ?`
2. Update service category with appropriate code
3. Use standard codes for Italian invoicing if applicable

#### Issue: Cannot delete service category

**Cause:** Service category is referenced by tasks or invoices.

**Solution:**
1. Check references: 
   ```sql
   SELECT COUNT(*) FROM tasks WHERE service_category_id = ?;
   SELECT COUNT(*) FROM invoice_details WHERE invoiceable_type = 'App\\Models\\Task\\Task' 
     AND invoiceable_id IN (SELECT id FROM tasks WHERE service_category_id = ?);
   ```
2. Soft delete is used, so category is hidden but data preserved
3. Consider archiving instead of deleting if heavily used

### Database Queries for Debugging

#### Check service category with usage count

```sql
SELECT 
    sc.id,
    sc.name,
    sc.code,
    sc.price,
    sc.add_to_invoice,
    sc.extra_is_pricable,
    sc.extra_price,
    COUNT(DISTINCT t.id) as task_count,
    SUM(CASE WHEN t.invoice_id IS NOT NULL THEN 1 ELSE 0 END) as invoiced_count
FROM service_categories sc
LEFT JOIN tasks t ON sc.id = t.service_category_id AND t.deleted_at IS NULL
WHERE sc.id = 1
GROUP BY sc.id;
```

#### Find service categories with no tasks

```sql
SELECT 
    sc.id,
    sc.name,
    sc.code,
    sc.price,
    sc.created_at
FROM service_categories sc
LEFT JOIN tasks t ON sc.id = t.service_category_id AND t.deleted_at IS NULL
WHERE sc.deleted_at IS NULL
GROUP BY sc.id
HAVING COUNT(t.id) = 0
ORDER BY sc.created_at DESC;
```

#### Find most used service categories

```sql
SELECT 
    sc.id,
    sc.name,
    sc.code,
    sc.price,
    COUNT(t.id) as usage_count,
    SUM(t.price_after_discount) as total_revenue
FROM service_categories sc
JOIN tasks t ON sc.id = t.service_category_id
WHERE sc.deleted_at IS NULL
AND t.deleted_at IS NULL
AND t.invoice_id IS NOT NULL
GROUP BY sc.id
ORDER BY usage_count DESC
LIMIT 10;
```

#### Check service categories with extra costs

```sql
SELECT 
    id,
    name,
    code,
    price,
    extra_is_pricable,
    extra_code,
    extra_price,
    extra_price_description
FROM service_categories
WHERE extra_is_pricable = 1
AND deleted_at IS NULL
ORDER BY name;
```

#### Find service categories by price range

```sql
SELECT 
    id,
    name,
    code,
    price,
    add_to_invoice
FROM service_categories
WHERE price BETWEEN 100 AND 500
AND add_to_invoice = 1
AND deleted_at IS NULL
ORDER BY price ASC;
```

### Performance Optimization

#### Indexing Recommendations

```sql
-- Index for name searches
CREATE INDEX idx_service_categories_name 
ON service_categories(name);

-- Index for code lookups
CREATE INDEX idx_service_categories_code 
ON service_categories(code);

-- Index for service type
CREATE INDEX idx_service_categories_type 
ON service_categories(service_type_id);

-- Index for invoice inclusion
CREATE INDEX idx_service_categories_invoice 
ON service_categories(add_to_invoice);
```

#### Query Optimization

- Cache service categories list for dropdown selectors
- Use eager loading when loading tasks with service categories
- Avoid N+1 queries: `Task::with('serviceCategory')`
- Consider caching frequently used service categories


## Integration with Other Modules

### Task Management Module
- Tasks are assigned to service categories
- Task pricing is based on service category price
- Service category determines if price is included in invoices
- Extra costs from service categories are added to tasks

### Invoice Management Module
- Invoice details reference service categories through tasks
- Service codes are used in XML export
- Extra costs are added as separate line items
- Service category pricing affects invoice totals

### Client Management Module
- Client-specific discounts/taxes are applied per service category
- ClientServiceDiscount links clients to service categories
- Discounts can be percentage or fixed amount
- Can be discount (decrease) or tax (increase)

### Parameter Module
- Service types are stored in parameter_values
- Used for categorizing services
- Helps with reporting and filtering

## Common Use Cases

### 1. Standard Billable Service

```json
{
  "name": "Web Development",
  "code": "WD01",
  "description": "Website development and maintenance",
  "addToInvoice": 1,
  "price": 500.00,
  "extraIsPricable": 1,
  "extraPrice": 2.00,
  "extraCode": "BOLLO",
  "extraPriceDescription": "Marca da bollo"
}
```

Use case: Regular service with fixed price and stamp duty.

### 2. Free Service (No Charge)

```json
{
  "name": "Initial Consultation",
  "code": "CONS00",
  "description": "Free initial consultation",
  "addToInvoice": 0,
  "price": 0,
  "extraIsPricable": 0,
  "extraPrice": 0
}
```

Use case: Complimentary service that appears on invoices but has no charge.

### 3. Service Without Extra Costs

```json
{
  "name": "Consulting",
  "code": "CONS01",
  "description": "Business consulting services",
  "addToInvoice": 1,
  "price": 300.00,
  "extraIsPricable": 0,
  "extraPrice": 0
}
```

Use case: Standard service without additional charges.

### 4. High-Value Service with Stamp

```json
{
  "name": "System Integration",
  "code": "SI01",
  "description": "Complex system integration project",
  "addToInvoice": 1,
  "price": 5000.00,
  "extraIsPricable": 1,
  "extraPrice": 2.00,
  "extraCode": "BOLLO",
  "extraPriceDescription": "Marca da bollo"
}
```

Use case: High-value service requiring revenue stamp.

## Best Practices

### Naming Conventions

1. **Service Names**: Use clear, descriptive names
   - Good: "Web Development", "Tax Consulting", "Accounting Services"
   - Bad: "Service 1", "Misc", "Other"

2. **Service Codes**: Use consistent coding scheme
   - Format: `[CATEGORY][NUMBER]` (e.g., WD01, CONS01, ACC01)
   - Keep codes short (2-6 characters)
   - Use uppercase for consistency

3. **Descriptions**: Provide detailed descriptions
   - Explain what the service includes
   - Mention any limitations or conditions
   - Use clear, professional language

### Pricing Strategy

1. **Base Prices**: Set realistic base prices
   - Research market rates
   - Consider your costs and margins
   - Review and update regularly

2. **Client Discounts**: Use ClientServiceDiscount for variations
   - Don't create multiple service categories for different prices
   - Use discounts/taxes for client-specific pricing
   - Maintain single source of truth for service definition

3. **Extra Costs**: Use consistently
   - Apply stamps/duties as required by law
   - Document extra cost purposes
   - Keep extra costs separate from base price

### Service Organization

1. **Service Types**: Use parameter_values for categorization
   - Group related services
   - Helps with reporting and filtering
   - Makes service selection easier

2. **Active Management**: Regularly review service catalog
   - Archive unused services (soft delete)
   - Update prices as needed
   - Remove duplicate or obsolete services

3. **Documentation**: Maintain clear descriptions
   - Update when service scope changes
   - Include any special terms or conditions
   - Keep descriptions current

## Italian Invoicing Considerations

### Revenue Stamps (Marca da Bollo)

In Italy, invoices over €77.47 require a €2.00 revenue stamp (marca da bollo). Configure this as:

```json
{
  "extraIsPricable": 1,
  "extraPrice": 2.00,
  "extraCode": "BOLLO",
  "extraPriceDescription": "Marca da bollo"
}
```

### Service Codes

Use standard Italian service codes when applicable:
- Professional services codes
- Consulting codes
- Technical services codes

These codes appear in XML exports for electronic invoicing.

### VAT Considerations

Service categories define base prices. VAT (22% in Italy) is calculated at invoice level, not in service category.

## Related Modules

- **Task Management**: Uses service categories for task pricing
- **Invoice Management**: Uses service codes and prices for invoicing
- **Client Management**: Client discounts are applied per service category
- **Parameter Management**: Service types for categorization
- **Reporting**: Service category data in reports and analytics

## Future Enhancements

- Service category templates
- Bulk import/export of service categories
- Service category versioning (price history)
- Multi-currency support
- Service bundles and packages
- Seasonal pricing
- Volume-based pricing tiers
- Service category analytics
- Integration with external pricing databases
- Automated price updates based on market data
- Service category approval workflow
- Custom fields for service categories
- Service category tags and labels
- Advanced search and filtering

---

**Last Updated:** March 5, 2026  
**Module Version:** 1.0  
**Documentation Status:** Complete
