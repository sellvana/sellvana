<?php

/**
 * Class Sellvana_IndexTank_Model_ProductFunction
 *
 * @property int $id
 * @property string $name
 * @property int $number
 * @property string $definition
 * @property string $label
 * @property string $field_name
 * @property string $sort_order enum('asc','desc')
 * @property int $use_custom_formula
 *
 * DI
 * @property Sellvana_IndexTank_Model_ProductFunction $Sellvana_IndexTank_Model_ProductFunction
 */
class Sellvana_IndexTank_Model_ProductFunction extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_product_function';

    public function getList()
    {
        /** @var Sellvana_IndexTank_Model_ProductFunction[] $functions */
        $functions = $this->Sellvana_IndexTank_Model_ProductFunction->orm()->find_many();
        $result = [];
        foreach ($functions as $f) {
            $result[$f->number] = $f;
        }
        return $result;
    }

    public function getSortingArray()
    {
        /** @var Sellvana_IndexTank_Model_ProductFunction[] $functions */
        $functions = $this->Sellvana_IndexTank_Model_ProductFunction->orm()->find_many();
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
