<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Model_Group
    extends FCom_Core_Model_Abstract
{
    protected static $_table = "fcom_customer_groups";
    protected static $_origClass = __CLASS__;

    /**
     * @param bool  $new
     * @param array $args
     * @return FCom_CustomerGroups_Model_Group
     */
    public static function i($new = false, array $args = array())
    {
        return parent::i($new, $args); // auto completion helper
    }
}