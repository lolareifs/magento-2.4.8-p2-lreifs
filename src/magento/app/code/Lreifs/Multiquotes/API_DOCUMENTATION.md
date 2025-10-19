# 📖 Lreifs Multiquotes - Complete API Documentation v1.0.0

## 🎯 Table of Contents

1. [System Overview](#system-overview)
2. [Data Architecture](#data-architecture)
3. [Available REST APIs](#available-rest-apis)
4. [CLI Commands](#cli-commands)
5. [Complete Use Cases](#complete-use-cases)
6. [Testing Examples](#testing-examples)
7. [Data Structure](#data-structure)
8. [Postman Collection v1.0.0](#postman-collection-v20)

---

## 🏗️ System Overview

**Lreifs Multiquotes v1.0.0** is a Magento 2 module that allows creating and managing **immutable quotes** for customers, with the following features:

- ✅ **Immutable quotes**: Cannot be modified once created
- ✅ **One active quote per customer**: Exclusive cart system
- ✅ **Flexible creation**: From existing quotes or with items from scratch
- ✅ **Email resolution**: Accepts customer_id or customer_email
- ✅ **Complete audit**: Logs of all operations
- ✅ **REST APIs + CLI**: Multiple interfaces for management
- ✅ **Postman Collection v1.0.0**: 25+ automated tests with intelligent validation
- ✅ **Complete documentation**: Technical architecture and database schemas

---

## 🏛️ Data Architecture

### **Quote State Management: `is_active` vs `status`**

Our system uses **both** `is_active` (boolean) and `status` (varchar) fields for different purposes:

#### **`is_active` Field (Boolean)**
- **Purpose**: Direct synchronization with Magento core `quote.is_active`
- **Values**: `0` (inactive) | `1` (active)
- **Use case**: Determines if quote is customer's current cart
- **Sync rule**: Always matches core quote table state

#### **`status` Field (Varchar)**
- **Purpose**: Business lifecycle state with granular control
- **Values**: `draft` | `active` | `inactive` | `converted` | `expired`
- **Use case**: Tracks quote business state and workflow

#### **State Combinations**
```sql
-- Active cart (customer's current cart)
is_active = 1, status = 'active'

-- Manually deactivated quote
is_active = 0, status = 'inactive'

-- Quote converted to order
is_active = 0, status = 'converted'

-- Expired quote
is_active = 0, status = 'expired'

-- Draft quote (not yet activated)
is_active = 0, status = 'draft'
```

#### **Why Both Fields?**
1. **`is_active`**: Ensures compatibility with Magento core cart behavior
2. **`status`**: Provides business context for quote lifecycle management
3. **Performance**: Boolean queries are faster for active/inactive filtering
4. **Extensibility**: Status can be extended with new business states
5. **Audit clarity**: Status provides human-readable state information

#### **Synchronization Rules**
- When quote is **activated**: `is_active = 1`, `status = 'active'`
- When quote is **deactivated**: `is_active = 0`, `status = 'inactive'`
- Core Magento sync: `is_active` always matches `quote.is_active`

---

## 🌐 Available REST APIs

### **Base URL**: `/rest/V1/lreifs-multiquotes/`

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/quotes` | Create immutable quote (with existing quote_id) |
| POST | `/create-quote` | Create immutable quote with items (without quote_id) |
| GET | `/quotes/:quoteId` | Get quote extension by Quote ID |
| POST | `/quotes/:quoteId/activate` | Activate quote (main cart) |
| POST | `/quotes/:quoteId/deactivate` | Deactivate quote |
| DELETE | `/quotes/:quoteId/delete` | Delete quote extension permanently |

---

### 📝 **1. Create Immutable Quote (from existing quote)**

**Endpoint**: `POST /V1/lreifs-multiquotes/quotes`

#### Request Body:
```json
{
  "request": {
    "quote_id": 15,
    "customer_id": 1,
    "customer_reference": "PO-2024-001",
    "custom_fee": 25.50,
    "notes": "Immutable quote created from existing quote",
    "metadata": {
      "priority": "high",
      "special_handling": true
    }
  }
}
```

#### Request Body (with email):
```json
{
  "request": {
    "quote_id": 15,
    "customer_email": "lolareifscarmona+11@gmail.com",
    "customer_reference": "PO-2024-002",
    "notes": "Quote created using customer email"
  }
}
```

#### Response:
```json
{
  "id": 1,
  "quote_id": 15,
  "customer_id": 1,
  "original_quote_id": 15,
  "is_immutable": true,
  "immutable_at": "2024-12-20T10:00:00",
  "created_at": "2024-12-20T09:30:00",
  "updated_at": "2024-12-20T09:30:00",
  "notes": "Immutable quote created from existing quote",
  "is_active": false,
  "admin_user_id": 1,
  "customer_reference": "PO-2024-001",
  "custom_fee": 25.50
}
```

---

### 🛒 **2. Create Immutable Quote with Items (from scratch)**

**Endpoint**: `POST /V1/lreifs-multiquotes/create-quote`

#### Request Body:
```json
{
  "request": {
    "customer_email": "lolareifscarmona+11@gmail.com",
    "immutable_at": "2024-12-20T10:00:00",
    "notes": "Quote created from items",
    "customer_reference": "ORDER-2024-15",
    "items": [
      {
        "sku": "lreifs-test-product-001",
        "qty": 2,
        "price": 99.99
      },
      {
        "sku": "lreifs-test-product-002",
        "qty": 1,
        "price": 149.50
      },
      {
        "sku": "lreifs-test-product-003",
        "qty": 3,
        "price": 79.95
      }
    ]
  }
}
```

#### Response:
```json
{
  "id": 2,
  "quote_id": 16,
  "customer_id": 1,
  "original_quote_id": null,
  "is_immutable": true,
  "immutable_at": "2024-12-20T10:00:00",
  "created_at": "2024-12-20T09:45:00",
  "updated_at": "2024-12-20T09:45:00",
  "notes": "Quote created from items",
  "is_active": false,
  "admin_user_id": null,
  "customer_reference": "ORDER-2024-15"
}
```

---

### 🔍 **3. Get Quote Extension**

**Endpoint**: `GET /V1/lreifs-multiquotes/quotes/{quoteId}`

#### Response:
```json
{
  "id": 1,
  "quote_id": 15,
  "customer_id": 1,
  "is_immutable": true,
  "is_active": true,
  "created_at": "2024-12-20T09:30:00",
  "notes": "Active immutable quote",
  "customer_reference": "PO-2024-001"
}
```

---

### ⚡ **4. Activate Quote (Main Cart)**

**Endpoint**: `POST /V1/lreifs-multiquotes/quotes/{quoteId}/activate`

#### Request Body:
```json
{
  "admin_user_id": 1
}
```

#### Response:
```json
{
  "success": true,
  "message": "Quote activated successfully",
  "quote_id": 15,
  "customer_id": 1,
  "deactivated_quotes": [12, 14],
  "timestamp": "2024-12-20T10:15:00"
}
```

---

### 💤 **5. Deactivate Quote**

**Endpoint**: `POST /V1/lreifs-multiquotes/quotes/{quoteId}/deactivate`

#### Request Body:
```json
{
  "admin_user_id": 1
}
```

---

### 🗑️ **6. Delete Quote Extension Permanently**

**Endpoint**: `DELETE /V1/lreifs-multiquotes/quotes/{quoteId}/delete`

**Purpose**: Permanently remove quote from system with descriptive endpoint

---

### 🔄 **7. Convert Quote to Order**

**Endpoint**: `POST /V1/lreifs-multiquotes/quotes/{quoteId}/convert-to-order`

**Purpose**: Convert an immutable quote to a Magento order

**Request Body**:
```json
{
  "admin_user_id": 1,
  "payment_method": "checkmo",
  "shipping_method": "flatrate_flatrate"
}
```

**Response**: Order details with order ID

---

### 🔍 **8. Check if Quote Can Be Modified**

**Endpoint**: `GET /V1/lreifs-multiquotes/quotes/{quoteId}/can-modify`

**Purpose**: Verify if a quote can still be modified (not immutable)

**Response**:
```json
{
  "can_modify": false,
  "reason": "Quote is marked as immutable",
  "immutable_since": "2025-10-17 10:30:00"
}
```

---

### 📋 **9. Get Customer Quotes (Alternative endpoint)**

**Endpoint**: `GET /V1/customers/{customerId}/lreifs-multiquotes/immutable`

**Purpose**: Get only immutable quotes for a customer

**Query Parameters**:
- `page` (optional): Page number
- `limit` (optional): Items per page

---

## 🏗️ Advanced Repository API

### **10. Get Quote Extension by Entity ID**

**Endpoint**: `GET /V1/lreifs-multiquotes/extensions/{id}`

**Purpose**: Get quote extension by its entity_id (not quote_id)

---

### **11. Save Quote Extension**

**Endpoint**: `POST /V1/lreifs-multiquotes/extensions`

**Purpose**: Create or update quote extension using repository pattern

**Request Body**: Complete QuoteExtension object

---

### **12. Update Quote Extension**

**Endpoint**: `PUT /V1/lreifs-multiquotes/extensions/{id}`

**Purpose**: Update existing quote extension

---

### **13. Delete Quote Extension by Entity ID**

**Endpoint**: `DELETE /V1/lreifs-multiquotes/extensions/{id}`

**Purpose**: Delete quote extension by entity_id

---

### **14. Search Quote Extensions**

**Endpoint**: `GET /V1/lreifs-multiquotes/extensions/search`

**Purpose**: Advanced search with filters, sorting, and pagination

**Query Parameters**:
- `searchCriteria[filter_groups][0][filters][0][field]`: Field to filter
- `searchCriteria[filter_groups][0][filters][0][value]`: Filter value
- `searchCriteria[filter_groups][0][filters][0][condition_type]`: Condition (eq, like, etc.)
- `searchCriteria[sort_orders][0][field]`: Sort field
- `searchCriteria[sort_orders][0][direction]`: Sort direction (ASC/DESC)
- `searchCriteria[page_size]`: Items per page
- `searchCriteria[current_page]`: Current page

---

### **15. Get Expired Quotes (Admin Only)**

**Endpoint**: `GET /V1/lreifs-multiquotes/admin/expired-quotes`

**Purpose**: Get all expired quotes for administrative cleanup

**Access**: Admin users only

---

## 🖥️ CLI Commands

### **1. Create Immutable Quote**

#### From existing quote:
```bash
# With customer ID
php bin/magento lreifs:multiquotes:create-immutable 15 1 \
  --admin-user-id=1 \
  --customer-reference="CLI-TEST-001" \
  --custom-fee=15.75

# With customer email
php bin/magento lreifs:multiquotes:create-immutable 15 \
  --customer-email="lolareifscarmona+11@gmail.com" \
  --admin-user-id=1 \
  --customer-reference="CLI-TEST-002"
```

#### From items (without existing quote):
```bash
php bin/magento lreifs:multiquotes:create-immutable \
  --customer-email="lolareifscarmona+11@gmail.com" \
  --items='[
    {"sku":"lreifs-test-product-001","qty":2,"price":99.99},
    {"sku":"lreifs-test-product-002","qty":1,"price":149.50},
    {"sku":"lreifs-test-product-003","qty":3,"price":79.95}
  ]' \
  --customer-reference="CLI-ITEMS-001" \
  --admin-user-id=1
```

### **2. Activate Quote**

```bash
# Activate specific quote
php bin/magento lreifs:multiquotes:activate 15 --admin-user-id=1

# With verbose mode to see details
php bin/magento lreifs:multiquotes:activate 15 --admin-user-id=1 -vvv
```

### **3. List Customer Quotes**

```bash
# All quotes
php bin/magento lreifs:multiquotes:list-customer-quotes 1

# Only immutable quotes
php bin/magento lreifs:multiquotes:list-customer-quotes 1 --immutable-only

# JSON format
php bin/magento lreifs:multiquotes:list-customer-quotes 1 --format=json

# CSV format
php bin/magento lreifs:multiquotes:list-customer-quotes 1 --format=csv
```

### **4. Deactivate Quote**

```bash
# Deactivate specific quote
php bin/magento lreifs:multiquotes:deactivate 15 --admin-user-id=1
```

### **5. Delete Quote**

```bash
# Permanently delete quote extension
php bin/magento lreifs:multiquotes:delete 15 --admin-user-id=1
```

### **4. Deactivate Quote**

```bash
php bin/magento lreifs:multiquotes:deactivate 15 --admin-user-id=1
```

---

## 🧪 Complete Testing Examples

### **Scenario 1: Testing with cURL (REST APIs)**

#### Step 1: Get authentication token
```bash
# Get admin token
curl -X POST "http://localhost/rest/V1/integration/admin/token" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "lola123"
  }'

# Response: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
export TOKEN="your_token_here"
```

#### Step 2: Create quote with items
```bash
curl -X POST "http://localhost/rest/V1/lreifs-multiquotes/create-quote" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "request": {
      "customer_email": "lolareifscarmona+11@gmail.com",
      "notes": "Quote de prueba API",
      "customer_reference": "API-TEST-001",
      "items": [
        {
          "sku": "lreifs-test-product-001",
          "qty": 2,
          "price": 99.99
        },
        {
          "sku": "lreifs-test-product-002",
          "qty": 1,
          "price": 149.50
        }
      ]
    }
  }'
```

#### Step 3: Get created quote
```bash
curl -X GET "http://localhost/rest/V1/lreifs-multiquotes/quotes/1" \
  -H "Authorization: Bearer $TOKEN"
```

#### Step 4: Activate quote
```bash
curl -X POST "http://localhost/rest/V1/lreifs-multiquotes/quotes/1/activate" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "admin_user_id": 1
  }'
```

### **Scenario 2: Testing with CLI commands**

#### Test data setup
```bash
# Clear cache
docker compose exec phpfpm php bin/magento cache:clean

# Create first quote with items
docker compose exec phpfpm php bin/magento lreifs:multiquotes:create-immutable \
  --customer-email="lolareifscarmona+11@gmail.com" \
  --items='[{"sku":"lreifs-test-product-001","qty":2,"price":99.99}]' \
  --customer-reference="CLI-QUOTE-001" \
  --admin-user-id=1

# Create second quote with different items
docker compose exec phpfpm php bin/magento lreifs:multiquotes:create-immutable \
  --customer-email="lolareifscarmona+11@gmail.com" \
  --items='[{"sku":"lreifs-test-product-002","qty":1,"price":149.50}]' \
  --customer-reference="CLI-QUOTE-002" \
  --admin-user-id=1

# List all customer quotes
docker compose exec phpfpm php bin/magento lreifs:multiquotes:list-customer-quotes 1

# Activate first quote (should automatically deactivate others)
docker compose exec phpfpm php bin/magento lreifs:multiquotes:activate 1 --admin-user-id=1

# Verify state after activation
docker compose exec phpfpm php bin/magento lreifs:multiquotes:list-customer-quotes 1

# Activate second quote (change active cart)
docker compose exec phpfpm php bin/magento lreifs:multiquotes:activate 2 --admin-user-id=1

# Verify state change
docker compose exec phpfpm php bin/magento lreifs:multiquotes:list-customer-quotes 1
```

### **Scenario 3: Validation testing**

#### Expected errors - Non-existent customer
```bash
# By non-existent email
docker compose exec phpfpm php bin/magento lreifs:multiquotes:create-immutable \
  --customer-email="noexiste@test.com" \
  --items='[{"sku":"product-test","qty":1}]'

# Expected result: "Customer with email 'noexiste@test.com' does not exist"
```

#### Expected errors - Non-existent product
```bash
# With non-existent SKU
docker compose exec phpfpm php bin/magento lreifs:multiquotes:create-immutable \
  --customer-email="lolareifscarmona+11@gmail.com" \
  --items='[{"sku":"non-existent-product","qty":1}]'

# Expected result: "Product does not exist"
```

#### Expected errors - Non-existent quote
```bash
# Try to activate non-existent quote
docker compose exec phpfpm php bin/magento lreifs:multiquotes:activate 999 --admin-user-id=1

# Expected result: "Quote with ID '999' does not exist"
```

### **Scenario 4: Complete CRUD testing - DELETE and DEACTIVATE**

#### Quote deletion
```bash
# Delete specific quote (with confirmation)
docker compose exec phpfpm php bin/magento lreifs:multiquotes:delete 1

# Delete quote without confirmation (forced)
docker compose exec phpfpm php bin/magento lreifs:multiquotes:delete 2 --force

# Try to delete non-existent quote
docker compose exec phpfpm php bin/magento lreifs:multiquotes:delete 999
# Expected result: "Quote with ID '999' does not exist"
```

#### Quote deactivation
```bash
# Deactivate quote (will no longer be customer's active cart)
docker compose exec phpfpm php bin/magento lreifs:multiquotes:deactivate 3

# Try to deactivate non-existent quote
docker compose exec phpfpm php bin/magento lreifs:multiquotes:deactivate 999
# Expected result: "Quote with ID '999' does not exist"
```

#### State verification after CRUD
```bash
# Verify remaining quotes after deletions
docker compose exec phpfpm php bin/magento lreifs:multiquotes:list-customer-quotes 1

# Verify in database
docker compose exec db mysql -u magento -pmagento -D magento -e "SELECT entity_id, quote_id, customer_id, status, is_immutable FROM lreifs_quote_extension;"
```

---

## **📊 Available CLI Commands Summary**

| Command | Description | Parameters |
|---------|-------------|------------|
| `lreifs:multiquotes:create-immutable` | Create immutable quote | `--customer-email`, `--items`, `--customer-reference`, `--admin-user-id` |
| `lreifs:multiquotes:list-customer-quotes` | List customer quotes | `customer_id` |
| `lreifs:multiquotes:activate` | Activate quote as active cart | `quote_id`, `--admin-user-id` |
| `lreifs:multiquotes:delete` | Delete quote permanently | `quote_id`, `--force` (optional) |
| `lreifs:multiquotes:deactivate` | Deactivate customer quote | `quote_id` |

---

## **🔄 APIs REST Disponibles**

### **Main Endpoints:**

1. **POST** `/rest/V1/lreifs/multiquotes/immutable` - Create immutable quote
2. **GET** `/rest/V1/lreifs/multiquotes/customer/{customerId}` - List customer quotes  
3. **PUT** `/rest/V1/lreifs/multiquotes/{quoteId}/activate` - Activate quote
4. **DELETE** `/rest/V1/lreifs/multiquotes/{quoteId}/delete` - Delete quote permanently
5. **PUT** `/rest/V1/lreifs/multiquotes/{quoteId}/deactivate` - Deactivate quote

---

## **🎯 Complete Use Cases**

### **Complete CRUD:**
- ✅ **CREATE**: `create-immutable` command and API POST
- ✅ **READ**: `list-customer-quotes` command and API GET  
- ✅ **UPDATE**: `activate` command and API PUT
- ✅ **DELETE**: `delete` command and API DELETE
- ✅ **DEACTIVATE**: `deactivate` command and API PUT

### **Enterprise features:**
- ✅ Customer and product validation
- ✅ Customer resolution by email
- ✅ Quote creation from scratch with items
- ✅ Exclusive activation system per customer
- ✅ Database persistence with audit
- ✅ Enterprise service architecture
- ✅ Complete CLI commands for testing

---
docker compose exec phpfpm php bin/magento lreifs:multiquotes:activate 999

# Expected result: "Quote extension for quote id '999' does not exist"
```

### **Scenario 4: Complete data testing**

#### Complete testing script
```bash
#!/bin/bash
# Complete testing script

echo "=== LREIFS MULTIQUOTES - COMPLETE TESTING ==="

# 1. Create multiple quotes for the same customer
echo "1. Creating test quotes..."

docker compose exec phpfpm php bin/magento lreifs:multiquotes:create-immutable \
  --customer-email="lolareifscarmona+11@gmail.com" \
  --items='[{"sku":"lreifs-test-product-001","qty":2,"price":99.99}]' \
  --customer-reference="BATCH-001" \
  --admin-user-id=1

docker compose exec phpfpm php bin/magento lreifs:multiquotes:create-immutable \
  --customer-email="lolareifscarmona+11@gmail.com" \
  --items='[{"sku":"lreifs-test-product-002","qty":1,"price":149.50}]' \
  --customer-reference="BATCH-002" \
  --admin-user-id=1

docker compose exec phpfpm php bin/magento lreifs:multiquotes:create-immutable \
  --customer-email="lolareifscarmona+11@gmail.com" \
  --items='[{"sku":"lreifs-test-product-003","qty":3,"price":79.95}]' \
  --customer-reference="BATCH-003" \
  --admin-user-id=1

# 2. List all quotes
echo "2. Listando quotes creadas..."
docker compose exec phpfpm php bin/magento lreifs:multiquotes:list-customer-quotes 1

# 3. Probar activaciones (solo una activa a la vez)
echo "3. Probando activaciones exclusivas..."

echo "Activando quote 1..."
docker compose exec phpfpm php bin/magento lreifs:multiquotes:activate 1 --admin-user-id=1

echo "Estado después de activar quote 1:"
docker compose exec phpfpm php bin/magento lreifs:multiquotes:list-customer-quotes 1

echo "Activando quote 2 (debería desactivar quote 1)..."
docker compose exec phpfpm php bin/magento lreifs:multiquotes:activate 2 --admin-user-id=1

echo "Estado después de activar quote 2:"
docker compose exec phpfpm php bin/magento lreifs:multiquotes:list-customer-quotes 1

echo "Activando quote 3 (debería desactivar quote 2)..."
docker compose exec phpfpm php bin/magento lreifs:multiquotes:activate 3 --admin-user-id=1

echo "Final state - only quote 3 should be active:"
docker compose exec phpfpm php bin/magento lreifs:multiquotes:list-customer-quotes 1

# 4. Test manual deactivation
echo "4. Testing manual deactivation..."
docker compose exec phpfpm php bin/magento lreifs:multiquotes:deactivate 3 --admin-user-id=1

echo "State after deactivating quote 3 - none should be active:"
docker compose exec phpfpm php bin/magento lreifs:multiquotes:list-customer-quotes 1

echo "=== TESTING COMPLETED ==="
```

---

## 📊 Data Structure

### **Quote Extension Entity**
```php
{
  "id": int,                    // Primary key
  "quote_id": int,              // Magento Quote ID
  "customer_id": int,           // Customer ID
  "original_quote_id": int,     // Origin quote (if applicable)
  "is_immutable": boolean,      // Always true
  
  // State Management (see Data Architecture section)
  "is_active": boolean,         // Synced with core quote.is_active (0|1)
  "status": string,             // Business state: draft|active|inactive|converted|expired
  
  "immutable_at": datetime,     // When it became immutable
  "expires_at": datetime,       // Expiration date
  "created_at": datetime,       // Creation date
  "updated_at": datetime,       // Last update
  "notes": string,              // Custom notes
  "admin_user_id": int,         // Admin who created it
  "customer_reference": string, // Customer reference
  "custom_fee": decimal,        // Custom fee
  "metadata": json,             // Additional metadata
  "ip_address": string,         // Creation IP
  "user_agent": string,         // Creation user agent
  "processing_time_ms": decimal // Processing time
}
```

### **Quote Items Structure**
```php
{
  "sku": string,        // Product SKU (required)
  "qty": number,        // Quantity (required)
  "price": number       // Price (optional, uses product price if not specified)
}
```

### **API Request Structure**
```php
{
  "request": {
    // Customer identification (one of the two required)
    "customer_id": int,           // Customer ID
    "customer_email": string,     // Customer email
    
    // Quote source (optional if using items)
    "quote_id": int,              // Quote existente
    
    // Items (opcional si se usa quote_id)
    "items": [QuoteItem],         // Array de items
    
    // Campos opcionales
    "admin_user_id": int,         // Admin responsable
    "customer_reference": string, // Referencia del customer
    "custom_fee": decimal,        // Tarifa adicional
    "notes": string,              // Notas
    "metadata": object,           // Metadatos JSON
    "immutable_at": datetime,     // Fecha inmutable (auto si no se especifica)
    "expires_at": datetime,       // Fecha expiración
    
    // Campos automáticos (no especificar)
    "ip_address": string,         // Se captura automáticamente
    "user_agent": string          // Se captura automáticamente
  }
}
```

---

## 🔒 Authentication and Permissions

### **REST API Authentication**
```bash
# Get token
curl -X POST "http://localhost/rest/V1/integration/admin/token" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"lola123"}'

# Use token in requests
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "http://localhost/rest/V1/lreifs-multiquotes/create-quote"
```

### **Required ACL Permissions**
- `Lreifs_Multiquotes::quote_extension_create` - Create quotes
- `Lreifs_Multiquotes::quote_extension_view` - View quotes
- `Lreifs_Multiquotes::quote_extension_activate` - Activate/Deactivate
- `Lreifs_Multiquotes::quote_extension_delete` - Delete quotes

---

## 📝 Implementation Notes

### **Automatic Validations**
1. **Customer exists**: By ID or email
2. **Products exist**: All SKUs must be valid
3. **Products enabled**: Only active products
4. **Valid quantities**: Positive numbers
5. **Quote belongs to customer**: Ownership validation
6. **Quote is immutable**: Only immutable quotes can be activated

### **System Behavior**
1. **One active quote per customer**: Activating one automatically deactivates others
2. **Complete audit**: All operations are logged
3. **Magento integration**: Active quote becomes the main cart
4. **Automatic events**: Events are triggered for integration with other modules

### **Recommended Database Queries**

#### **Get customer's active quote (performance optimized)**
```sql
-- Use is_active for performance (boolean index)
SELECT * FROM lreifs_quote_extension 
WHERE customer_id = ? AND is_active = 1;
```

#### **Get quotes by business state**
```sql
-- Use status for business logic
SELECT * FROM lreifs_quote_extension 
WHERE customer_id = ? AND status = 'active';

SELECT * FROM lreifs_quote_extension 
WHERE status IN ('expired', 'converted');
```

#### **Verify core synchronization**
```sql
-- Check sync between extension and core
SELECT qe.entity_id, qe.quote_id, qe.is_active as ext_active, 
       q.is_active as core_active, qe.status
FROM lreifs_quote_extension qe 
LEFT JOIN quote q ON qe.quote_id = q.entity_id 
WHERE qe.is_active != q.is_active;
```

### **Logs and Debugging**
- **Log file**: `/var/log/lreifs_multiquotes.log`
- **Verbose mode**: Use `-vvv` in CLI commands for stack traces
- **Events**: All changes trigger events for auditing

---

## 🎯 Main Use Cases

1. **B2B E-commerce**: Customers can have multiple quotes and activate the one they need for checkout
2. **Cart management**: Easily switch between different product configurations

---

## 🧪 Postman Collection v1.0.0

### **📁 Complete Testing Suite**

The module includes a comprehensive Postman collection with **25+ automated tests** that provide:

#### **🔗 Collection Location**
- **Primary**: `/POSTMAN_COLLECTION_MULTIQUOTES_v2.json` (project root)
- **Module copy**: `./Lreifs_Multiquotes_API.postman_collection.json`

#### **✅ Automated Features**
- **Auto-Authentication**: Automatically generates and manages JWT tokens
- **Response Validation**: Validates structure, data types, and business logic
- **Test Chaining**: Uses response data from previous tests in subsequent requests
- **Intelligent Logging**: Detailed console output for debugging
- **Error Handling**: Graceful handling of expected and unexpected errors

#### **📋 Test Coverage by Endpoint**

| Endpoint | Tests | Validations |
|----------|-------|-------------|
| **Admin Token** | 3 tests | Token format, expiration, authentication |
| **Customer Token** | 3 tests | Customer validation, token structure |
| **Create Quote** | 5 tests | Request validation, response structure, business rules |
| **Get Quote** | 4 tests | Quote retrieval, permissions, data integrity |
| **List Quotes** | 3 tests | Pagination, filtering, customer quotes |
| **Update Status** | 4 tests | Status transitions, validation rules |
| **Delete Quote** | 3 tests | Deletion validation, cascade effects |
| **Convert Quote** | 2 tests | Cart conversion, quote creation |
| **Get By Customer** | 3 tests | Customer filtering, immutable queries |

#### **🔧 Environment Variables**
```json
{
  "base_url": "http://local.magento",
  "admin_user": "admin",
  "admin_password": "lola123", 
  "customer_email": "customer@example.com",
  "customer_password": "password123",
  "admin_token": "{{admin_token}}",
  "customer_token": "{{customer_token}}",
  "quote_id": "{{quote_id}}",
  "customer_id": "{{customer_id}}"
}
```

#### **🚀 Quick Start**
1. Import `POSTMAN_COLLECTION_MULTIQUOTES_v2.json` into Postman
2. Set environment variables (base_url, credentials)
3. Run the full collection or individual folders
4. Monitor test results in the Postman Test Results tab

#### **📊 Test Scripts Examples**

**Auto-Token Generation:**
```javascript
pm.test("Store admin token", function () {
    const responseJson = pm.response.json();
    pm.environment.set("admin_token", responseJson);
    console.log("🔑 Admin token stored:", responseJson.substring(0, 20) + "...");
});
```

**Response Structure Validation:**
```javascript
pm.test("Quote structure is valid", function () {
    const quote = pm.response.json();
    pm.expect(quote).to.have.property('entity_id');
    pm.expect(quote).to.have.property('quote_id');
    pm.expect(quote).to.have.property('customer_id');
    pm.expect(quote).to.have.property('is_immutable');
    pm.expect(quote.is_immutable).to.be.true;
});
```

**Business Logic Validation:**
```javascript
pm.test("Only one active quote per customer", function () {
    const quotes = pm.response.json();
    const activeQuotes = quotes.filter(q => q.is_active === true);
    pm.expect(activeQuotes.length).to.be.at.most(1);
    console.log(`📊 Customer has ${activeQuotes.length} active quotes (max: 1)`);
});
```

#### **🎯 Advanced Testing Features**

**Dynamic Test Data:**
- Quote IDs are automatically captured and reused
- Customer data is dynamically generated per test run
- Product SKUs are validated against catalog

**Error Scenario Testing:**
- Invalid customer credentials
- Non-existent quote IDs
- Permission validation
- Malformed request bodies

**Performance Monitoring:**
- Response time validation
- Memory usage tracking
- Database query optimization verification

---

## 📈 **Testing Best Practices**

1. **Run Environment Setup** first (Admin Token folder)
2. **Execute tests sequentially** for data dependencies
3. **Monitor console output** for detailed debugging information
4. **Verify database state** after destructive operations
5. **Use different customer accounts** for comprehensive testing

---

**🎉 Ready for enterprise-level testing and production deployment!**
3. **Temporary quotes**: Create quotes with expiration for special offers
4. **Commercial audit**: Complete traceability of changes and activations
5. **Approval workflow**: Immutable quotes that cannot change after approval

---

**🚀 The Lreifs Multiquotes system is completely documented and ready to use!**