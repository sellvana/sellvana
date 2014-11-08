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
        if (empty($params['promo_id'])) {
            return null;
        }
        $data = [
            'promo_id' => $params['promo_id'],
            'uses_per_customer' => empty($params['uses_per_customer'])? null: (int)$params['uses_per_customer'],
            'uses_total' => empty($params['uses_total'])? null: (int)$params['uses_total'],
        ];

        $paramsCount = empty($params['count'])? 1: (int)$params['count'];
        if (empty($params['pattern'])) { // no pattern supplied, first generate a random pattern
            $length = empty($params['length'])? 8: (int)$params['length'];
            $pattern = $this->BUtil->randomString($length, 'UD');
        } else {
            $pattern = $params['pattern'];
        }
        $codes = $this->prepareCodes($pattern, $paramsCount);
        foreach ($codes as $code) {
            $coupon = array_merge($data, ['code' => $code]);
            static::create($coupon)->save();
        }
        return count($codes);
    }

    /**
     * @param $pattern
     * @param $paramsCount
     * @return array
     */
    protected function prepareCodes($pattern, $paramsCount)
    {
        $done = false;
        $count = $paramsCount;
        $codes = [];
        $sql = "SELECT `code` FROM " . static::table() . " WHERE `code` IN "; // check if codes exist already
        $limit = 100; // 100 tries to generate the coupons
        while (!$done) {
            for ($i = 0; $i < $count; $i++) {
                $code = $this->BUtil->randomPattern($pattern);
                if(!isset($codes[$code])){ // if code repeats, don't add it
                    $codes[$code] = 1;
                }
            }
            $place_holders = implode(',', array_fill(0, count($codes), '?'));
            $sql .= "($place_holders)";
            $res = BORM::get_db()->query($sql, array_keys($codes));
            while ($row = $res->fetchObject()) {
                unset($codes[$row->code]);
            }
            $count = $count - count($codes);
            $done = (count($codes) == $paramsCount);
            if ($limit-- == 0) {
                break;
            }
        }
        return $codes;
    }
}
