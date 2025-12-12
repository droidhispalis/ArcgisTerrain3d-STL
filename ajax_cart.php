<?php
/**
 * AJAX endpoint - Añadir al carrito Y guardar datos del terreno
 */

ini_set('display_errors', 0);
error_reporting(0);
if (ob_get_level()) ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');

try {
    $context = Context::getContext();
    
    if (!$context->customer->isLogged()) {
        die(json_encode(['success' => false, 'error' => 'No logueado']));
    }
    
    $productId = (int)Tools::getValue('product_id');
    if ($productId <= 0) {
        die(json_encode(['success' => false, 'error' => 'ID invalido']));
    }
    
    // Obtener datos del terreno
    $latitude = (float)Tools::getValue('latitude');
    $longitude = (float)Tools::getValue('longitude');
    $areaKm2 = (float)Tools::getValue('area_km2');
    $shapeType = pSQL(Tools::getValue('shape_type'));
    $fileSizeMb = (float)Tools::getValue('file_size_mb');
    $fingerprint = pSQL(Tools::getValue('fingerprint'));
    $geometryJson = pSQL(Tools::getValue('geometry_json'));
    
    error_log('[ARC3D Cart] Geometry JSON recibido: ' . substr($geometryJson, 0, 200));
    
    $cart = $context->cart;
    if (!$cart->id) {
        $cart = new Cart();
        $cart->id_customer = $context->customer->id;
        $cart->id_address_delivery = Address::getFirstCustomerAddressId($context->customer->id);
        $cart->id_address_invoice = Address::getFirstCustomerAddressId($context->customer->id);
        $cart->id_lang = $context->language->id;
        $cart->id_currency = $context->currency->id;
        $cart->id_carrier = 0;
        $cart->add();
        $context->cookie->id_cart = $cart->id;
        $context->cookie->write();
    }
    
    $cart->updateQty(1, $productId);
    
    // Verificar si la tabla existe, si no crearla
    $tableExists = Db::getInstance()->executeS("SHOW TABLES LIKE '" . _DB_PREFIX_ . "arc3d_terrain_data'");
    
    if (empty($tableExists)) {
        $sql = "CREATE TABLE `" . _DB_PREFIX_ . "arc3d_terrain_data` (
            `id_terrain` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_cart` INT(11) UNSIGNED NOT NULL,
            `id_order` INT(11) UNSIGNED DEFAULT NULL,
            `id_product` INT(11) UNSIGNED NOT NULL,
            `product_name` VARCHAR(255) DEFAULT NULL,
            `latitude` DECIMAL(10,7) NOT NULL,
            `longitude` DECIMAL(10,7) NOT NULL,
            `area_km2` DECIMAL(10,2) NOT NULL,
            `shape_type` VARCHAR(50) DEFAULT NULL,
            `file_size_mb` DECIMAL(10,2) DEFAULT NULL,
            `fingerprint` VARCHAR(255) DEFAULT NULL,
            `geometry_json` MEDIUMTEXT DEFAULT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_terrain`),
            KEY `id_cart` (`id_cart`),
            KEY `id_order` (`id_order`),
            KEY `fingerprint` (`fingerprint`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4";
        
        if (!Db::getInstance()->execute($sql)) {
            die(json_encode(['success' => false, 'error' => 'Error creando tabla: ' . Db::getInstance()->getMsgError()]));
        }
    }
    
    // Obtener nombre del producto
    $product = new Product($productId, false, $context->language->id);
    $productName = $product->name;
    
    // Insertar datos del terreno
    $insertSql = "INSERT INTO `" . _DB_PREFIX_ . "arc3d_terrain_data` 
        (`id_cart`, `id_order`, `id_product`, `product_name`, `latitude`, `longitude`, 
         `area_km2`, `shape_type`, `file_size_mb`, `fingerprint`, `geometry_json`, `date_add`)
        VALUES 
        (" . (int)$cart->id . ", NULL, " . (int)$productId . ", '" . pSQL($productName) . "', 
         " . (float)$latitude . ", " . (float)$longitude . ", " . (float)$areaKm2 . ", 
         '" . pSQL($shapeType) . "', " . (float)$fileSizeMb . ", '" . pSQL($fingerprint) . "', 
         '" . pSQL($geometryJson) . "', NOW())";
    
    error_log('[ARC3D Cart] SQL: ' . $insertSql);
    
    $result = Db::getInstance()->execute($insertSql);
    
    if (!$result) {
        $error = Db::getInstance()->getMsgError();
        error_log('[ARC3D Cart] Error SQL: ' . $error);
        die(json_encode(['success' => false, 'error' => 'Error BD: ' . $error, 'sql' => $insertSql]));
    }
    
    error_log('[ARC3D Cart] Registro guardado OK, id_cart: ' . $cart->id);
    
    die(json_encode([
        'success' => true,
        'message' => 'OK',
        'cart_url' => $context->link->getPageLink('cart', true)
    ]));
    
} catch (Exception $e) {
    die(json_encode(['success' => false, 'error' => $e->getMessage()]));
}
