<?php

// Deshabilitar TODOS los outputs de errores/warnings
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Limpiar cualquier output buffer previo
if (ob_get_level()) ob_end_clean();
ob_start();

class Arcgisterrain3dSavemeshModuleFrontController extends ModuleFrontController
{
    public function display()
    {
        // Limpiar buffer y forzar JSON limpio
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $productId = (int)Tools::getValue('product_id');
            
            if (!$this->context->customer->isLogged()) {
                die('{"success":false,"error":"No logueado"}');
            }
            
            if ($productId <= 0) {
                die('{"success":false,"error":"ID invalido"}');
            }
            
            $cart = $this->context->cart;
            
            if (!$cart->id) {
                $cart = new Cart();
                $cart->id_customer = $this->context->customer->id;
                $cart->id_lang = $this->context->language->id;
                $cart->id_currency = $this->context->currency->id;
                $cart->add();
                $this->context->cookie->id_cart = $cart->id;
            }
            
            $cart->updateQty(1, $productId);
            
            $cartUrl = $this->context->link->getPageLink('cart', true);
            
            die('{"success":true,"message":"OK","cart_url":"' . addslashes($cartUrl) . '"}');
            
        } catch (Exception $e) {
            die('{"success":false,"error":"' . addslashes($e->getMessage()) . '"}');
        }
    }
}
