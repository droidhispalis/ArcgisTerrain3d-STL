<?php

class Arcgisterrain3dLoadorderModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if (!Tools::isSubmit('ajax')) {
            $this->ajaxDie(json_encode(array('success' => false, 'error' => 'Solo AJAX')));
        }

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
            $this->ajaxDie(json_encode(array('success' => false, 'error' => 'Acceso denegado. Solo administradores')));
        }

        $orderId = (int)Tools::getValue('order_id');

        if ($orderId <= 0) {
            $this->ajaxDie(json_encode(array('success' => false, 'error' => 'ID de pedido no valido')));
        }

        // Cargar pedido
        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            $this->ajaxDie(json_encode(array('success' => false, 'error' => 'Pedido no encontrado')));
        }

        // Verificar que el pedido estÃ¡ pagado
        $orderState = $order->getCurrentState();
        $stateObj = new OrderState($orderState);
        
        if ($stateObj->paid != 1) {
            $this->ajaxDie(json_encode(array('success' => false, 'error' => 'El pedido no ha sido pagado todavia')));
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
            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'arc3d_terrain_data WHERE id_order = ' . (int)$orderId;
            $result = Db::getInstance()->getRow($sql);
            
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
            }
        }

        if (!$terrainData) {
            $this->ajaxDie(json_encode(array('success' => false, 'error' => 'No se encontraron datos del terreno para este pedido')));
        }

        $this->ajaxDie(json_encode(array(
            'success' => true,
            'data' => $terrainData,
            'order_reference' => $order->reference,
            'customer_name' => $customer->firstname . ' ' . $customer->lastname
        )));
    }
}
