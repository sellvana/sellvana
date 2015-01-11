<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_ShippingFree_Main
 *
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 * @property FCom_Promo_Model_Promo $FCom_Promo_Model_Promo
 * @property FCom_Promo_Model_Cart $FCom_Promo_Model_Cart
 * @property FCom_Sales_Main $FCom_Sales_Main
 */
class FCom_ShippingFree_Main extends BClass
{

    public function bootstrap()
    {
        return;

        // only check cart if module is enabled
        if ($this->BConfig->get('modules/FCom_ShippingFree/active')) {
            // get cart and check for promotions which have get_type 'free'
            $cart = $this->FCom_Sales_Model_Cart->sessionCart(true);

            $promoCart = $this->FCom_Promo_Model_Promo->orm('p')
                                               ->where('p.get_type', FCom_ShippingPlain_ShippingMethod::FREE_SHIPPING)
                                               ->join($this->FCom_Promo_Model_Cart->table(), 'pc.promo_id=p.id', 'pc')
                                               ->where('pc.cart_id', $cart->id())
                                               ->find_one();
            if ($promoCart) {
                $this->FCom_Sales_Main->addShippingMethod('free_shipping', 'FCom_ShippingFree_ShippingMethod');
            }
        }
    }
}
