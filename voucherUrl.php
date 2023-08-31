<?php

require_once (__DIR__ . '/../../classes/Cart.php');

use CartCore as Cart;

class voucherUrl extends Module
{
    private const DEBUG_MODE = true;

    public function __construct()
    {
        $this->name = 'voucherUrl';
        $this->tab = 'advertising_marketing';
        $this->version = 0.1;
        $this->author = 'Pablo Garcia';

        parent::__construct();

        /* Nombre y descripcion que se muestra en la seccion de modulos */
        $this->displayName = $this->l('Vales Descuento en Url');
        $this->description = $this->l('Permite añadir vales descuento por parametro (\'voucher\') en cualquier lugar');
    }

    public function install(): bool
    {
        if (!parent::install() or
            !$this->registerHook('header') or
            !$this->registerHook('cart')
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {

        return parent::uninstall();
    }

    public function hookHeader()
    {
        global $cookie;

        if (Tools::getValue('voucher')) {
            $cookie->voucherCode = Tools::getValue('voucher');
        }
    }

    public function hookCart($params)
    {
        if (!isset($params['cookie']->voucherCode)) {
            self::DEBUG_MODE && error_log('voucherCode not found in cookie');
            return;
        }

        self::DEBUG_MODE && error_log('voucherCode found in cookie');

        /** @var Cart $cart */
        $cart = $params['cart'];

        $vouchersInCartIds = array_map(function (array $voucher) {
            return $voucher['id_discount'];
        }, $cart->getDiscounts());

        self::DEBUG_MODE && error_log('discount IDs found in current cart : ' . print_r($vouchersInCartIds, true));

        $idDiscount = CartRule::getIdByCode($params['cookie']->voucherCode);

        if (in_array($idDiscount, $vouchersInCartIds)) {
            unset($params['cookie']->voucherCode);
            return;
        }
        $discountObj = new CartRule($idDiscount);
        $totalType = Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING; // TODO : update deprecated const

        if (
            ($cart->getOrderTotal(true, $totalType) >= Configuration::get(PS_PURCHASE_MINIMUM)) // TODO: PHP Warning:  Use of undefined constant PS_PURCHASE_MINIMUM
            && ($cart->getOrderTotal(true, $totalType) >= $discountObj->minimum_amount) // TODO: update
        ) {
            self::DEBUG_MODE && error_log("add dìscount {$idDiscount} to cart, remove voucherCode from cookie");

            $cart->addCartRule($idDiscount);

            self::DEBUG_MODE && error_log('cart discounts updated : ' . print_r($cart->getDiscounts(), true));

            unset($params['cookie']->voucherCode);
        }
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }
}