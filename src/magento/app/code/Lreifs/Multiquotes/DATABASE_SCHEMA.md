# Lreifs Multiquotes - Database Schema Documentation

## 📊 Overview

This document describes the complete database schema of the **Lreifs_Multiquotes** module, implemented to manage immutable quotes with comprehensive enterprise auditing.

## 🗄️ Created Tables

### 1. **`lreifs_quote_extension`** - Main Table

Consolidated table that extends Magento quotes with immutable functionality and advanced enterprise management.

#### Column Structure:

| Field | Type | Null | Key | Default | Description |
|-------|------|------|-------|---------|-------------|
| `entity_id` | int(10) unsigned | NO | PRI | auto_increment | Unique extension ID |
| `quote_id` | int(10) unsigned | NO | MUL | NULL | FK to Magento `quote` table |
| `customer_id` | int(10) unsigned | YES | MUL | NULL | FK to `customer_entity` table |
| `extension_type` | varchar(50) | NO | | 'immutable' | Applied extension type |
| `status` | varchar(20) | NO | MUL | 'draft' | Current status (draft, active, locked, expired, converted) |
| `is_immutable` | tinyint(1) | NO | MUL | 0 | Immutability activation flag |
| `locked_at` | timestamp | YES | | NULL | Lock timestamp |
| `locked_by_user_id` | varchar(255) | YES | | NULL | User who locked the quote |
| `immutable_hash` | varchar(64) | YES | UNI | NULL | Unique integrity verification hash |
| `business_rules` | text | YES | | NULL | Business rules in JSON format |
| `protection_settings` | longtext | YES | | NULL | Protection configuration in JSON |
| `allow_item_modification` | tinyint(1) | NO | | 0 | Allow item modifications |
| `allow_price_modification` | tinyint(1) | NO | | 0 | Allow price modifications |
| `allow_quantity_modification` | tinyint(1) | NO | | 0 | Allow quantity modifications |
| `expires_at` | timestamp | YES | MUL | NULL | Expiration date |
| `expiration_notification_sent` | smallint(5) unsigned | NO | | 0 | Expiration notifications sent |
| `version` | int(10) unsigned | NO | | 1 | Version number for versioning |
| `parent_quote_extension_id` | int(10) unsigned | YES | MUL | NULL | FK for hierarchical versioning |
| `change_log` | text | YES | | NULL | Log of changes made |
| `name` | varchar(255) | YES | | NULL | Descriptive quote name |
| `description` | text | YES | | NULL | Detailed description |
| `notes` | text | YES | | NULL | Additional notes |
| `metadata` | longtext | YES | | NULL | Additional metadata in JSON |
| `created_by_user_id` | varchar(255) | YES | | NULL | Creator user |
| `updated_by_user_id` | varchar(255) | YES | | NULL | Last modifier user |
| `created_at` | timestamp | NO | MUL | current_timestamp() | Creation date |
| `updated_at` | timestamp | NO | | current_timestamp() | Last update date |
| `converted_to_order_id` | int(10) unsigned | YES | MUL | NULL | FK to converted order |
| `converted_at` | timestamp | YES | | NULL | Order conversion date |

#### Implemented Indexes:

- **PRIMARY KEY**: `entity_id`
- **UNIQUE INDEX**: `immutable_hash` (integrity verification)
- **UNIQUE COMPOSITE**: `quote_id + extension_type` (one extension per type per quote)
- **INDEX**: `quote_id` (quote queries)
- **INDEX**: `customer_id` (customer queries)
- **INDEX**: `status` (status queries)
- **INDEX**: `is_immutable` (immutable filter)
- **INDEX**: `expires_at` (expiration management)
- **INDEX**: `created_at` (temporal sorting)
- **INDEX**: `converted_to_order_id` (conversion tracking)
- **FOREIGN KEY**: `parent_quote_extension_id` (versioning)

---

### 2. **`lreifs_quote_extension_audit`** - Audit Table

Complete audit table for compliance and traceability of all changes made to quote extensions.

#### Column Structure:

