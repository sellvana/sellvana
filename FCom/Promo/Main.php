<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Main
 *
 * @property FCom_Promo_Model_Promo         $FCom_Promo_Model_Promo
 */
class FCom_Promo_Main extends BClass
{
    public function onWorkflowCustomerAddsCouponCode($args)
    {
        $cart = $args['cart'];
        $couponCode = $args['coupon_code'];

        $promos = $this->FCom_Promo_Model_Promo->findByCouponCodes([$couponCode]);
        if (!$promos) {
            $result['error']['message'] = 'Coupon not found';
            return;
        }
        foreach ($promos as $promo) {
            if (!$promo->validateForCart($cart)) {
                $result['error']['message'] = "Coupon can't be applied to your cart";
                return;
            }
        }
        $result['success'] = true;
        unset($result['error']);
    }

    public function onCartDiscountCalculate($args)
    {
        /** @var FCom_Sales_Model_Cart $cart */
        $cart = $args['cart'];
        $result =& $args['result'];
        $stopFlag = 0;

        $couponCodes = $cart->getCouponCodes();
        if ($couponCodes) {
            $couponPromos = $this->FCom_Promo_Model_Promo->findByCouponCodes($couponCodes);
            foreach ($couponPromos as $couponPromo) {
                $validateResult = $couponPromo->validateForCart($cart);
                if (!empty($validateResult['match'])) {
                    $couponPromo->calculateActionsForCart($cart, $validateResult, $result);
                    if ($couponPromo->get('stop_flag')) {
                        $stopFlag = 1;
                        break;
                    }
                }
            }
        }

        if (!$stopFlag) {
            /** @var FCom_Promo_Model_Promo[] $noCouponPromos */
            $noCouponPromos = $this->FCom_Promo_Model_Promo->findActiveOrm()
                ->where('promo_type', 'cart')->where('coupon_type', 0)->find_many();

            foreach ($noCouponPromos as $promo) {
                $validateResult = $promo->validateForCart($cart);
                if (!empty($validateResult['match'])) {
                    $promo->calculateActionsForCart($cart, $validateResult, $result);
                    if ($promo->get('stop_flag')) {
                        break;
                    }
                }
            }
        }
    }

    public function onCatalogDiscountCalculate($args)
    {
        /** @var FCom_Promo_Model_Promo[] $noCouponPromos */
        $promos = $this->FCom_Promo_Model_Promo->findActiveOrm()
            ->where('promo_type', 'catalog')->where('coupon_type', 0)->find_many();

        foreach ($promos as $promo) {

        }
    }

}