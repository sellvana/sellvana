<?php

/**
 * Class FCom_Sales_Workflow_Cart
 *
 * Uses:
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Order $FCom_Sales_Model_Order
 * @property FCom_Promo_Model_Promo $FCom_Promo_Model_Promo
 */
class FCom_Promo_Workflow_Promo extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerAddsPromoCode',
    ];

    public function customerAddsPromoCode($args)
    {
        $cart = $args['cart'];
        $post = $args['post'];

        $promo = $this->FCom_Promo_Model_Promo->findByCouponCode($post['coupon_code']);
        if ($promo) {
            $promo->applyToCart($cart);
        } else {
            //TODO: handle exception
        }
    }
}
