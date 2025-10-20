# LREIFS Multiquotes API Documentation

## Authentication

Authenticate using Magento's admin token system for API access.

**Endpoint:**
```
POST /rest/V1/integration/admin/token
```

**cURL Example:**
```bash
curl -X POST "<baseUrl>/rest/V1/integration/admin/token" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "XXX"
  }'
```

**CLI Command:**
```bash
bin/magento admin:user:create --admin-user=apiuser --admin-password=ApiPass123! --admin-email=api@lreifs.com --admin-firstname=API --admin-lastname=User
```

**Response:** 
```
"bm90aWNlZGFta25vd2luZzphcGl0ZXN0aW5nMTIz"
```

> **Important:** Store the token securely and include it in all API requests as: `Authorization: Bearer {token}`

---

## Quote Management API

Real endpoints for quote management with practical examples and CLI testing commands.

### List All Quotes
**Endpoint:**
```
GET /rest/V1/lreifs-multiquotes/all-quotes
```

**cURL Example:**
```bash
curl -X GET "<baseUrl>/rest/V1/lreifs-multiquotes/all-quotes?page=1&limit=20" \
  -H "Authorization: Bearer <adminToken>" \
  -H "Content-Type: application/json"
```

**CLI Command:**
```bash
bin/magento lreifs:multiquotes:list --format=json
bin/magento lreifs:multiquotes:list --status=pending
```

**Response:**
```json
{
  "success": true,
  "quotes": [
    {
      "id": 1,
      "customer_id": 123,
      "status": "pending",
      "total_amount": 1250.50,
      "currency": "EUR",
      "created_at": "2025-10-19T10:30:00Z",
      "expires_at": "2025-11-18T23:59:59Z"
    }
  ],
  "total_count": 24
}
```

### Get Quote Details
**Endpoint:**
```
GET /rest/V1/lreifs-multiquotes/quotes/{id}
```

**cURL Example:**
```bash
curl -X GET "<baseUrl>/rest/V1/lreifs-multiquotes/quotes/1" \
  -H "Authorization: Bearer <adminToken>" \
  -H "Content-Type: application/json"
```

**CLI Command:**
```bash
bin/magento lreifs:multiquotes:show --quote-id=1 --detailed
```

**Response:**
```json
{
  "success": true,
  "quote": {
    "id": 1,
    "customer_id": 123,
    "customer_email": "customer@example.com",
    "status": "pending",
    "total_amount": 1250.50,
    "currency": "EUR",
    "notes": "Bulk order discount applied",
    "items": [
      {
        "product_id": 456,
        "sku": "LAPTOP-001",
        "quantity": 2,
        "unit_price": 625.25,
        "total": 1250.50
      }
    ],
    "created_at": "2025-10-19T10:30:00Z",
    "expires_at": "2025-11-18T23:59:59Z"
  }
}
```

### Create New Quote
**Endpoint:**
```
POST /rest/V1/lreifs-multiquotes/create-quote
```

**cURL Example:**
```bash
curl -X POST "<baseUrl>/rest/V1/lreifs-multiquotes/create-quote" \
  -H "Authorization: Bearer <adminToken>" \
  -H "Content-Type: application/json" \
  -d '{
    "request": {
      "customer_id": 123,
      "items": [
        {
          "sku": "LAPTOP-001",
          "qty": 2,
          "price": 625.25
        }
      ],
      "notes": "Special pricing for VIP customer",
      "expires_at": "2025-12-19T23:59:59Z"
    }
  }'
```

**CLI Command:**
```bash
bin/magento lreifs:multiquotes:create-immutable \
  --customer-id=123 \
  --items='[{"sku":"LAPTOP-001","qty":2,"price":625.25}]' \
  --expires-at="2025-12-19T23:59:59Z"
```

**Response:**
```json
{
  "success": true,
  "message": "Quote created successfully",
  "quote_id": 25,
  "quote_number": "QTE-2025-000025"
}
```

### Activate Quote
**Endpoint:**
```
PUT /rest/V1/lreifs-multiquotes/quotes/{id}/activate
```

**cURL Example:**
```bash
curl -X PUT "<baseUrl>/rest/V1/multiquotes/quotes/1/status" \
  -H "Authorization: Bearer <adminToken>" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "approved",
    "admin_notes": "Approved with special terms"
  }'
```

**CLI Command:**
```bash
bin/magento lreifs:multiquotes:approve --quote-id=1 --notes="Approved with special terms"
bin/magento lreifs:multiquotes:reject --quote-id=1 --reason="Price too high"
```

**Response:**
```json
{
  "success": true,
  "message": "Quote status updated successfully",
  "new_status": "approved"
}
```

### Delete Quote
**Endpoint:**
```
DELETE /rest/V1/lreifs-multiquotes/quotes/{id}/delete
```

**cURL Example:**
```bash
curl -X DELETE "<baseUrl>/rest/V1/multiquotes/quotes/1" \
  -H "Authorization: Bearer <adminToken>" \
  -H "Content-Type: application/json"
```

