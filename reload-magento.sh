#!/bin/bash
# reload-magento.sh
# Simplified Magento 2.4.8 reload script (EN + ES)

set -e

MAGENTO_ROOT="src/magento"  
BIN_MAGE="docker compose exec -T phpfpm php bin/magento"
OWNER="app"

echo "🚀 Reloading Magento 2.4.8..."

# 1️⃣ Ensure developer mode
CURRENT_MODE=$($BIN_MAGE deploy:mode:show | awk '{print $2}')
if [ "$CURRENT_MODE" != "developer" ]; then
    echo "🔧 Switching Magento to developer mode..."
    $BIN_MAGE deploy:mode:set developer
fi

# 1.5️⃣ Ask about setup:upgrade
read -p "Do you want to run setup:upgrade before compilation? (y/n): " SETUP_UPGRADE
if [[ "$SETUP_UPGRADE" =~ ^[Yy]$ ]]; then
    echo "⚙️ Running setup:upgrade..."
    $BIN_MAGE setup:upgrade
fi

# 2️⃣ Compile code and proxies if necessary
if [ -d "$MAGENTO_ROOT/generated/metadata" ]; then
    echo "⚡ Generated code exists, skipping compilation..."
else
    echo "⚙️ Generating code..."
    $BIN_MAGE s:d:c
fi

# 3️⃣ Deploy static content (EN + ES)
echo "🎨 Deploying static content for English and Spanish..."
$BIN_MAGE s:s:d -f es_ES en_US

# 4️⃣ Ask about reindex
read -p "Do you want to reindex? (y/n): " REINDEX_CHOICE
if [[ "$REINDEX_CHOICE" =~ ^[Yy]$ ]]; then
    echo "📦 Reindexing..."
    $BIN_MAGE indexer:reindex
fi

# 5️⃣ Ask about cron
read -p "Do you want to run cron jobs? (y/n): " CRON_CHOICE
if [[ "$CRON_CHOICE" =~ ^[Yy]$ ]]; then
    echo "⏱ Running cron jobs..."
    $BIN_MAGE cron:run || true
fi

# 6️⃣ Clear preprocessed view files
echo "🗑 Clearing view preprocessed files..."
rm -rf $MAGENTO_ROOT/var/view_preprocessed/*

# 7️⃣ Final cache clean
echo "🧹 Cleaning caches..."
$BIN_MAGE cache:clean
$BIN_MAGE cache:flush

# 8️⃣ Ask about resetting permissions and owner
read -p "Do you want to reset permissions and owner? (y/n): " PERM_CHOICE
if [[ "$PERM_CHOICE" =~ ^[Yy]$ ]]; then
    echo "🔑 Setting file permissions and owner..."
    find $MAGENTO_ROOT -type f -exec chmod 644 {} \;
    find $MAGENTO_ROOT -type d -exec chmod 755 {} \;
    chown -R $OWNER:$OWNER $MAGENTO_ROOT
fi

echo "✅ Magento reload complete. You can now test your changes."
