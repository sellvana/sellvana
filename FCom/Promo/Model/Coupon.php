<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @property FCom_Promo_Model_Promo $FCom_Promo_Model_Promo
 * @project sellvana_core
 */
class FCom_Promo_Model_Coupon extends BModel
{
    static protected $_origClass = __CLASS__;
    protected $_table = 'fcom_promo_coupon';
    /**
     * @var resource file handle
     */
    protected $_importFileHandle;

    /**
     * Generate number fo coupon codes for a promotion
     * $params = [
     *  'promo_id' => $id,
     *  'pattern' => $pattern,
     *  'length' => $length,
     *  'uses_per_customer' => $usesPerCustomer,
     *  'uses_total' => $usesTotal,
     *  'count' => $couponCount
     * ]
     * @param array $params
     * @return null
     */
    public function generateCoupons($params)
    {
        if (!empty($params['promo_id'])) { // or just remove this check entirely?
            $promo = $this->FCom_Promo_Model_Promo->load($params['promo_id']);
            if(!$promo){
                throw new \InvalidArgumentException("Invalid promotion id.");
            }
        }

        $paramsCount = empty($params['count'])? 1: (int)$params['count'];
        if (empty($params['pattern'])) { // no pattern supplied, first generate a random pattern
            $length = empty($params['length'])? 8: (int)$params['length'];
            $pattern = '{UDL' . $length . '}';
        } else {
            $pattern = $params['pattern'];
        }
        $codes = $this->prepareCodes($pattern, $paramsCount);

        //$count = $this->createCouponCodes($codes, $promo->id());
        $count = count($codes);

        return ['generated' => $count, 'failed' => ($paramsCount - $count), 'codes' => $codes];
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
        $limit = 100; // 100 tries to generate the coupons
        while (!$done) {
            $count = $count - count($codes); // calculate how many more need to be generated
            for ($i = 0; $i < $count; $i++) {
                $code = $this->generateCouponCode($pattern);
                if (!isset($codes[$code])) { // if code repeats, don't add it
                    $codes[$code] = 1;
                }
            }
            $codes = $this->filterOutExistingCodes($codes); // codes now has just valid (unique) codes in it.
            $done = (count($codes) == $paramsCount); // if number of codes is equal to requested number of codes, we're done
            if ($limit-- == 0) { // if limit has reached 0, give up
                break;
            }
        }
        return array_keys($codes);
    }

    /**
     * @param $pattern
     * @return string
     */
    public function generateCouponCode($pattern)
    {
        $code = $this->BUtil->randomPattern($pattern);
        return $code;
    }

    /**
     * Check if any of the supplied codes already exists and if so remove it from results
     * @param $codes
     */
    protected function filterOutExistingCodes($codes)
    {
        $sql = "SELECT `code` FROM " . static::table() . " WHERE `code` IN "; // check if codes exist already
        $place_holders = implode(',', array_fill(0, count($codes), '?'));
        $sql .= "($place_holders)";
        $PDO = BORM::get_db();
        $res = $PDO->prepare($sql);
        $res->execute(array_keys($codes));
        while ($row = $res->fetchObject()) {
            unset($codes[$row->code]); // if code exists, remove it
        }
        return $codes;
    }

    /**
     * Ensure we can read file
     * @param $fullFileName
     * @return bool
     */
    public function validateImportFile($fullFileName)
    {
        return is_readable($fullFileName);
    }

    /**
     * @param $fileName
     * @return bool|int
     */
    public function importFromFile($fileName, $promoId)
    {
        $fh = fopen($fileName, 'r');
        if (!$fh) {
            return false;
        }
        $paramsCount = 0;
        $this->_importFileHandle = $fh;
        $codes = [];
        while($line = fgetcsv($this->_importFileHandle)) {
            if(empty($line)){
                continue;
            }
            $code = $line[0];
            if (!isset($codes[$code])) { // if code repeats, don't add it
                $codes[$code] = 1;
            }
            $paramsCount++;
        }

        $codes = $this->filterOutExistingCodes($codes); // do not allow duplicate codes?

        //$count = $this->createCouponCodes($codes, $promoId);
        $count = count($codes);

        return ['generated' => $count, 'failed' => ($paramsCount - $count), 'codes' => array_keys($codes)];
    }

    public function __destruct()
    {
        if(is_resource($this->_importFileHandle)){
            fclose($this->_importFileHandle);
        }
        parent::__destruct();
    }

    /**
     * @param array $codes
     * @param int $promoId
     * @return int
     */
    public function createCouponCodes($codes, $promoId)
    {
        $count = 0;
        foreach ($codes as $code) {
            $coupon = [
                'promo_id' => $promoId,
                'code'     => $code
            ];
            static::create($coupon)->save();
            $count++;
        }

        return $count;
    }

}
