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
 * DI
 * @property FCom_Promo_Model_Cart $FCom_Promo_Model_Cart
 * @property FCom_Promo_Model_Group $FCom_Promo_Model_Group
 * @property FCom_Promo_Model_Media $FCom_Promo_Model_Media
 * @property FCom_Core_Model_MediaLibrary $FCom_Core_Model_MediaLibrary
 * @property FCom_Promo_Model_Product $FCom_Promo_Model_Product
 */
class FCom_Promo_Model_Promo extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo';
    protected static $_fieldOptions = [
        'buy_type' => [
            'qty' => 'Quantity',
            '$' => '$ AMT',
        ],
        'buy_group' => [
            'one' => 'Single Group',
            'any' => 'ANY Group',
            'all' => 'ALL Groups',
            'cat' => 'Categories',
            'anyp' => 'ANY Product'
        ],
        'get_type' => [
            'qty' => 'Quantity',
            '$' => '$ AMT',
            '%' => '% OFF',
            'free' => 'Free Shipping',
        ],
        'get_group' => [
            'same_prod' => 'Same Product',
            'same_group' => 'Same Group',
            'any_group' => 'Any Group',
            'diff_group' => 'Different Group',
        ],
        'status' => [
            'template' => 'Template',
            'pending' => 'Pending',
            'active' => 'Active',
            'expired' => 'Expired',
        ],
    ];

    protected static $_validationRules = [
        ['description', '@required'],
//        array('manuf_vendor_id', '@required'),

        ['description', '@string', null, ['max' => 255]],

        ['buy_amount', '@integer'],
        ['get_amount', '@integer'],
    ];

    public function getPromosByCart($cartId)
    {
        return $this->orm('p')
                ->join($this->FCom_Promo_Model_Cart->table(), "p.id = pc.promo_id", "pc")
                ->where('cart_id', $cartId)
                ->select('p.id')
                ->select('p.description')
                ->find_many();
    }

    public function manuf()
    {
        //todo: load vendors here
    }

    public function groups()
    {
        return $this->FCom_Promo_Model_Group->orm()
            ->where('promo_id', $this->id)
            ->order_by_asc('group_type')
            ->find_many_assoc();
    }

    public function mediaORM()
    {
        return $this->FCom_Promo_Model_Media->orm('pa')
            ->join($this->FCom_Core_Model_MediaLibrary->table(), ['a.id', '=', 'pa.file_id'], 'a')
            ->select('a.id')->select('a.file_name')->select('a.folder')
            ->where('pa.promo_id', $this->id);
    }

    /**
     * @return FCom_Promo_Model_Media[]
     */
    public function media()
    {
        return $this->mediaORM()->find_many();
    }

    public function createClone()
    {
        $grHlp = $this->FCom_Promo_Model_Group;
        $prodHlp = $this->FCom_Promo_Model_Product;
        $attHlp = $this->FCom_Promo_Model_Media;
        $clone = $this->create($this->as_array())->set([
            'id' => 'null',
            'status' => 'pending',
        ])->save();
        foreach ($this->groups() as $gr) {
            $clGr = $grHlp->create($gr->as_array())->set([
                'id' => null,
                'promo_id' => $clone->id,
            ])->save();
            foreach ($gr->products() as $gp) {
                $clProd = $prodHlp->create($gp->as_array())->set([
                    'id' => null,
                    'promo_id' => $clone->id,
                    'group_id' => $clGr->id,
                ])->save();
            }
        }
        foreach ($this->media() as $att) {
            $attHlp->create($att->as_array())->set([
                'id' => null,
                'promo_id' => $clone->id,
            ])->save();
        }
        return $clone;
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

        $this->setDate($this->get("from_date"), 'from_date');
        $this->setDate($this->get("to_date"), 'to_date');

        return true;
    }

    /**
     * Set date field
     * By default dates are returned as strings, therefore we need to convert them for mysql
     *
     * @param $fieldDate
     * @param $field
     */
    public function setDate($fieldDate, $field)
    {
        $date = strtotime($fieldDate);
        if (-1 != $date) {
            $this->set($field, date("Y-m-d", $date));
        }
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        $groups = [];
        if (!$this->_newRecord) {
            /** @var FCom_Promo_Model_Group[] $groupsRaw */
            $groupsRaw = $this->FCom_Promo_Model_Group->orm()->where('promo_id', $this->id)->find_many();
            foreach ($groupsRaw as $g) {
                $groups[$g->group_type][] = $g;
            }
        }
        $delete = [];
        if (empty($groups['buy'])) {
            $this->FCom_Promo_Model_Group->create([
                'promo_id' => $this->id,
                'group_type' => 'buy',
                'group_name' => 'BUY Group',
            ])->save();
        } elseif ($this->buy_group === 'one' && sizeof($groups['buy']) > 1) {
            foreach ($groups['buy'] as $i => $g) {
                if ($i) $delete[] = $g->id;
            }
        }
        if (empty($groups['get']) && $this->get_group === 'diff_group') {
            $this->FCom_Promo_Model_Group->create([
                'promo_id' => $this->id,
                'group_type' => 'get',
                'group_name' => 'GET Group',
            ])->save();
        } elseif (!empty($groups['get']) && $this->get_group !== 'diff_group') {
            $delete[] = $groups['get'][0]->id;
        }
        if (!empty($delete)) {
            $this->FCom_Promo_Model_Group->delete_many(['id' => $delete]);
        }
    }

    public function getActive()
    {
        return $this->orm()->where('status', 'active')
                ->order_by_desc('buy_amount')
                ->find_many();
    }
}
