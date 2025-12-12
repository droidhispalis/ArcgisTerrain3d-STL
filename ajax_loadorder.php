<?php
/**
 * Endpoint AJAX directo para cargar pedidos
 * Alternativa a controlador si falla
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
    // Log de depuración
    error_log('[ArcGIS LoadOrder Direct] Petición recibida');
    error_log('[ArcGIS LoadOrder Direct] POST: ' . print_r($_POST, true));
    error_log('[ArcGIS LoadOrder Direct] GET: ' . print_r($_GET, true));
    
    // Validar que sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        die(json_encode(['success' => false, 'error' => 'Método no permitido']));
    }
    
    $context = Context::getContext();
    
    // Verificar que es administrador
    $isAdmin = false;
    if (isset($context->employee) && $context->employee && $context->employee->id) {
        $isAdmin = true;
    } elseif ($context->customer->isLogged()) {
        $sql = 'SELECT id_employee FROM ' . _DB_PREFIX_ . 'employee WHERE email = "' . pSQL($context->customer->email) . '" AND active = 1';
        $employeeId = Db::getInstance()->getValue($sql);
        if ($employeeId) {
            $employee = new Employee($employeeId);
            if (Validate::isLoadedObject($employee)) {
                $isAdmin = $employee->isSuperAdmin() || $employee->id_profile == 1;
            }
        }
    }

    if (!$isAdmin) {
        error_log('[ArcGIS LoadOrder Direct] Error: No es admin');
        die(json_encode(['success' => false, 'error' => 'Acceso denegado. Solo administradores']));
    }

    $orderInput = Tools::getValue('order_id');
    
    error_log('[ArcGIS LoadOrder Direct] orderInput: ' . $orderInput);
    
    if (!$orderInput || trim($orderInput) === '') {
        die(json_encode(['success' => false, 'error' => 'ID o referencia de pedido no especificado']));
    }

    // Intentar cargar pedido por ID numérico o por referencia alfanumérica
    $order = null;
    
    error_log('[ArcGIS LoadOrder Direct] Buscando pedido: ' . $orderInput);
    
    // Primero intentar como ID numérico
    if (is_numeric($orderInput)) {
        $orderId = (int)$orderInput;
        error_log('[ArcGIS LoadOrder Direct] Buscando por ID: ' . $orderId);
        $order = new Order($orderId);
    }
    
    // Si no se encontró, buscar por referencia (ej: ZGSTEXUNV)
    if (!$order || !Validate::isLoadedObject($order)) {
        $orderReference = pSQL(trim($orderInput));
        $sql = 'SELECT id_order FROM ' . _DB_PREFIX_ . 'orders WHERE reference = "' . $orderReference . '"';
        error_log('[ArcGIS LoadOrder Direct] SQL: ' . $sql);
        $orderId = (int)Db::getInstance()->getValue($sql);
        error_log('[ArcGIS LoadOrder Direct] ID encontrado: ' . $orderId);
        
        if ($orderId > 0) {
            $order = new Order($orderId);
        }
    }
    
    if (!$order || !Validate::isLoadedObject($order)) {
        error_log('[ArcGIS LoadOrder Direct] Error: Pedido no encontrado');
        die(json_encode(['success' => false, 'error' => 'Pedido no encontrado con ID/referencia: ' . $orderInput]));
    }
    
    error_log('[ArcGIS LoadOrder Direct] Pedido cargado OK, ID: ' . $order->id);
    error_log('[ArcGIS LoadOrder Direct] id_cart del pedido: ' . $order->id_cart);

    // Verificar que el pedido está pagado
    $orderState = $order->getCurrentState();
    $stateObj = new OrderState($orderState);
    
    if ($stateObj->paid != 1) {
        die(json_encode(['success' => false, 'error' => 'El pedido no ha sido pagado todavía']));
    }

    // Verificar si la tabla existe
    $tableName = _DB_PREFIX_ . 'arc3d_terrain_data';
    $tableExists = Db::getInstance()->executeS("SHOW TABLES LIKE '" . $tableName . "'");
    
    if (empty($tableExists)) {
        // Crear tabla
        $createSql = "CREATE TABLE IF NOT EXISTS `" . $tableName . "` (
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
            `geometry_json` LONGTEXT DEFAULT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_terrain`),
            KEY `id_cart` (`id_cart`),
            KEY `id_order` (`id_order`),
            KEY `fingerprint` (`fingerprint`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4";
        
        if (!Db::getInstance()->execute($createSql)) {
            die(json_encode(['success' => false, 'error' => 'Error creando tabla: ' . Db::getInstance()->getMsgError()]));
        }
    }

    // Buscar datos del terreno - primero por id_order, luego por id_cart
    error_log('[ArcGIS LoadOrder Direct] Table name: ' . $tableName);
    error_log('[ArcGIS LoadOrder Direct] Buscando: id_order=' . $order->id . ', id_cart=' . $order->id_cart);
    
    // Intentar primero por id_order
    $sql = 'SELECT * FROM `' . $tableName . '` WHERE id_order = ' . (int)$order->id . ' ORDER BY date_add DESC';
    error_log('[ArcGIS LoadOrder Direct] SQL 1: ' . $sql);
    
    $result = Db::getInstance()->getRow($sql);
    
    // Si no encontró, buscar por id_cart
    if (!$result) {
        $sql = 'SELECT * FROM `' . $tableName . '` WHERE id_cart = ' . (int)$order->id_cart . ' ORDER BY date_add DESC';
        error_log('[ArcGIS LoadOrder Direct] SQL 2: ' . $sql);
        $result = Db::getInstance()->getRow($sql);
    }
    
    if ($result === false) {
        $dbError = Db::getInstance()->getMsgError();
        error_log('[ArcGIS LoadOrder Direct] DB Error: ' . $dbError);
        die(json_encode(['success' => false, 'error' => 'Error SQL: ' . $dbError]));
    }
    
    error_log('[ArcGIS LoadOrder Direct] Resultado: ' . print_r($result, true));
    
    if (!$result) {
        die(json_encode(['success' => false, 'error' => 'No se encontraron datos del terreno. id_order=' . $order->id . ', id_cart=' . $order->id_cart]));
    }
    
    // Si encontró por id_cart pero id_order es NULL, actualizar
    if ($result && empty($result['id_order'])) {
        $updateSql = 'UPDATE ' . _DB_PREFIX_ . 'arc3d_terrain_data SET id_order = ' . (int)$order->id . ' WHERE id_terrain = ' . (int)$result['id_terrain'];
        error_log('[ArcGIS LoadOrder Direct] Actualizando id_order: ' . $updateSql);
        Db::getInstance()->execute($updateSql);
        $result['id_order'] = $order->id;
    }
    
    $customer = new Customer($order->id_customer);
    
    error_log('[ArcGIS LoadOrder Direct] Éxito - devolviendo datos');
    
    // Decodificar geometry_json antes de enviarlo (evitar doble escape)
    $geometryJson = null;
    if (!empty($result['geometry_json'])) {
        error_log('[ArcGIS LoadOrder Direct] geometry_json raw: ' . $result['geometry_json']);
        
        // Intentar decodificar directamente
        $geometryJson = json_decode($result['geometry_json'], true);
        
        // Si falla, intentar con stripslashes (por si está doblemente escapado)
        if ($geometryJson === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log('[ArcGIS LoadOrder Direct] Error json_decode: ' . json_last_error_msg());
            $unescaped = stripslashes($result['geometry_json']);
            error_log('[ArcGIS LoadOrder Direct] Intentando con stripslashes: ' . $unescaped);
            $geometryJson = json_decode($unescaped, true);
            
            if ($geometryJson === null) {
                error_log('[ArcGIS LoadOrder Direct] Aún falla después de stripslashes: ' . json_last_error_msg());
            }
        }
        
        if ($geometryJson !== null) {
            error_log('[ArcGIS LoadOrder Direct] ✓ geometry_json decodificado OK');
        }
    }
    
    // Enviar geometry_json para reconstruir geometría exacta (círculo o polígono)
    die(json_encode([
        'success' => true,
        'data' => [
            'product_id' => (int)$result['id_product'],
            'product_name' => $result['product_name'],
            'latitude' => $result['latitude'],
            'longitude' => $result['longitude'],
            'area_km2' => $result['area_km2'],
            'shape_type' => $result['shape_type'],
            'file_size_mb' => $result['file_size_mb'],
            'geometry_json' => $geometryJson  // Ya decodificado como objeto
        ],
        'order_reference' => $order->reference,
        'customer_name' => $customer->firstname . ' ' . $customer->lastname
    ]));
    
} catch (Exception $e) {
    error_log('[ArcGIS LoadOrder Direct] Exception: ' . $e->getMessage());
    die(json_encode([
        'success' => false,
        'error' => 'Error interno: ' . $e->getMessage()
    ]));
}
