<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Model_Promo
 *
 * @property int $id
 * @property string $description
 * @property string $details
 * @property int $manuf_vendor_id
 * @property string $from_date
 * @property string $to_date
 * @property string $status enum('template','pending','active','expired')
 * @property string $buy_type enum('qty','$')
 * @property string $buy_group enum('one','any','all','cat','anyp')
 * @property int $buy_amount
 * @property string $get_type enum('qty','$','%','text','choice','free')
 * @property string $get_group enum('same_prod','same_group','any_group','diff_group')
 * @property int $get_amount
 * @property string $originator enum('manuf','vendor')
 * @property string $fulfillment enum('manuf','vendor')
 * @property string $create_at
 * @property string $update_at
 * @property string $coupon
 *
 * @property FCom_Promo_Model_PromoCart     $FCom_Promo_Model_PromoCart
 * @property FCom_Promo_Model_PromoMedia    $FCom_Promo_Model_PromoMedia
 * @property FCom_Core_Model_MediaLibrary   $FCom_Core_Model_MediaLibrary
 * @property FCom_Promo_Model_PromoProduct  $FCom_Promo_Model_PromoProduct
 * @property FCom_Customer_Model_Customer   $FCom_Customer_Model_Customer
 * @property FCom_MultiSite_Main            $FCom_MultiSite_Main
 * @property FCom_Promo_Model_PromoCoupon   $FCom_Promo_Model_PromoCoupon
 * @property FCom_Catalog_Model_CategoryProduct $FCom_Catalog_Model_CategoryProduct
 * @property FCom_Promo_Model_PromoDisplay $FCom_Promo_Model_PromoDisplay
 */
class FCom_Promo_Model_Promo extends FCom_Core_Model_Abstract
{
    const MATCH_ALL = 'all', MATCH_ANY = 'any';

    const COUPON_TYPE_NONE = 0, COUPON_TYPE_SINGLE = 1, COUPON_TYPE_MULTI = 2;

    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo';
    protected static $_fieldOptions = [
        'promo_type' => [
            'cart' => 'Cart',
            'catalog' => 'Catalog',
        ],
        'coupon_type' => [
            0 => "No coupon code required",
            1 => "Single coupon code",
            2 => "Multiple coupon codes",
        ],
        'status' => [
            'template' => 'Template',
            'pending' => 'Pending',
            'active' => 'Active',
            'expired' => 'Expired',
        ],
        'display_index_section' => [
            'regular' => 'Regular',
            'featured' => 'Featured',
            'this_month' => 'This Month',
            'this_week' => 'This Week',
            'today' => 'Today Only',
        ],
        'display_index_type' => [
            'text' => 'Text',
            'cms_block' => 'CMS BLOCK',
        ],
    ];

    protected static $_fieldDefaults = [
        'promo_type' => 'cart',
        'status' => 'pending',
        'coupon_type' => 0,
        'display_index' => 0,
        'display_index_order' => 0,
        'display_index_showexp' => 1,
        'display_index_section' => 'regular',
        'display_index_type' => 'text',
    ];

    protected static $_validationConditions = [];

    public function getPromosByCart($cartId)
    {
        return $this->orm('p')
            ->join($this->FCom_Promo_Model_PromoCart->table(), "p.id = pc.promo_id", "pc")
            ->where('cart_id', $cartId)
            ->select('p.id')
            ->select('p.description')
            ->find_many();
    }

    public function mediaORM()
    {
        return $this->FCom_Promo_Model_PromoMedia->orm('pa')
            ->join($this->FCom_Core_Model_MediaLibrary->table(), ['a.id', '=', 'pa.file_id'], 'a')
            ->select('a.id')->select('a.file_name')->select('a.folder')
            ->where('pa.promo_id', $this->id);
    }

    /**
     * @return FCom_Promo_Model_PromoMedia[]
     */
    public function media()
    {
        return $this->mediaORM()->find_many();
    }

    public function onAfterCreate()
    {
        parent::onAfterCreate();
        $this->from_date = gmdate('Y-m-d');
        $this->to_date   = gmdate('Y-m-d', time() + 30 * 86400);
        $this->status    = 'pending';
    }

    public function onBeforeSave()
    {
        parent::onBeforeSave();

        $this->setDate('from_date', $this->get("from_date"));
        $this->setDate('to_date', $this->get("to_date"));
        if (!$this->get("create_at")) {
            $this->set("create_at", date("Y-m-d"));
        }
        $this->set("update_at", date("Y-m-d"));

        return true;
    }

    /**
     * Set date field
     * By default dates are returned as strings, therefore we need to convert them for mysql
     *
     * @param $field
     * @param $fieldDate
     * @return static
     */
    public function setDate($field, $fieldDate)
    {
        $date = strtotime($fieldDate);
        if (-1 != $date) {
            $this->set($field, date("Y-m-d", $date));
        }
        return $this;
    }

    public function getActive()
    {
        return $this->orm()->where('status', 'active')
                ->order_by_desc('buy_amount')
                ->find_many();
    }

    /**
     * @return BORM
     */
    public function findActiveOrm()
    {
        $now = $this->BDb->now();

        $orm = $this->orm('p')
            ->where('status', 'active')
            ->where_raw('((from_date is null or from_date<?) and (to_date is null or to_date>?))', [$now, $now])
            ->order_by_asc('priority_order')
        ;

        //TODO: move to each specific module event observers?
        if ($this->BModuleRegistry->isLoaded('FCom_CustomerGroup')) {
            $customer = $this->FCom_Customer_Model_Customer->sessionUser();
            if ($customer && ($custGroupId = $customer->get('customer_group'))) {
                $orm->where_raw('FIND_IN_SET(?, customer_group_ids)', [$custGroupId]);
            }
        }

        if ($this->BModuleRegistry->isLoaded('FCom_MultiSite')) {
            $siteData = $this->FCom_MultiSite_Main->getCurrentSiteData();
            if ($siteData) {
                $orm->where_raw('FIND_IN_SET(?, site_ids)', [$siteData['id']]);
            }
        }

        $this->BEvents->fire(__METHOD__, ['orm' => $orm]);

        return $orm;
    }

    /**
     * @param array $couponCodes
     * @return FCom_Promo_Model_Promo[]
     * @throws BException
     */
    public function findByCouponCodes(array $couponCodes)
    {
        $promos = $this->orm('p')->select('p.*')
            ->join('FCom_Promo_Model_PromoCoupon', ['pc.promo_id', '=', 'p.id'], 'pc')
            ->select('pc.code', 'coupon_code')->select('pc.id', 'coupon_id')
            ->where_in('pc.code', $couponCodes)
            ->order_by_asc('p.priority_order')
            ->find_many();
        if (!$promos) {
            return [];
        }
        return $promos;
    }

    public function getPromoDisplayData($asJson = false)
    {
        $results = $this->FCom_Promo_Model_PromoDisplay->orm()->where('promo_id', $this->id())->find_many();
        $result = [];
        foreach ($results as $r) {
            $result[] = $r->as_array();
        }

        return $asJson? $this->BUtil->toJson($result): $result;
    }
}
