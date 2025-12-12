<?php
/**
 * Endpoint AJAX directo para añadir al carrito
 * Sin usar controladores de PrestaShop para evitar warnings
 */

// Deshabilitar errores que contaminen JSON
ini_set('display_errors', 0);
error_reporting(0);

// Limpiar cualquier output previo
if (ob_get_level()) ob_end_clean();

// Headers JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Cargar PrestaShop
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');

try {
    // Validar que sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die(json_encode(['success' => false, 'error' => 'Metodo no permitido']));
    }
    
    // Validar usuario logueado
    $context = Context::getContext();
    if (!$context->customer->isLogged()) {
        die(json_encode(['success' => false, 'error' => 'Debes iniciar sesion']));
    }
    
    // Obtener datos
    $productId = (int)Tools::getValue('product_id');
    
    if ($productId <= 0) {
        die(json_encode(['success' => false, 'error' => 'Producto invalido']));
    }
    
    // Obtener o crear carrito
    $cart = $context->cart;
    
    if (!Validate::isLoadedObject($cart) || !$cart->id) {
        $cart = new Cart();
        $cart->id_customer = (int)$context->customer->id;
        $cart->id_address_delivery = (int)Address::getFirstCustomerAddressId($context->customer->id);
        $cart->id_address_invoice = (int)Address::getFirstCustomerAddressId($context->customer->id);
        $cart->id_lang = (int)$context->language->id;
        $cart->id_currency = (int)$context->currency->id;
        $cart->id_carrier = 0;
        
        if (!$cart->add()) {
            die(json_encode(['success' => false, 'error' => 'Error al crear carrito']));
        }
        
        $context->cart = $cart;
        $context->cookie->id_cart = (int)$cart->id;
        $context->cookie->write();
    }
    
    // Añadir producto
    $result = $cart->updateQty(1, $productId);
    
    if (!$result) {
        die(json_encode(['success' => false, 'error' => 'Error al anadir producto']));
    }
    
    // Obtener datos del terreno desde POST
    $latitude = (float)Tools::getValue('latitude');
    $longitude = (float)Tools::getValue('longitude');
    $areaKm2 = (float)Tools::getValue('area_km2');
    $shapeType = pSQL(Tools::getValue('shape_type'));
    $fileSizeMB = (float)Tools::getValue('file_size_mb');
    $fingerprint = pSQL(Tools::getValue('fingerprint'));
    
    // Obtener nombre del producto
    $product = new Product($productId, false, $context->language->id);
    $productName = $product->name;
    
    // Guardar datos del terreno en base de datos
    try {
        // Crear tabla si no existe
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'arc3d_terrain_data` (
            `id_terrain` INT(11) NOT NULL AUTO_INCREMENT,
            `id_cart` INT(11) NOT NULL,
            `id_order` INT(11) DEFAULT NULL,
            `id_product` INT(11) NOT NULL,
            `product_name` VARCHAR(255) NOT NULL,
            `latitude` DECIMAL(10, 6) NOT NULL,
            `longitude` DECIMAL(10, 6) NOT NULL,
            `area_km2` DECIMAL(10, 2) NOT NULL,
            `shape_type` VARCHAR(50) NOT NULL,
            `file_size_mb` DECIMAL(10, 2) DEFAULT NULL,
            `fingerprint` VARCHAR(100) DEFAULT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_terrain`),
            KEY `idx_cart` (`id_cart`),
            KEY `idx_order` (`id_order`),
            KEY `idx_fingerprint` (`fingerprint`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        
        Db::getInstance()->execute($sql);
        
        // Insertar datos del terreno
        $insertSql = 'INSERT INTO `' . _DB_PREFIX_ . 'arc3d_terrain_data` 
            (`id_cart`, `id_product`, `product_name`, `latitude`, `longitude`, `area_km2`, `shape_type`, `file_size_mb`, `fingerprint`, `date_add`)
            VALUES (
                ' . (int)$cart->id . ',
                ' . (int)$productId . ',
                "' . pSQL($productName) . '",
                ' . (float)$latitude . ',
                ' . (float)$longitude . ',
                ' . (float)$areaKm2 . ',
                "' . pSQL($shapeType) . '",
                ' . (float)$fileSizeMB . ',
                "' . pSQL($fingerprint) . '",
                NOW()
            )';
        
        Db::getInstance()->execute($insertSql);
    } catch (Exception $dbError) {
        // Log error pero no fallar (datos opcionales)
        error_log('ArcGIS Terrain3D DB error: ' . $dbError->getMessage());
    }
    
    // URL del carrito
    $cartUrl = $context->link->getPageLink('cart', true);
    
    // Éxito
    die(json_encode([
        'success' => true,
        'message' => 'Producto anadido',
        'cart_url' => $cartUrl
    ]));
    
} catch (Exception $e) {
    die(json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]));
}
