<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_IndexTank_Model_ProductFunction extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_product_function';

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Model_ProductFunction
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    public function getList()
    {
        $functions = $this->FCom_IndexTank_Model_ProductFunction->orm()->find_many();
        $result = [];
        foreach ($functions as $f) {
            $result[$f->number] = $f;
        }
        return $result;
    }

    public function getSortingArray()
    {
        $functions = $this->FCom_IndexTank_Model_ProductFunction->orm()->find_many();
        $result = [];
        foreach ($functions as $f) {
            if ($f->use_custom_formula) {
                $result[$f->name] = $f->label;
            } else {
                $result[$f->field_name . '|' . $f->sort_order] = $f->label;
            }
        }
        return $result;
    }

}
