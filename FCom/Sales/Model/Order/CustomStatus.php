<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Order_CustomStatus
 *
 * @property int $id
 * @property string $name
 * @property string $code
 */
class FCom_Sales_Model_Order_CustomStatus extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_status';
    protected static $_origClass = __CLASS__;

    protected static $_validationRules = [
        ['name', '@required'],
        ['code', '@required'],
    ];

    /**
    * Fallback singleton/instance factory
    *
    * @param bool $new if true returns a new instance, otherwise singleton
    * @param array $args
    * @return FCom_Sales_Model_Order_Item
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(get_called_class(), $args, !$new);
    }

    /**
     * @return FCom_Sales_Model_Order_CustomStatus
     */
    public function statusNew()
    {
        return $this->orm()->where('code', 'new')->find_one();
    }

    /**
     * @return FCom_Sales_Model_Order_CustomStatus
     */
    public function statusPending()
    {
        return $this->orm()->where('code', 'pending')->find_one();
    }

    /**
     * @return FCom_Sales_Model_Order_CustomStatus
     */
    public function statusPaid()
    {
        return $this->orm()->where('code', 'paid')->find_one();
    }

    /**
     * @param string $name
     * @return FCom_Sales_Model_Order_CustomStatus
     */
    public function status($name)
    {
        return $this->orm()->where('code', $name)->find_one();
    }

    /**
     * @return FCom_Sales_Model_Order_CustomStatus[]
     */
    public function statusList()
    {
        return $this->orm()->find_many();
    }

    /**
     * @return array
     */
    public function statusOptions()
    {
        $status = $this->statusList();
        $options = [];
        if ($status) {
            foreach ($status as $s) {
                $options[$s->code] = $s->name;
            }
        }
        return $options;
    }
}
