<?php

class Arcgisterrain3dDownloadModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        if (!$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication&back=' . urlencode($this->context->link->getModuleLink('arcgisterrain3d', 'download')));
        }

        $orderId = (int)Tools::getValue('id_order');
        
        if ($orderId <= 0) {
            $this->errors[] = $this->l('Pedido no especificado');
            $this->setTemplate('module:arcgisterrain3d/views/templates/front/download.tpl');
            return;
        }

        $order = new Order($orderId);
        
        // Verificar que el pedido existe y pertenece al cliente
        if (!Validate::isLoadedObject($order) || $order->id_customer != $this->context->customer->id) {
            $this->errors[] = $this->l('Pedido no encontrado o no autorizado');
            $this->setTemplate('module:arcgisterrain3d/views/templates/front/download.tpl');
            return;
        }

        // Verificar que el pedido está pagado
        $orderState = $order->getCurrentState();
        $stateObj = new OrderState($orderState);
        
        if ($stateObj->paid != 1) {
            $this->errors[] = $this->l('El pedido no ha sido pagado todavía. Por favor, complete el pago antes de descargar.');
            $this->setTemplate('module:arcgisterrain3d/views/templates/front/download.tpl');
            return;
        }

        // Verificar que el pedido contiene el producto de terreno 3D
        $reference = 'TERRAIN3D-VIRTUAL';
        $sql = 'SELECT id_product FROM ' . _DB_PREFIX_ . 'product WHERE reference = "' . pSQL($reference) . '"';
        $terrainProductId = (int)Db::getInstance()->getValue($sql);
        
        $orderDetails = $order->getProducts();
        $hasTerrainProduct = false;
        
        foreach ($orderDetails as $product) {
            if ((int)$product['product_id'] == $terrainProductId) {
                $hasTerrainProduct = true;
                break;
            }
        }
        
        if (!$hasTerrainProduct) {
            $this->errors[] = $this->l('Este pedido no contiene productos de terreno 3D');
            $this->setTemplate('module:arcgisterrain3d/views/templates/front/download.tpl');
            return;
        }

        // Buscar datos del pedido en cookies
        $orders = array();
        if (isset($this->context->cookie->arc3d_orders)) {
            $orders = json_decode($this->context->cookie->arc3d_orders, true);
            if (!is_array($orders)) $orders = array();
        }

        // Buscar el pedido correspondiente por cart_id
        $orderData = null;
        foreach ($orders as $key => $data) {
            if (isset($data['cart_id']) && $data['cart_id'] == $order->id_cart) {
                $orderData = $data;
                break;
            }
        }

        $this->context->smarty->assign(array(
            'order' => $order,
            'order_id' => $orderId,
            'order_reference' => $order->reference,
            'order_data' => $orderData,
            'has_session_data' => !empty($orderData),
            'module_dir' => $this->module->getPathUri()
        ));

        $this->setTemplate('module:arcgisterrain3d/views/templates/front/download.tpl');
    }
}
