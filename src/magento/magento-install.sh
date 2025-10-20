#!/bin/bash
# magento-install.sh
# Complete Magento 2.4.8 installation script with 2FA disabled and cleanup

set -e

echo "🚀 Starting complete Magento 2.4.8 installation..."

# Check if containers are running
echo "📋 Checking if containers are running..."
if ! docker compose ps | grep -q "Up"; then
    echo "⚠️  Some containers are not running. Starting containers..."
    docker compose up -d
    echo "⏳ Waiting for database to be ready..."
    sleep 15
fi

echo "🗑️ Cleaning any existing installation files..."
docker compose exec phpfpm rm -f app/etc/env.php || true
docker compose exec phpfpm rm -f app/etc/config.php || true

echo "🔑 Creando auth.json con credenciales de acceso a Magento Marketplace..."
cat <<EOF > auth.json
{
  "http-basic": {
    "repo.magento.com": {
      "username": "8cbf8dcea67d8fc17424deae73c5c212",
      "password": "8b61424ca6f008e765310a22b2b48483"
    }
  }
}
EOF

echo "🧹 Cleaning cache and generated files..."
docker compose exec phpfpm rm -rf var/cache/* || true
docker compose exec phpfpm rm -rf var/page_cache/* || true
docker compose exec phpfpm rm -rf generated/* || true
docker compose exec phpfpm rm -rf pub/static/* || true
docker compose exec phpfpm rm -rf var/view_preprocessed/* || true

echo "🔧 Disabling Two-Factor Authentication modules..."
docker compose exec phpfpm php bin/magento module:disable Magento_AdminAdobeImsTwoFactorAuth Magento_TwoFactorAuth || true

echo "⚙️ Installing Magento..."
composer install

docker compose exec phpfpm php bin/magento setup:install \
  --base-url=http://local.magento/ \
  --db-host=db:3306 \
  --db-name=magento \
  --db-user=magento \
  --db-password=magento \
  --admin-firstname=Admin \
  --admin-lastname=User \
  --admin-email=lolareifscarmona@gmail.com \
  --admin-user=admin \
  --admin-password=XXX1234 \
  --language=es_ES \
  --currency=EUR \
  --timezone=Europe/Madrid \
  --use-rewrites=1 \
  --search-engine=opensearch \
  --opensearch-host=opensearch \
  --opensearch-port=9200 \
  --use-secure=0 \
  --use-secure-admin=0 \
  --cleanup-database \
  --skip-db-validation

echo "🔧 Fixing InventorySales default website issue..."
docker compose exec phpfpm php -r "
\$conn = new PDO('mysql:host=db:3306;dbname=magento', 'magento', 'magento');
\$conn->exec('INSERT IGNORE INTO store_website (website_id, code, name, sort_order, default_group_id, is_default) VALUES (0, \"admin\", \"Admin\", 0, 0, 0)');
\$conn->exec('INSERT IGNORE INTO store_website (website_id, code, name, sort_order, default_group_id, is_default) VALUES (1, \"base\", \"Main Website\", 0, 1, 1)');
\$conn->exec('INSERT IGNORE INTO store_group (group_id, website_id, name, root_category_id, default_store_id) VALUES (0, 0, \"Default\", 0, 0)');
\$conn->exec('INSERT IGNORE INTO store_group (group_id, website_id, name, root_category_id, default_store_id) VALUES (1, 1, \"Main Website Store\", 2, 1)');
\$conn->exec('INSERT IGNORE INTO store (store_id, code, website_id, group_id, name, sort_order, is_active) VALUES (0, \"admin\", 0, 0, \"Admin\", 0, 1)');
\$conn->exec('INSERT IGNORE INTO store (store_id, code, website_id, group_id, name, sort_order, is_active) VALUES (1, \"default\", 1, 1, \"Default Store View\", 0, 1)');
echo \"Default website structure created successfully\n\";
"

echo "🎯 Setting up developer mode..."
docker compose exec phpfpm php bin/magento deploy:mode:set developer

echo "🎨 Deploying static content for Spanish and English..."
docker compose exec phpfpm php bin/magento setup:static-content:deploy es_ES en_US -f

echo "📦 Running indexers..."
docker compose exec phpfpm php bin/magento indexer:reindex

echo "🛍️ Creating test products..."
docker compose exec phpfpm php create-test-products.php

echo "🧹 Final cache clear..."
docker compose exec phpfpm php bin/magento cache:clean
docker compose exec phpfpm php bin/magento cache:flush

echo "✅ Magento installation completed successfully!"
echo ""
echo "🌐 Access URLs:"
echo "   Frontend: http://localhost/"

# Get the actual admin URL
ADMIN_URL=$(docker compose exec phpfpm php bin/magento info:adminuri 2>/dev/null | grep -o '/admin_[a-zA-Z0-9]*' || echo "/admin")
echo "   Admin Panel: http://localhost${ADMIN_URL}"
echo ""
echo "🔑 Admin Credentials:"
echo "   Username: admin"
echo "   Password: XXX1234"
echo ""
echo "�️ Test Products Created:"
echo "   - lreifs-test-product-001 (€99.99)"
echo "   - lreifs-test-product-002 (€149.50)"
echo ""
echo "�💡 Notes:"
echo "   - Two-Factor Authentication has been disabled for easier development"
echo "   - Test products are ready for multiquotes module testing"
