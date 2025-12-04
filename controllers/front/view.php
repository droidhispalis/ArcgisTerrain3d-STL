<?php

class Arcgisterrain3dViewModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

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
        
        if ($categoryId > 0) {
            $category = new Category($categoryId, $this->context->language->id);
            if (Validate::isLoadedObject($category)) {
                $productsObj = $category->getProducts(
                    $this->context->language->id,
                    1,
                    1000,
                    'position',
                    'ASC',
                    false,
                    true
                );
                
                foreach ($productsObj as $prod) {
                    $product = new Product($prod['id_product'], false, $this->context->language->id);
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
        ));

        $this->setTemplate('module:arcgisterrain3d/views/templates/front/map.tpl');
    }
}
