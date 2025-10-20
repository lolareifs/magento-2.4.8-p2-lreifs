<?php
use Magento\Framework\App\Bootstrap;

require 'app/bootstrap.php';

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$obj = $bootstrap->getObjectManager();

$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

$productFactory = $obj->get('Magento\Catalog\Model\ProductFactory');
$storeManager = $obj->get('Magento\Store\Model\StoreManagerInterface');

function createProduct($sku, $name, $price) {
  global $productFactory, $storeManager;
  $product = $productFactory->create();
  $product->setSku($sku);
  $product->setName($name);
  $product->setAttributeSetId(4); // Default attribute set
  $product->setStatus(1);
  $product->setTypeId('simple');
  $product->setVisibility(4);
  $product->setPrice($price);
  $product->setWebsiteIds([$storeManager->getWebsite()->getId()]);
  $product->setStockData([
    'use_config_manage_stock' => 0,
    'manage_stock' => 1,
    'is_in_stock' => 1,
    'qty' => 100
  ]);
  $product->setDescription('Producto de prueba para multiquotes');
  $product->setShortDescription('Producto de prueba');
  $product->save();
  echo "Producto {$sku} creado\n";
}

createProduct('lreifs-test-product-001', 'Producto Test 001', 99.99);
createProduct('lreifs-test-product-002', 'Producto Test 002', 149.50);
