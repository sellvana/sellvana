<?php

class FCom_IndexTank_Model_ProductFunction extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_product_function';

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Model_ProductFunction
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    public function get_list()
    {
        $functions = FCom_IndexTank_Model_ProductFunction::i()->orm()->find_many();
        $result = array();
        foreach($functions as $f){
            $result[$f->number] = $f;
        }
        return $result;
    }

}
