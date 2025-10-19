# 📡 Lreifs Multiquotes API v1.0.0 - Complete Endpoint Documentation

## Overview

Enterprise-grade REST API for immutable quote management in Magento 2.4.8+. Provides 9 comprehensive endpoints with advanced filtering, system-wide quote access, and complete audit trails.

## 🔐 Authentication

All endpoints require proper authentication tokens:

### Admin Token (Required for admin-only operations)
```bash
POST /rest/V1/integration/admin/token
Content-Type: application/json

{
  "username": "admin",
  "password": "admin123"
}
```

### Customer Token (For customer-specific operations)
```bash
POST /rest/V1/integration/customer/token
Content-Type: application/json

{
  "username": "customer@example.com", 
  "password": "password123"
}
```

---

## 📋 Complete API Endpoints

### 1. Create Immutable Quote with Items

**Creates a new immutable quote with multiple products**

```http
POST /rest/V1/lreifs-multiquotes/create-quote
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "request": {
    "customer_id": 1,
    "quote_name": "Enterprise Q4 Quote",
    "description": "Bulk order for quarterly inventory",
    "notes": "Expedited shipping required",
    "valid_until": "2024-12-31",
    "items": [
      {
        "sku": "product-sku-1",
        "qty": 10
      },
      {
        "sku": "product-sku-2",
        "qty": 5
      }
    ]
  }
}
```

**Response:**
```json
{
  "entity_id": 123,
  "quote_id": 456,
  "customer_id": 1,
  "quote_name": "Enterprise Q4 Quote",
  "description": "Bulk order for quarterly inventory",
  "is_immutable": 1,
  "is_active": 1,
  "valid_until": "2024-12-31",
  "immutable_created_by": 1,
  "created_at": "2024-10-18 10:30:00"
}
```

**Features:**
- ✅ Validates all SKUs and inventory
- ✅ Auto-generates quote with sequential ID
- ✅ Immediate immutable conversion
- ✅ Complete audit trail logging

---

### 2. Convert Existing Quote to Immutable

**Converts an existing Magento quote to immutable state**

```http
POST /rest/V1/lreifs-multiquotes/quotes/{quoteId}/convert
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "request": {
    "customer_id": 1,
    "quote_name": "Converted Shopping Cart",
    "description": "Customer cart converted to immutable quote",
    "valid_until": "2024-11-30"
  }
}
```

**Use Cases:**
- Convert abandoned shopping carts to quotes
- Lock pricing for customer negotiations
- Preserve cart state for later purchase

---

### 3. Get Quote Details

**Retrieves detailed information for a specific quote**

```http
GET /rest/V1/lreifs-multiquotes/quotes/{quoteId}
Authorization: Bearer {token}
```

**Response includes:**
- Complete quote metadata
- Item details with pricing
- Immutability status
- Audit information
- Customer data

---

### 4. Activate Quote

**Makes quote active and available for customer use**

```http
POST /rest/V1/lreifs-multiquotes/quotes/{quoteId}/activate
Authorization: Bearer {admin_token}
```

**Effects:**
- Quote becomes visible to customer
- Enables order conversion
- Logs activation with admin details
- Updates audit trail

---

### 5. Deactivate Quote

**Deactivates quote while preserving data**

```http
POST /rest/V1/lreifs-multiquotes/quotes/{quoteId}/deactivate
Authorization: Bearer {admin_token}
```

**Effects:**
- Hides quote from customer view
- Prevents order conversion
- Maintains data for audit purposes
- Logs deactivation action

---

### 6. Delete Quote Permanently

**Permanently removes quote from system with descriptive endpoint**

```http
DELETE /rest/V1/lreifs-multiquotes/quotes/{quoteId}/delete
Authorization: Bearer {admin_token}
```

**⚠️ Warning:** Irreversible action with complete audit logging

---

### 7. Get Customer Quotes

**Retrieves quotes for a specific customer**

```http
GET /rest/V1/customers/{customerId}/lreifs-multiquotes
Authorization: Bearer {token}

# Optional: Filter to immutable quotes only
GET /rest/V1/customers/{customerId}/lreifs-multiquotes?immutableOnly=true
```

**Features:**
- Customer-specific quote listing
- Optional immutable filtering
- Proper access control validation

---

### 8. Get All System Quotes (Admin Only) - 🆕 Enhanced in v1.0.0

**System-wide quote access with advanced filtering and pagination**

```http
GET /rest/V1/lreifs-multiquotes/all-quotes
Authorization: Bearer {admin_token}
```

#### Basic Usage
```bash
# Simple listing with pagination
GET /rest/V1/lreifs-multiquotes/all-quotes?page=1&limit=20

# With sorting
GET /rest/V1/lreifs-multiquotes/all-quotes?page=1&limit=20&sortBy=created_at&sortDirection=desc
```

#### Advanced Filtering
```bash
# Filter by customer
GET /rest/V1/lreifs-multiquotes/all-quotes?customer_id=1&page=1&limit=10

# Filter by active status
GET /rest/V1/lreifs-multiquotes/all-quotes?is_active=1&limit=5

# Filter by store
GET /rest/V1/lreifs-multiquotes/all-quotes?store_id=1&limit=15

# Date range filtering
GET /rest/V1/lreifs-multiquotes/all-quotes?date_from=2025-10-01&date_to=2025-10-31

# Combined filters
GET /rest/V1/lreifs-multiquotes/all-quotes?customer_id=1&is_active=0&sortBy=updated_at&sortDirection=desc&limit=10
```

