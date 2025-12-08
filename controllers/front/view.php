<?php

class Arcgisterrain3dViewModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;

    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();

        // Registrar CSS del módulo
        $this->context->controller->registerStylesheet(
            'arcgisterrain3d-style',
            'modules/arcgisterrain3d/views/css/arcgisterrain3d.css',
            array('media' => 'all', 'priority' => 150)
        );

        // Verificar si el usuario es administrador
        $isAdmin = false;
        if (isset($this->context->employee) && $this->context->employee && $this->context->employee->id) {
            // Usuario logueado en backoffice
            $isAdmin = true;
        } elseif ($this->context->customer->isLogged()) {
            // Verificar si el email del cliente coincide con un empleado admin
            $sql = 'SELECT id_employee FROM ' . _DB_PREFIX_ . 'employee WHERE email = "' . pSQL($this->context->customer->email) . '" AND active = 1';
            $employeeId = Db::getInstance()->getValue($sql);
            if ($employeeId) {
                $employee = new Employee($employeeId);
                if (Validate::isLoadedObject($employee)) {
                    $isAdmin = $employee->isSuperAdmin() || $employee->id_profile == 1;
                }
            }
        }

        // Obtener productos de la categoría configurada
        $categoryId = (int)Configuration::get(Arcgisterrain3d::CONF_PRODUCT_CATEGORY);
        $products = array();
        $debugInfo = array();
        
        $debugInfo['category_id'] = $categoryId;
        
        if ($categoryId > 0) {
            $category = new Category($categoryId, $this->context->language->id);
            $debugInfo['category_loaded'] = Validate::isLoadedObject($category);
            
            if (Validate::isLoadedObject($category)) {
                $debugInfo['category_name'] = $category->name;
                
                // Usar consulta SQL directa para obtener productos de la categoría
                $sql = 'SELECT cp.`id_product`
                        FROM `' . _DB_PREFIX_ . 'category_product` cp
                        INNER JOIN `' . _DB_PREFIX_ . 'product` p ON (p.`id_product` = cp.`id_product`)
                        INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON (ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . (int)$this->context->shop->id . ')
                        WHERE cp.`id_category` = ' . (int)$categoryId . '
                        AND ps.`active` = 1
                        AND ps.`visibility` IN ("both", "catalog", "search")
                        ORDER BY cp.`position` ASC';
                
                $productIds = Db::getInstance()->executeS($sql);
                
                if (!is_array($productIds)) {
                    $productIds = array();
                }
                
                $debugInfo['products_found'] = count($productIds);
                
                foreach ($productIds as $row) {
                    $product = new Product($row['id_product'], false, $this->context->language->id);
                    if (Validate::isLoadedObject($product) && $product->active) {
                        $products[] = array(
                            'id_product' => $product->id,
                            'name' => $product->name,
                            'price' => $product->getPrice(true),
                            'price_without_tax' => $product->getPrice(false),
                            'reference' => $product->reference,
                            'description_short' => strip_tags($product->description_short)
                        );
                    }
                }
                
                $debugInfo['active_products'] = count($products);
            }
        }

        $this->context->smarty->assign(array(
            'arcgis_terrain3d_api_key'           => Configuration::get(Arcgisterrain3d::CONF_API_KEY),
            'arcgis_terrain3d_width'             => (int)Configuration::get(Arcgisterrain3d::CONF_WIDTH),
            'arcgis_terrain3d_height'            => (int)Configuration::get(Arcgisterrain3d::CONF_HEIGHT),
            'arcgis_terrain3d_max_faces_stl'     => (int)Configuration::get(Arcgisterrain3d::CONF_MAX_FACES_STL),
            'arcgis_terrain3d_max_faces_preview' => (int)Configuration::get(Arcgisterrain3d::CONF_MAX_FACES_PREVIEW),
            'arcgis_terrain3d_max_area_km2'      => (float)Configuration::get(Arcgisterrain3d::CONF_MAX_AREA_KM2),
            'is_admin'                           => $isAdmin,
            'available_products'                 => $products,
            'debug_info'                         => $debugInfo,
        ));

        $this->setTemplate('module:arcgisterrain3d/views/templates/front/map.tpl');
    }
}
