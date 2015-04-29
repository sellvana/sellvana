<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Promo_Frontend_Controller
 *
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Promo_Model_Promo $Sellvana_Promo_Model_Promo
 * @property Sellvana_Promo_Model_PromoCoupon $Sellvana_Promo_Model_PromoCoupon
 */
class Sellvana_Promo_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $promos = $this->Sellvana_Promo_Model_Promo->findActiveOrm()
            ->where('display_index', 1)
            ->order_by_asc('display_index_order')
            ->find_many_assoc('id');

        $couponPromoIds = [];
        foreach ($promos as $promo) {
            if ($promo->get('coupon_type') == Sellvana_Promo_Model_Promo::COUPON_TYPE_SINGLE) {
                $couponPromoIds[] = $promo->id();
            }
        }
        if ($couponPromoIds) {
            $coupons = $this->Sellvana_Promo_Model_PromoCoupon->orm()->where_in('promo_id', $couponPromoIds)
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
}
