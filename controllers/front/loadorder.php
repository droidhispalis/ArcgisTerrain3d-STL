<?php

class Arcgisterrain3dLoadorderModuleFrontController extends ModuleFrontController
{
    public $ajax = true; // Importante: marcar como controlador AJAX
    
    public function init()
    {
        parent::init();
        // Forzar modo AJAX
        $this->ajax = true;
    }
    
    public function displayAjax()
    {
        $this->processLoadOrder();
    }
    
    public function postProcess()
    {
        if (Tools::isSubmit('ajax') || $this->ajax) {
            $this->processLoadOrder();
        }
    }
    
    private function processLoadOrder()
    {
        // Log de depuración
        error_log('[ArcGIS LoadOrder] Petición recibida - POST: ' . print_r($_POST, true));
        error_log('[ArcGIS LoadOrder] GET: ' . print_r($_GET, true));
        
        // No verificar ajax=1 ya que viene por GET en la URL de PrestaShop

        // Verificar que es administrador
        $isAdmin = false;
        if (isset($this->context->employee) && $this->context->employee && $this->context->employee->id) {
            $isAdmin = true;
        } elseif ($this->context->customer->isLogged()) {
            $sql = 'SELECT id_employee FROM ' . _DB_PREFIX_ . 'employee WHERE email = "' . pSQL($this->context->customer->email) . '" AND active = 1';
            $employeeId = Db::getInstance()->getValue($sql);
            if ($employeeId) {
                $employee = new Employee($employeeId);
                if (Validate::isLoadedObject($employee)) {
                    $isAdmin = $employee->isSuperAdmin() || $employee->id_profile == 1;
                }
            }
        }

        if (!$isAdmin) {
            die(json_encode(array('success' => false, 'error' => 'Acceso denegado. Solo administradores')));
        }

        $orderInput = Tools::getValue('order_id');
        
        error_log('[ArcGIS LoadOrder] orderInput recibido: ' . $orderInput);

        if (!$orderInput || trim($orderInput) === '') {
            error_log('[ArcGIS LoadOrder] Error: orderInput vacío');
            die(json_encode(array('success' => false, 'error' => 'ID o referencia de pedido no especificado')));
        }

        // Intentar cargar pedido por ID numérico o por referencia alfanumérica
        $order = null;
        
        error_log('[ArcGIS LoadOrder] Buscando pedido con: ' . $orderInput);
        
        // Primero intentar como ID numérico
        if (is_numeric($orderInput)) {
            $orderId = (int)$orderInput;
            error_log('[ArcGIS LoadOrder] Intentando buscar por ID numérico: ' . $orderId);
            $order = new Order($orderId);
        }
        
        // Si no se encontró, buscar por referencia (ej: ZGSTEXUNV)
        if (!$order || !Validate::isLoadedObject($order)) {
            $orderReference = pSQL(trim($orderInput));
            $sql = 'SELECT id_order FROM ' . _DB_PREFIX_ . 'orders WHERE reference = "' . $orderReference . '"';
            error_log('[ArcGIS LoadOrder] Buscando por referencia SQL: ' . $sql);
            $orderId = (int)Db::getInstance()->getValue($sql);
            error_log('[ArcGIS LoadOrder] ID encontrado por referencia: ' . $orderId);
            
            if ($orderId > 0) {
                $order = new Order($orderId);
            }
        }
        
        error_log('[ArcGIS LoadOrder] Order loaded: ' . ($order && Validate::isLoadedObject($order) ? 'SI' : 'NO'));
        
        if (!$order || !Validate::isLoadedObject($order)) {
            error_log('[ArcGIS LoadOrder] Error: Pedido no encontrado');
            die(json_encode(array('success' => false, 'error' => 'Pedido no encontrado con ID/referencia: ' . $orderInput)));
        }

        // Verificar que el pedido estÃ¡ pagado
        $orderState = $order->getCurrentState();
        $stateObj = new OrderState($orderState);
        
        if ($stateObj->paid != 1) {
            die(json_encode(array('success' => false, 'error' => 'El pedido no ha sido pagado todavia')));
        }

        // Buscar datos del terreno en cookies del cliente
        $customer = new Customer($order->id_customer);
        
        // Intentar recuperar de la cookie del contexto actual (si el admin estÃ¡ en el navegador del cliente)
        $terrainData = null;
        
        if (isset($this->context->cookie->arc3d_orders)) {
            $orders = json_decode($this->context->cookie->arc3d_orders, true);
            if (is_array($orders)) {
                foreach ($orders as $data) {
                    if (isset($data['cart_id']) && $data['cart_id'] == $order->id_cart) {
                        $terrainData = $data;
                        break;
                    }
                }
            }
        }

        // Si no se encuentra en cookies, buscar en base de datos (guardado anteriormente)
        if (!$terrainData) {
            $tableName = _DB_PREFIX_ . 'arc3d_terrain_data';
            
            // Buscar primero por id_order
            $sql = 'SELECT * FROM `' . $tableName . '` WHERE id_order = ' . (int)$order->id . ' ORDER BY date_add DESC';
            error_log('[ArcGIS LoadOrder] SQL 1: ' . $sql);
            
            $result = Db::getInstance()->getRow($sql);
            
            // Si no encontró, buscar por id_cart
            if (!$result) {
                $sql = 'SELECT * FROM `' . $tableName . '` WHERE id_cart = ' . (int)$order->id_cart . ' ORDER BY date_add DESC';
                error_log('[ArcGIS LoadOrder] SQL 2: ' . $sql);
                $result = Db::getInstance()->getRow($sql);
            }
            
            error_log('[ArcGIS LoadOrder] Resultado BD: ' . print_r($result, true));
            
            if ($result) {
                $terrainData = array(
                    'product_id' => $result['id_product'],
                    'product_name' => $result['product_name'],
                    'latitude' => $result['latitude'],
                    'longitude' => $result['longitude'],
                    'area_km2' => $result['area_km2'],
                    'shape_type' => $result['shape_type'],
                    'file_size_mb' => $result['file_size_mb']
                );
                error_log('[ArcGIS LoadOrder] Terrain data encontrado: ' . print_r($terrainData, true));
            }
        }

        if (!$terrainData) {
            error_log('[ArcGIS LoadOrder] Error: No se encontraron datos del terreno');
            die(json_encode(array('success' => false, 'error' => 'No se encontraron datos del terreno para este pedido')));
        }
        
        error_log('[ArcGIS LoadOrder] Éxito - Devolviendo datos');

        die(json_encode(array(
            'success' => true,
            'data' => $terrainData,
            'order_reference' => $order->reference,
            'customer_name' => $customer->firstname . ' ' . $customer->lastname
        )));
    }
}
