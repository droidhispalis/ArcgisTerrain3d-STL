<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Arcgisterrain3d extends Module
{
    // Claves de configuración
    const CONF_API_KEY            = 'ARC3D_API_KEY';
    const CONF_WIDTH              = 'ARC3D_WIDTH';
    const CONF_HEIGHT             = 'ARC3D_HEIGHT';
    const CONF_MAX_FACES_STL      = 'ARC3D_MAX_FACES_STL';
    const CONF_MAX_FACES_PREVIEW  = 'ARC3D_MAX_FACES_PREVIEW';
    const CONF_MAX_AREA_KM2       = 'ARC3D_MAX_AREA_KM2';
    const CONF_PRODUCT_CATEGORY   = 'ARC3D_PRODUCT_CATEGORY';
    const CONF_NOTIFICATION_EMAIL = 'ARC3D_NOTIFICATION_EMAIL';

    public function __construct()
    {
        $this->name = 'arcgisterrain3d';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Fco Javier Domínguez / ChatGPT';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('ArcGIS Terrain 3D');
        $this->description = $this->l('Módulo para seleccionar un área en ArcGIS 3D y exportar el relieve a STL.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionOrderStatusPostUpdate')
            && Configuration::updateValue(self::CONF_API_KEY, '')
            && Configuration::updateValue(self::CONF_WIDTH, 100)
            && Configuration::updateValue(self::CONF_HEIGHT, 500)
            && Configuration::updateValue(self::CONF_MAX_FACES_STL, 5000000)
            && Configuration::updateValue(self::CONF_MAX_FACES_PREVIEW, 2000000)
            && Configuration::updateValue(self::CONF_MAX_AREA_KM2, 50.0)
            && Configuration::updateValue(self::CONF_PRODUCT_CATEGORY, (int)Configuration::get('PS_HOME_CATEGORY'))
            && Configuration::updateValue(self::CONF_NOTIFICATION_EMAIL, Configuration::get('PS_SHOP_EMAIL'));
    }

    /**
     * Hook para añadir recursos en el header
     */
    public function hookDisplayHeader()
    {
        // Este hook está registrado pero no requiere implementación específica
        // Los CSS y JS se registran en el controlador view.php
    }

    /**
     * Hook que se ejecuta cuando se confirma un pedido
     */
    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $cart = $params['cart'];
        
        // Actualizar tabla con el id_order
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'arc3d_terrain_data 
                SET id_order = ' . (int)$order->id . '
                WHERE id_cart = ' . (int)$cart->id . '
                AND id_order IS NULL';
        
        Db::getInstance()->execute($sql);
        
        // Marcar pedido como validado
        $context = Context::getContext();
        $cookieKey = 'arc3d_order_validated_' . $order->id;
        $context->cookie->$cookieKey = 1;
        $context->cookie->write();
    }

    /**
     * Hook que se ejecuta cuando cambia el estado de un pedido
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        $orderStatus = $params['newOrderStatus'];
        $order = new Order((int)$params['id_order']);
        
        // Verificar si el estado es "Pago aceptado"
        if ($orderStatus->paid != 1) {
            return;
        }
        
        // Verificar si hay datos de terreno asociados en la base de datos
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'arc3d_terrain_data WHERE id_order = ' . (int)$order->id;
        $terrainData = Db::getInstance()->getRow($sql);
        
        if (!$terrainData) {
            return;
        }
        
        // Enviar email de confirmación de pago
        $customer = new Customer($order->id_customer);
        $this->sendPaymentConfirmationEmail($order, $customer, $terrainData);
    }

    /**
     * Envía email cuando el pago se confirma
     */
    private function sendPaymentConfirmationEmail($order, $customer, $terrainData)
    {
        $subject = '[Pago Confirmado] Pedido #' . $order->id . ' - Terreno 3D';
        $message = '<html><body>';
        $message .= '<h2>Su pedido se ha enviado</h2>';
        $message .= '<p>Estimado/a ' . $customer->firstname . ' ' . $customer->lastname . ',</p>';
        $message .= '<p>Hemos recibido el pago de su pedido <strong>#' . $order->id . '</strong>.</p>';
        $message .= '<p><strong>Nos pondremos en contacto con usted con las instrucciones para su proceso.</strong></p>';
        $message .= '<p>Detalles del terreno:</p>';
        $message .= '<ul>';
        $message .= '<li><strong>Producto:</strong> ' . $terrainData['product_name'] . '</li>';
        $message .= '<li><strong>Coordenadas:</strong> ' . $terrainData['latitude'] . ', ' . $terrainData['longitude'] . '</li>';
        $message .= '<li><strong>Área:</strong> ' . $terrainData['area_km2'] . ' km²</li>';
        $message .= '<li><strong>Tipo:</strong> ' . $terrainData['shape_type'] . '</li>';
        $message .= '</ul>';
        $message .= '<p>Para descargar su archivo STL personalizado, acceda a:</p>';
        $message .= '<p><a href="' . Context::getContext()->link->getModuleLink('arcgisterrain3d', 'download', array('id_order' => $order->id)) . '">Descargar Terreno 3D</a></p>';
        $message .= '<hr>';
        $message .= '<p><small>Pedido confirmado: ' . date('d/m/Y H:i:s') . '</small></p>';
        $message .= '</body></html>';
        
        $headers = 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $headers .= 'From: ' . Configuration::get('PS_SHOP_EMAIL') . "\r\n";
        
        @mail($customer->email, $subject, $message, $headers);
        
        // Notificar también al admin
        $adminEmail = Configuration::get(self::CONF_NOTIFICATION_EMAIL);
        if ($adminEmail) {
            $adminSubject = '[PAGO CONFIRMADO] Pedido #' . $order->id . ' - Terreno 3D';
            @mail($adminEmail, $adminSubject, $message, $headers);
        }
    }

    public function uninstall()
    {
        return Configuration::deleteByName(self::CONF_API_KEY)
            && Configuration::deleteByName(self::CONF_WIDTH)
            && Configuration::deleteByName(self::CONF_HEIGHT)
            && Configuration::deleteByName(self::CONF_MAX_FACES_STL)
            && Configuration::deleteByName(self::CONF_MAX_FACES_PREVIEW)
            && Configuration::deleteByName(self::CONF_MAX_AREA_KM2)
            && Configuration::deleteByName(self::CONF_PRODUCT_CATEGORY)
            && Configuration::deleteByName(self::CONF_NOTIFICATION_EMAIL)
            && parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitArcgisterrain3d')) {
            $apiKey  = Tools::getValue('ARC3D_API_KEY');
            $width   = (int)Tools::getValue('ARC3D_WIDTH');
            $height  = (int)Tools::getValue('ARC3D_HEIGHT');
            $maxStl  = (int)Tools::getValue('ARC3D_MAX_FACES_STL');
            $maxPrev = (int)Tools::getValue('ARC3D_MAX_FACES_PREVIEW');
            $maxKm2  = (float)Tools::getValue('ARC3D_MAX_AREA_KM2');
            $category = (int)Tools::getValue('ARC3D_PRODUCT_CATEGORY');
            $email   = Tools::getValue('ARC3D_NOTIFICATION_EMAIL');

            Configuration::updateValue(self::CONF_API_KEY, pSQL($apiKey));
            Configuration::updateValue(self::CONF_WIDTH, $width > 0 ? $width : 100);
            Configuration::updateValue(self::CONF_HEIGHT, $height > 0 ? $height : 500);
            Configuration::updateValue(self::CONF_MAX_FACES_STL, $maxStl > 0 ? $maxStl : 5000000);
            Configuration::updateValue(self::CONF_MAX_FACES_PREVIEW, $maxPrev > 0 ? $maxPrev : 2000000);
            Configuration::updateValue(self::CONF_MAX_AREA_KM2, $maxKm2 > 0 ? $maxKm2 : 50.0);
            Configuration::updateValue(self::CONF_PRODUCT_CATEGORY, $category > 0 ? $category : (int)Configuration::get('PS_HOME_CATEGORY'));
            Configuration::updateValue(self::CONF_NOTIFICATION_EMAIL, pSQL($email));

            $output .= $this->displayConfirmation($this->l('Configuración actualizada.'));
        }

        // Mostrar enlace al módulo frontend
        $moduleLink = Context::getContext()->link->getModuleLink('arcgisterrain3d', 'view');
        $output .= '<div class="alert alert-info">';
        $output .= '<p><strong>' . $this->l('URL del módulo en el frontend:') . '</strong></p>';
        $output .= '<p><a href="' . $moduleLink . '" target="_blank">' . $moduleLink . '</a></p>';
        $output .= '<p><em>' . $this->l('Si el enlace no funciona, intenta limpiar la caché en: Parámetros avanzados > Rendimiento > Limpiar caché') . '</em></p>';
        $output .= '</div>';

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Obtener todas las categorías para el select
        $categories = Category::getCategories($default_lang, true, false);
        $categoryOptions = array();
        $this->buildCategoryOptions($categories, $categoryOptions, 0, '');

        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Configuración ArcGIS Terrain 3D'),
                'icon'  => 'icon-cogs',
            ),
            'input'  => array(
                array(
                    'type'  => 'text',
                    'label' => $this->l('API Key de ArcGIS'),
                    'name'  => 'ARC3D_API_KEY',
                    'desc'  => $this->l('Introduce tu API key de ArcGIS (esriConfig.apiKey).'),
                ),
                array(
                    'type'  => 'text',
                    'label' => $this->l('Ancho del mapa (%)'),
                    'name'  => 'ARC3D_WIDTH',
                    'class' => 'fixed-width-xs',
                    'suffix'=> '%',
                    'desc'  => $this->l('Porcentaje de ancho del contenedor (por defecto 100).'),
                ),
                array(
                    'type'  => 'text',
                    'label' => $this->l('Altura del mapa (px)'),
                    'name'  => 'ARC3D_HEIGHT',
                    'class' => 'fixed-width-xs',
                    'suffix'=> 'px',
                    'desc'  => $this->l('Altura en píxeles del mapa 3D (por defecto 500).'),
                ),
                array(
                    'type'  => 'text',
                    'label' => $this->l('Máx. triángulos STL'),
                    'name'  => 'ARC3D_MAX_FACES_STL',
                    'class' => 'fixed-width-lg',
                    'desc'  => $this->l('Límite de triángulos para exportar STL (por defecto 5.000.000).'),
                ),
                array(
                    'type'  => 'text',
                    'label' => $this->l('Máx. triángulos vista previa 3D'),
                    'name'  => 'ARC3D_MAX_FACES_PREVIEW',
                    'class' => 'fixed-width-lg',
                    'desc'  => $this->l('Límite de triángulos para la vista previa (por defecto 2.000.000).'),
                ),
                array(
                    'type'  => 'text',
                    'label' => $this->l('Área máxima de selección (km)'),
                    'name'  => 'ARC3D_MAX_AREA_KM2',
                    'class' => 'fixed-width-lg',
                    'desc'  => $this->l('Área máxima de la selección en km (por defecto 50 km).'),
                ),
                array(
                    'type'  => 'select',
                    'label' => $this->l('Categoría de productos'),
                    'name'  => 'ARC3D_PRODUCT_CATEGORY',
                    'desc'  => $this->l('Selecciona la categoría de productos que se mostrará en la página del módulo. Los usuarios elegirán uno de estos productos para asociar a su terreno 3D.'),
                    'options' => array(
                        'query' => $categoryOptions,
                        'id' => 'id_category',
                        'name' => 'name'
                    )
                ),
                array(
                    'type'  => 'text',
                    'label' => $this->l('Email de notificación'),
                    'name'  => 'ARC3D_NOTIFICATION_EMAIL',
                    'desc'  => $this->l('Email donde se enviará la notificación de nuevos pedidos de terrenos 3D.'),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Guardar'),
            ),
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->toolbar_scroll = false;
        $helper->submit_action = 'submitArcgisterrain3d';
        $helper->fields_value = $this->getConfigFormValues();

        return $helper->generateForm(array($fields_form[0]));
    }

    /**
     * Construir opciones de categorías de forma recursiva
     */
    private function buildCategoryOptions($categories, &$options, $depth = 0, $prefix = '')
    {
        foreach ($categories as $category) {
            if (isset($category['id_category'])) {
                $options[] = array(
                    'id_category' => (int)$category['id_category'],
                    'name' => $prefix . $category['name']
                );
                
                if (isset($category['children']) && count($category['children']) > 0) {
                    $this->buildCategoryOptions($category['children'], $options, $depth + 1, $prefix . '-- ');
                }
            }
        }
    }

    protected function getConfigFormValues()
    {
        return array(
            'ARC3D_API_KEY'            => Configuration::get(self::CONF_API_KEY),
            'ARC3D_WIDTH'              => (int)Configuration::get(self::CONF_WIDTH),
            'ARC3D_HEIGHT'             => (int)Configuration::get(self::CONF_HEIGHT),
            'ARC3D_MAX_FACES_STL'      => (int)Configuration::get(self::CONF_MAX_FACES_STL),
            'ARC3D_MAX_FACES_PREVIEW'  => (int)Configuration::get(self::CONF_MAX_FACES_PREVIEW),
            'ARC3D_MAX_AREA_KM2'       => (float)Configuration::get(self::CONF_MAX_AREA_KM2),
            'ARC3D_PRODUCT_CATEGORY'   => (int)Configuration::get(self::CONF_PRODUCT_CATEGORY),
            'ARC3D_NOTIFICATION_EMAIL' => Configuration::get(self::CONF_NOTIFICATION_EMAIL),
        );
    }
}
