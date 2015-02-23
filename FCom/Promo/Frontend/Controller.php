<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Frontend_Controller
 *
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Promo_Model_Promo $FCom_Promo_Model_Promo
 * @property FCom_Promo_Model_PromoCoupon $FCom_Promo_Model_PromoCoupon
 */
class FCom_Promo_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $promos = $this->FCom_Promo_Model_Promo->findActiveOrm()
            ->where('display_index', 1)
            ->order_by_asc('display_index_order')
            ->find_many_assoc('id');

        $couponPromoIds = [];
        foreach ($promos as $promo) {
            if ($promo->get('coupon_type') == FCom_Promo_Model_Promo::COUPON_TYPE_SINGLE) {
                $couponPromoIds[] = $promo->id();
            }
        }
        if ($couponPromoIds) {
            $coupons = $this->FCom_Promo_Model_PromoCoupon->orm()->where_in('promo_id', $couponPromoIds)
                ->find_many_assoc('promo_id');
            foreach ($coupons as $promoId => $coupon) {
                $promos[$promoId]->set('coupon_code', $coupon->get('code'));
            }
        }

        $this->view('promo/index')->set([
            'promos' => $promos,
        ]);
        $this->layout('/promo');
    }

    public function hook_promotions()
    {
        $cart = $this->FCom_Sales_Model_Cart->sessionCart(true);
        $promoList = $this->FCom_Promo_Model_Promo->getPromosByCart($cart->id);
        $this->BLayout->view('promotions')->promoList = $promoList;
        return $this->BLayout->view('promotions')->render();
    }

    public function action_media()
    {
        $promoId = $this->BRequest->get('id');
        $this->layout('/promo/media');
        $this->view('promo/media')->promo = $this->FCom_Promo_Model_Promo->load($promoId);
    }
}
