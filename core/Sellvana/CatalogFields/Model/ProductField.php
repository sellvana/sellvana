<?php

/**
 * Class Sellvana_CatalogFields_Model_ProductField
 *
 * @property int $id
 * @property int $product_id
 * @property string $_fieldset_ids
 * @property string $_add_field_ids
 * @property string $_hide_field_ids
 * @property string $_data_serialized
 * @property string $Color
 * @property string $size
 * @property string $ColorABC
 * @property string $storage
 * @property string $test
 * @property string $test1
 * @property string $test2
 *
 * DI
 * @property Sellvana_CatalogFields_Model_Field $Sellvana_CatalogFields_Model_Field
 * @property Sellvana_CatalogFields_Model_SetField $Sellvana_CatalogFields_Model_SetField
 */
class Sellvana_CatalogFields_Model_ProductField extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_product_custom';
    /*
    protected static $_importExportProfile = ['skip' => [],
     'related' => ['product_id' => 'Sellvana_Catalog_Model_Product.id',], 'unique_id' => ['product_id'] ];
    */

    public function productFields($p, $r = [])
    {
        $where = [];
        if ($p->get('_fieldset_ids') || !empty($r['add_fieldset_ids'])) {
            $addSetIds = $this->BUtil->arrayCleanInt($p->get('_fieldset_ids'));
            if (!empty($r['add_fieldset_ids'])) {
                //$addSetIds += $this->BUtil->arrayCleanInt($r['add_fieldset_ids']);
                $addSetIds = array_merge($addSetIds, $this->BUtil->arrayCleanInt($r['add_fieldset_ids']));
            }
            $where['OR'][] = "f.id IN (SELECT field_id FROM " . $this->Sellvana_CatalogFields_Model_SetField->table()
                . " WHERE set_id IN (" . join(',', $addSetIds) . "))";
                $p->set('_fieldset_ids', join(',', array_unique($addSetIds)));
        }

        if ($p->get('_add_field_ids') || !empty($r['add_field_ids'])) {
            $addFieldIds = $this->BUtil->arrayCleanInt($p->get('_add_field_ids'));
            if (!empty($r['add_field_ids'])) {
                //$addFieldIds += $this->BUtil->arrayCleanInt($r['add_field_ids']);
                $addFieldIds = array_merge($addFieldIds, $this->BUtil->arrayCleanInt($r['add_field_ids']));
            }

            $where['OR'][] = "f.id IN (" . join(',', $addFieldIds) . ")";
            $p->set('_add_field_ids', join(',', array_unique($addFieldIds)));
        }

        if ($p->get('_hide_field_ids') || !empty($r['hide_field_ids'])) {
            $hideFieldIds = $this->BUtil->arrayCleanInt($p->get('_hide_field_ids'));
            if (!empty($r['hide_field_ids'])) {
                //$hideFieldIds += $this->BUtil->arrayCleanInt($r['hide_field_ids']);
                $hideFieldIds = array_merge($hideFieldIds, $this->BUtil->arrayCleanInt($r['hide_field_ids']));
            }
            if (!empty($r['add_field_ids'])) {
                //don't hide hidden fileds which user wants to add even
                $addFieldIdsUnset = $this->BUtil->arrayCleanInt($p->_add_field_ids);
                $hideFieldIds = array_diff($hideFieldIds, $addFieldIdsUnset);
            }
            if (!empty($hideFieldIds)) {
                $where[] = "f.id NOT IN (" . join(',', $hideFieldIds) . ")";
            }
            $p->set('_hide_field_ids', join(',', array_unique($hideFieldIds)));
        }

        if (!$where) {
            $fields = [];
        } else {
            $fields = $this->Sellvana_CatalogFields_Model_Field->orm('f')
                    ->select("f.*")
                    ->left_outer_join($this->Sellvana_CatalogFields_Model_SetField->table(), 'f.id = sf.field_id', 'sf') //todo: implement override left_outer_join to IDE understand BORM::where
                    ->where($where, null)
                    ->order_by_asc('sf.position')
                    ->find_many_assoc();
        }
        return $fields;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;
        if (!$this->get('product_id')) return false;
        if (!$this->id() && ($exists = $this->load($this->get('product_id'), 'product_id'))) {
            return false;
        }

        //clear add fields ids
        /*
        if(!empty($this->_hide_field_ids)){
            $hide_fields = explode(",",$this->_hide_field_ids);
            if (!empty($this->_add_field_ids)){
                $add_fields = explode(",",$this->_add_field_ids);
                foreach($add_fields as $id => $af){
                    if(in_array($af, $hide_fields)){
                        unset($add_fields[$id]);
                    }
                }
                $this->_add_field_ids = implode(",", $add_fields);
            }
        }
         *
         */

        return true;
    }

    /**
     * @param $p
     * @param $hide_field
     * @throws BException
     */
    public function removeField($p, $hide_field)
    {
        $field = $this->Sellvana_CatalogFields_Model_Field->load($hide_field);
        $p->set($field->get('field_code'), '');

        $field_unset = false;
        if ($p->get('_add_field_ids')) {
            $add_fields = explode(",", $p->get('_add_field_ids'));
            foreach ($add_fields as $id => $af) {
                if ($af == $hide_field) {
                    $field_unset = true;
                    unset($add_fields[$id]);
                }
            }
            $p->set('_add_field_ids', implode(",", $add_fields));
        }
        if (false == $field_unset) {
            if ($p->get('_hide_field_ids')) {
                $p->set('_hide_field_ids', $p->get('_hide_field_ids') . ',' . $hide_field);
            } else {
                $p->set('_hide_field_ids', $hide_field);
            }
        }
        $p->save();
    }
}
