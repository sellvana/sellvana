<?php

/**
 * Class Sellvana_ShippingFree_Main
 *
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property Sellvana_Promo_Model_Promo $Sellvana_Promo_Model_Promo
 * @property Sellvana_Promo_Model_PromoCart $Sellvana_Promo_Model_PromoCart
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_ShippingFree_Main extends BClass
{

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_ShippingFree' => 'Shipping Free Settings',
        ]);
        return;

        // only check cart if module is enabled
        if ($this->BConfig->get('modules/Sellvana_ShippingFree/active')) {
            // get cart and check for promotions which have get_type 'free'
            $cart = $this->Sellvana_Sales_Model_Cart->sessionCart(true);

            $promoCart = $this->Sellvana_Promo_Model_Promo->orm('p')
                                               ->where('p.get_type', Sellvana_ShippingPlain_ShippingMethod::FREE_SHIPPING)
                                               ->join($this->Sellvana_Promo_Model_PromoCart->table(), 'pc.promo_id=p.id', 'pc')
                                               ->where('pc.cart_id', $cart->id())
                                               ->find_one();
            if ($promoCart) {
                $this->Sellvana_Sales_Main->addShippingMethod('free_shipping', 'Sellvana_ShippingFree_ShippingMethod');
            }
        }
    }
}
