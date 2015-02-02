<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Main
 *
 * @property FCom_Promo_Model_Promo         $FCom_Promo_Model_Promo
 * @property FCom_Promo_Workflow_Promo      $FCom_Promo_Workflow_Promo
 */
class FCom_Promo_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Promo_Workflow_Promo->registerWorkflow();
    }

    public function onCartDiscountCalculate($args)
    {
        /** @var FCom_Sales_Model_Cart $cart */
        $cart = $args['cart'];
        $result =& $args['result'];
        $customerId = !empty($args['customer']) ? $args['customer']->id() : $cart->get('customer_id');
        $stopFlag = 0;

        $couponCode = $cart->get('coupon_code');
        if ($couponCode) {
            $couponPromo = $this->FCom_Promo_Model_Promo->findByCouponCode($couponCode);
            if ($couponPromo && $couponPromo->validateForCart($cart)) {
                $couponPromo->applyToCart($cart);
                if ($couponPromo->get('stop_flag')) {
                    $stopFlag = 1;
                }
            }
        }

        if (!$stopFlag) {
            $noCouponPromos = $this->FCom_Promo_Model_Promo->findActiveOrm('cart', 0)
                ->where('promo_type', 'cart')->where('coupon_type', 0)->find_many();

            foreach ($noCouponPromos as $promo) {
                if ($promo->validateForCart($cart)) {
                    $promo->applyToCart($cart);
                    if ($promo->get('stop_flag')) {
                        $stopFlag = 1;
                        break;
                    }
                }
            }
        }
    }
}