**CLI Command:**
```bash
bin/magento lreifs:multiquotes:delete --quote-id=1 --reason="Customer request"
bin/magento lreifs:multiquotes:delete --quote-id=1 --force
```

**Response:**
```json
{
  "success": true,
  "message": "Quote deleted successfully"
}
```

---

## Rate Limiting & Performance

API rate limiting controls and performance monitoring endpoints.

### Get Rate Limit Status
**Endpoint:**
```
GET /rest/V1/multiquotes/rate-limit/status
```

**cURL Example:**
```bash
curl -X GET "<baseUrl>/rest/V1/multiquotes/rate-limit/status" \
  -H "Authorization: Bearer <adminToken>" \
  -H "Content-Type: application/json"
```

**CLI Command:**
```bash
bin/magento lreifs:multiquotes:rate-limit:status
bin/magento lreifs:multiquotes:rate-limit:reset --ip=192.168.1.100
```

**Response:**
```json
{
  "success": true,
  "rate_limit": {
    "requests_per_hour": 1000,
    "remaining_requests": 847,
    "reset_time": "2025-10-19T11:00:00Z",
    "current_usage": 153
  }
}
```

---

## CLI Commands Reference

### Quote Management
```bash
# List all quotes
bin/magento lreifs:multiquotes:list

# Get quote details
bin/magento lreifs:multiquotes:show --quote-id=1

# Create new quote
bin/magento lreifs:multiquotes:create --customer-id=123 --product-id=456 --quantity=2

# Approve quote
bin/magento lreifs:multiquotes:approve --quote-id=1

# Reject quote
bin/magento lreifs:multiquotes:reject --quote-id=1

# Export quotes to CSV
bin/magento lreifs:multiquotes:export --format=csv --status=pending

# Process expired quotes (NEW)
bin/magento lreifs:quotes:expire --dry-run --batch-size=50

# Create quote with expiration date
bin/magento lreifs:multiquotes:create-immutable \
  --customer-id=123 \
  --expires-at="2025-12-31T23:59:59Z" \
  --items='[{"sku":"product-001","qty":2,"price":99.99}]'
```

### Rate Limiting
```bash
# Check rate limit status
bin/magento lreifs:multiquotes:rate-limit:status

# Reset rate limits
bin/magento lreifs:multiquotes:rate-limit:reset --ip=all

# Configure rate limits
bin/magento lreifs:multiquotes:rate-limit:config --requests-per-hour=1200
```

### System Maintenance
```bash
# Clean expired quotes
bin/magento lreifs:multiquotes:cleanup --expired

# Generate test data
bin/magento lreifs:multiquotes:generate-test-data --count=50

# Validate system integrity
bin/magento lreifs:multiquotes:validate

# Performance report
bin/magento lreifs:multiquotes:performance-report
```

---

## Error Handling

### Authentication Errors (401-403)
```json
401 Unauthorized
{
  "error": true,
  "message": "Consumer is not authorized to access %resources",
  "parameters": { "resources": "Lreifs_Multiquotes::quotes" }
}

403 Forbidden
{
  "error": true,
  "message": "Access denied. Admin role required for this operation."
}
```
**Solution:** Ensure you include a valid admin token in the Authorization header and verify ACL permissions.

### Validation Errors (422)
```json
422 Validation Failed
{
  "error": true,
  "message": "Validation failed",
  "details": [
    {
      "field": "customer_id",
      "message": "Customer ID is required and must be a valid integer"
    },
    {
      "field": "items",
      "message": "At least one item is required"
    }
  ]
}
```
**Solution:** Check all required fields and data types in your request.

### Rate Limiting (429)
```json
429 Too Many Requests
{
  "error": true,
  "message": "Rate limit exceeded. Try again in 3600 seconds.",
  "retry_after": 3600,
  "limit": 1000,
  "remaining": 0
}
```
**Solution:** Wait for the rate limit to reset or contact support for higher limits.

### Resource Not Found (404)
```json
404 Quote Not Found
{
  "error": true,
  "message": "Quote with ID 999 not found"
}
```
**Solution:** Verify the quote ID exists and is accessible to your account.

---

## Testing & Debugging Tips

### API Testing
Use the CLI commands to test endpoints quickly without setting up external tools.
```bash
bin/magento lreifs:multiquotes:test-api --endpoint=quotes --method=get
```

### Debug Mode
Enable debug logging to see detailed API request/response information.
```bash
bin/magento config:set multiquotes/debug/enabled 1
bin/magento cache:flush
```

### Performance
Monitor API performance and rate limiting with built-in metrics.
```bash
bin/magento lreifs:multiquotes:performance-report --last-hours=24
```

---

**Need Help?** Use the CLI commands for quick testing or check the system logs for detailed error information.

*This documentation reflects the actual implemented endpoints. Last updated: October 2025*