#### Available Parameters
| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `page` | int | Page number for pagination | `page=1` |
| `limit` | int | Results per page (max 100) | `limit=20` |
| `sortBy` | string | Sort field | `sortBy=created_at` |
| `sortDirection` | string | Sort direction (asc/desc) | `sortDirection=desc` |
| `customer_id` | int | Filter by customer ID | `customer_id=1` |
| `is_active` | int | Filter by active status (0/1) | `is_active=1` |
| `store_id` | int | Filter by store ID | `store_id=1` |
| `date_from` | string | Start date (YYYY-MM-DD) | `date_from=2025-10-01` |
| `date_to` | string | End date (YYYY-MM-DD) | `date_to=2025-10-31` |

**⚠️ Admin Only:** Requires `Lreifs_Multiquotes::quote_extension_view_all` permission

#### Advanced Filtering Options

**Filter by Immutable Status:**
```bash
# Show only immutable quotes
GET /rest/V1/lreifs-multiquotes/all-quotes?filters[is_immutable]=1

# Show only normal Magento quotes  
GET /rest/V1/lreifs-multiquotes/all-quotes?filters[is_immutable]=0
```

**Filter by Customer:**
```bash
GET /rest/V1/lreifs-multiquotes/all-quotes?filters[customer_id]=123
```

**Filter by Active Status:**
```bash
# Active quotes only
GET /rest/V1/lreifs-multiquotes/all-quotes?filters[is_active]=1

# Inactive quotes only
GET /rest/V1/lreifs-multiquotes/all-quotes?filters[is_active]=0
```

**Filter by Store:**
```bash
GET /rest/V1/lreifs-multiquotes/all-quotes?filters[store_id]=1
```

**Date Range Filtering:**
```bash
# Quotes created in specific date range
GET /rest/V1/lreifs-multiquotes/all-quotes?filters[created_from]=2024-01-01&filters[created_to]=2024-12-31
```

**Combined Filtering:**
```bash
# Complex filter example: Active immutable quotes for customer 123 in store 1
GET /rest/V1/lreifs-multiquotes/all-quotes?pageSize=10&currentPage=1&filters[is_immutable]=1&filters[is_active]=1&filters[customer_id]=123&filters[store_id]=1
```

#### Response Format
```json
{
  "quotes": [
    {
      "entity_id": 1,
      "quote_id": 456,
      "customer_id": 123,
      "quote_name": "Enterprise Quote",
      "is_immutable": 1,
      "is_active": 1,
      "created_at": "2024-10-18 10:30:00",
      "creator_email": "admin@company.com"
    }
  ],
  "total_count": 150,
  "page_size": 20,
  "current_page": 1,
  "total_pages": 8
}
```

#### Available Filters Summary
| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `is_immutable` | int (0/1) | Filter by immutable status | `filters[is_immutable]=1` |
| `customer_id` | int | Filter by customer | `filters[customer_id]=123` |
| `is_active` | int (0/1) | Filter by active status | `filters[is_active]=1` |
| `store_id` | int | Filter by store | `filters[store_id]=1` |
| `created_from` | date | Start date filter | `filters[created_from]=2024-01-01` |
| `created_to` | date | End date filter | `filters[created_to]=2024-12-31` |

---

## 🛡️ Security & Authorization

### ACL Resources
- `Lreifs_Multiquotes::quote_extension_view` - View customer quotes
- `Lreifs_Multiquotes::quote_extension_view_all` - View all system quotes
- `Lreifs_Multiquotes::quote_extension_create` - Create and convert quotes
- `Lreifs_Multiquotes::quote_extension_activate` - Activate/deactivate quotes
- `Lreifs_Multiquotes::quote_extension_delete` - Delete quotes

### Audit Logging
All operations include comprehensive logging:
- IP address tracking
- User agent information
- Admin user identification
- Timestamp and action details
- Request/response data

---

## 📊 Response Status Codes

| Code | Description | Use Case |
|------|-------------|----------|
| 200 | Success | Successful operation |
| 201 | Created | Quote successfully created |
| 400 | Bad Request | Invalid parameters or request data |
| 401 | Unauthorized | Invalid or missing authentication token |
| 403 | Forbidden | Insufficient permissions for operation |
| 404 | Not Found | Quote or resource doesn't exist |
| 422 | Unprocessable Entity | Validation errors (invalid SKU, etc.) |
| 500 | Internal Server Error | System error during processing |

---

## 🧪 Testing with Postman Collection v1.0.0

Complete testing suite included: `POSTMAN_COLLECTION_MULTIQUOTES_v2.json`

### Features:
- ✅ All 9 endpoints with examples
- ✅ Advanced filtering demonstrations
- ✅ Automatic token management
- ✅ Response validation
- ✅ English descriptions throughout
- ✅ Real configuration values

### Quick Setup:
1. Import `POSTMAN_COLLECTION_MULTIQUOTES_v2.json`
2. Configure environment variables:
   - `base_url`: localhost
   - `admin_user`: admin
   - `admin_password`: lola123
   - `customer_email`: jane.doe@example.com
3. Run collection to test all endpoints

---

## 🚀 Enterprise Features

### Performance Optimizations
- Database indexes for fast filtering
- Efficient pagination handling
- Optimized query generation
- Memory-efficient collection loading

### Scalability Features  
- Handles thousands of quotes
- Efficient filtering at database level
- Proper resource management
- Enterprise-grade error handling

### Monitoring & Debugging
- Comprehensive audit trails
- Detailed error logging
- Performance metrics tracking
- Security event monitoring

---

*Complete API documentation for Lreifs Multiquotes Module v1.0.0*
*Production-ready with comprehensive testing and security features*