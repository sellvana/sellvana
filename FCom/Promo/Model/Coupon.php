<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project sellvana_core
 */
class FCom_Promo_Model_Coupon extends BModel
{
    protected        $_table     = 'fcom_promo_coupon';
    static protected $_origClass = __CLASS__;

    /**
     * Generate number fo coupon codes for a promotion
     * $params = [
     *  'promo_id' => $id,
     *  'pattern' => $pattern,
     *  'length' => $length,
     *  'uses_per_customer' => $usesPerCustomer,
     *  'total_uses' => $usesTotal,
     *  'count' => $couponCount
     * ]
     * @param array $params
     * @return null
     */
    public function generateCoupons($params)
    {
        if(empty($params['promo_id'])){
            return null;
        }
        $data = [
            'promo_id' => $params['promo_id'],
            'uses_per_customer' => empty($params['uses_per_customer'])? null: (int)$params['uses_per_customer'],
            'uses_total' => empty($params['uses_total'])? null: (int)$params['uses_total'],
        ];

        $codes = [];
        $count = empty($params['count'])? 1: (int)$params['count'];
        if(empty($params['pattern'])){
            $length = empty($params['length'])? 8: (int)$params['length'];
            $pattern = $this->BUtil->randomString($length, 'UD');
        }else{
            $pattern = $params['pattern'];
        }

        for($i = 0; $i < $count; $i++) {
            $code = $this->BUtil->randomPattern($pattern);
            $codes[$code] = 1;
        }
    }
}
