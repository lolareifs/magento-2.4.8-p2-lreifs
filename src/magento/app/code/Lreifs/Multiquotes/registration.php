<?php
/**
 * Lreifs Multiquotes Module v1.0.0 Registration
 * Enterprise-grade immutable quote management for Magento 2.4.8+
 * 
 * Features:
 * - Enhanced REST API with 8 endpoints and advanced filtering
 * - System-wide quote viewing for admins
 * - Comprehensive audit trails with IP tracking
 * - Enhanced CLI commands with optional parameters
 * - Postman Collection v1.0.0
 * 
 * Copyright © Lreifs All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Lreifs_Multiquotes', __DIR__);