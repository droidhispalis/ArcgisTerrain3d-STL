<?php

class Arcgisterrain3dSavemeshModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if (!Tools::isSubmit('ajax')) {
            $this->ajaxDie(json_encode(array('success' => false, 'error' => 'Solo AJAX')));
        }

        if (!$this->context->customer->isLogged()) {
            $this->ajaxDie(json_encode(array('success' => false, 'error' => 'Debes iniciar sesion')));
        }

        $productId = (int)Tools::getValue('product_id');
        $latitude = (float)Tools::getValue('latitude');
        $longitude = (float)Tools::getValue('longitude');
        $areaKm2 = (float)Tools::getValue('area_km2');
        $shapeType = pSQL(Tools::getValue('shape_type'));
        $fileSizeMB = (float)Tools::getValue('file_size_mb');
        $fingerprint = pSQL(Tools::getValue('fingerprint'));

        if ($productId <= 0) {
            $this->ajaxDie(json_encode(array('success' => false, 'error' => 'Producto no especificado')));
        }

        // Verificar que el producto existe
        $product = new Product($productId, false, $this->context->language->id);
        if (!Validate::isLoadedObject($product) || !$product->active) {
            $this->ajaxDie(json_encode(array('success' => false, 'error' => 'Producto no valido')));
        }

        // Obtener precio del producto
        $price = $product->getPrice(true);

        // Obtener o crear carrito
        $cart = $this->context->cart;
        if (!Validate::isLoadedObject($cart)) {
            $cart = new Cart();
            $cart->id_customer = (int)$this->context->customer->id;
            $cart->id_address_delivery = (int)Address::getFirstCustomerAddressId($this->context->customer->id);
            $cart->id_address_invoice = (int)Address::getFirstCustomerAddressId($this->context->customer->id);
            $cart->id_lang = (int)$this->context->language->id;
            $cart->id_currency = (int)$this->context->currency->id;
            $cart->id_carrier = 0;
            $cart->add();
            $this->context->cart = $cart;
            $this->context->cookie->id_cart = (int)$cart->id;
        }

        // Añadir producto al carrito
        $updateResult = $cart->updateQty(1, $productId);

        if (!$updateResult) {
            $this->ajaxDie(json_encode(array('success' => false, 'error' => 'Error al anadir al carrito')));
        }

        // Guardar datos del pedido en cookie para recuperación posterior
        $timestamp = time();
        if (!isset($this->context->cookie->arc3d_orders)) {
            $this->context->cookie->arc3d_orders = json_encode(array());
        }

        $orders = json_decode($this->context->cookie->arc3d_orders, true);
        if (!is_array($orders)) $orders = array();

        $orderKey = 'order_' . $timestamp;
        $orders[$orderKey] = array(
            'product_id' => $productId,
            'product_name' => $product->name,
            'product_reference' => $product->reference,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'area_km2' => $areaKm2,
            'shape_type' => $shapeType,
            'file_size_mb' => $fileSizeMB,
            'fingerprint' => $fingerprint,
            'timestamp' => $timestamp,
            'price' => $price,
            'cart_id' => $cart->id,
            'status' => 'pending'
        );

        $this->context->cookie->arc3d_orders = json_encode($orders);
        $this->context->cookie->write();

        // Guardar también en base de datos para recuperación posterior
        $this->saveTerrainDataToDB($cart->id, $productId, $product->name, $orders[$orderKey]);

        // Enviar notificación al admin
        $this->sendNotificationEmail($orders[$orderKey]);

        $this->ajaxDie(json_encode(array(
            'success' => true,
            'message' => 'Producto anadido al carrito',
            'product_name' => $product->name,
            'price' => $price,
            'cart_url' => $this->context->link->getPageLink('cart', true)
        )));
    }

    private function sendNotificationEmail($orderData)
    {
        $notificationEmail = Configuration::get('ARC3D_NOTIFICATION_EMAIL');
        if (empty($notificationEmail)) {
            $notificationEmail = Configuration::get('PS_SHOP_EMAIL');
        }

        if (!$notificationEmail) return false;

        $customer = $this->context->customer;
        $subject = '[Nuevo Pedido] Terreno 3D - ' . $orderData['shape_type'];
        $message = '<html><body>';
        $message .= '<h2>Nuevo pedido Terreno 3D (Pendiente de pago)</h2>';
        $message .= '<p><strong>Cliente:</strong> ' . $customer->firstname . ' ' . $customer->lastname . '</p>';
        $message .= '<p><strong>Email:</strong> ' . $customer->email . '</p>';
        $message .= '<p><strong>Producto:</strong> ' . $orderData['product_name'] . ' (Ref: ' . $orderData['product_reference'] . ')</p>';
        $message .= '<p><strong>Tipo terreno:</strong> ' . $orderData['shape_type'] . '</p>';
        $message .= '<p><strong>Coordenadas:</strong> ' . $orderData['latitude'] . ', ' . $orderData['longitude'] . '</p>';
        $message .= '<p><strong>Area:</strong> ' . $orderData['area_km2'] . ' km2</p>';
        $message .= '<p><strong>Precio:</strong> ' . $orderData['price'] . ' EUR</p>';
        $message .= '<p><strong>Tamano estimado:</strong> ' . $orderData['file_size_mb'] . ' MB</p>';
        $message .= '<hr>';
        $message .= '<p><em>Estado: PENDIENTE DE PAGO</em></p>';
        $message .= '<p><em>El cliente debe completar el pago para recibir el archivo STL.</em></p>';
        $message .= '<p><small>Pedido iniciado: ' . date('d/m/Y H:i:s', $orderData['timestamp']) . '</small></p>';
        $message .= '</body></html>';
        $headers = 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $headers .= 'From: ' . Configuration::get('PS_SHOP_EMAIL') . "\r\n";

        return @mail($notificationEmail, $subject, $message, $headers);
    }

    private function saveTerrainDataToDB($cartId, $productId, $productName, $data)
    {
        // Crear tabla si no existe
        $sql = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'arc3d_terrain_data (
            id_terrain INT(11) NOT NULL AUTO_INCREMENT,
            id_cart INT(11) NOT NULL,
            id_order INT(11) DEFAULT NULL,
            id_product INT(11) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            latitude DECIMAL(10, 6) NOT NULL,
            longitude DECIMAL(10, 6) NOT NULL,
            area_km2 DECIMAL(10, 2) NOT NULL,
            shape_type VARCHAR(50) NOT NULL,
            file_size_mb DECIMAL(10, 2) DEFAULT NULL,
            date_add DATETIME NOT NULL,
            PRIMARY KEY (id_terrain),
            KEY idx_cart (id_cart),
            KEY idx_order (id_order)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        
        Db::getInstance()->execute($sql);

        // Insertar datos
        $insertSql = 'INSERT INTO ' . _DB_PREFIX_ . 'arc3d_terrain_data 
            (id_cart, id_product, product_name, latitude, longitude, area_km2, shape_type, file_size_mb, date_add)
            VALUES (
                ' . (int)$cartId . ',
                ' . (int)$productId . ',
                "' . pSQL($productName) . '",
                ' . (float)$data['latitude'] . ',
                ' . (float)$data['longitude'] . ',
                ' . (float)$data['area_km2'] . ',
                "' . pSQL($data['shape_type']) . '",
                ' . (float)$data['file_size_mb'] . ',
                NOW()
            )';
        
        return Db::getInstance()->execute($insertSql);
    }
}
