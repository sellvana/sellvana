<?php

class Sellvana_CustomerSegments_Model_Segment extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_segment';
    protected static $_origClass = __CLASS__;

    public function options()
    {
        return $this->orm()->order_by_asc('title')->find_many_assoc('id', 'title');
    }
}