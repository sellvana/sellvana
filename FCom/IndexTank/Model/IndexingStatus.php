<?php

class FCom_IndexTank_Model_IndexingStatus extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_indexing_status';

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Model_IndexHelper
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }
}