| Field | Type | Null | Key | Default | Description |
|-------|------|------|-------|---------|-------------|
| `audit_id` | int(10) unsigned | NO | PRI | auto_increment | Unique audit record ID |
| `quote_extension_id` | int(10) unsigned | NO | MUL | NULL | FK to `lreifs_quote_extension` |
| `action` | varchar(50) | NO | MUL | NULL | Action performed (create, update, delete, lock, unlock, convert) |
| `user_id` | varchar(255) | YES | MUL | NULL | ID of user who performed the action |
| `user_type` | varchar(50) | YES | | NULL | User type (admin, customer, system) |
| `old_values` | text | YES | | NULL | Previous values in JSON format |
| `new_values` | text | YES | | NULL | New values in JSON format |
| `context` | text | YES | | NULL | Additional operation context |
| `ip_address` | varchar(45) | YES | | NULL | User IP address |
| `user_agent` | varchar(500) | YES | | NULL | Browser user agent |
| `created_at` | timestamp | NO | MUL | current_timestamp() | Event timestamp |

#### Audit Indexes:

- **PRIMARY KEY**: `audit_id`
- **INDEX**: `quote_extension_id` (queries by extension)
- **INDEX**: `action` (queries by action type)
- **INDEX**: `user_id` (queries by user)
- **INDEX**: `created_at` (temporal queries)

---

## 🔗 Database Relationships

### Implemented Foreign Keys:

1. **`lreifs_quote_extension.quote_id`** → **`quote.entity_id`**
   - Main relationship with Magento quotes
   - ON DELETE RESTRICT (integrity protection)

2. **`lreifs_quote_extension.customer_id`** → **`customer_entity.entity_id`**
   - Relationship with Magento customers
   - ON DELETE SET NULL (preserve historical data)

3. **`lreifs_quote_extension.converted_to_order_id`** → **`sales_order.entity_id`**
   - Order conversion tracking
   - ON DELETE SET NULL (preserve historical data)

4. **`lreifs_quote_extension.parent_quote_extension_id`** → **`lreifs_quote_extension.entity_id`**
   - Self-reference for versioning
   - ON DELETE SET NULL (maintain version chain)

5. **`lreifs_quote_extension_audit.quote_extension_id`** → **`lreifs_quote_extension.entity_id`**
   - Complete change auditing
   - ON DELETE CASCADE (remove audit with main record)

---

## 📈 Enterprise Features

### ✅ Implemented Functionalities:

- **Configurable Immutability**: Granular modification control
- **Complete Auditing**: All changes recorded with context
- **Advanced Versioning**: Hierarchical version support
- **Expiration Management**: Automatic expiration control
- **Extensible Metadata**: JSON fields for custom data
- **Referential Integrity**: Foreign keys with defined policies
- **Query Optimization**: Strategic indexes for performance
- **Complete Traceability**: From creation to order conversion

### 🎯 Supported Use Cases:

1. **Immutable Quotes**: Once activated, cannot be modified
2. **Quote Versioning**: Create new versions while preserving history
3. **Compliance Auditing**: Complete record for legal audits
4. **Expiration Management**: Automatic quote lifecycle control
5. **Order Conversion**: Complete sales process tracking
6. **Behavior Analysis**: Data for analytics and reporting

---

## 🚀 Performance and Scalability

### Implemented Optimizations:

- **Composite Indexes**: For efficient multi-criteria queries
- **Temporal Partitioning**: Ready for date-based partitioning implementation
- **JSON Fields**: Flexible structure without performance impact
- **Surrogate Keys**: Numeric IDs for efficient JOINs
- **Optimal Normalization**: Balance between normalization and performance

### Expected Metrics:

- **Quote Queries**: < 50ms with indexes
- **Audit Insertion**: < 10ms batch processing
- **List Queries**: < 100ms with pagination
- **Referential Integrity**: 0% inconsistencies

---

## 📋 Verification Commands

```sql
-- Verify created tables
SHOW TABLES LIKE 'lreifs%';

-- Main table structure
DESCRIBE lreifs_quote_extension;

-- Audit table structure
DESCRIBE lreifs_quote_extension_audit;

-- Verify indexes
SHOW INDEX FROM lreifs_quote_extension;
SHOW INDEX FROM lreifs_quote_extension_audit;

-- Verify foreign keys
SELECT * FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_NAME IN ('lreifs_quote_extension', 'lreifs_quote_extension_audit') 
AND REFERENCED_TABLE_NAME IS NOT NULL;
```

---

*Documentation generated for Lreifs_Multiquotes module v1.0.0.0*
*Date: October 2025*
*Includes comprehensive Postman testing suite v1.0.0 with 25+ automated tests*