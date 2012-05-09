<?php

class FCom_CustomField_Model_ProductField extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_product_custom';

    public function productFields($p, $r=array())
    {
        $where = array();
        if ($p->_fieldset_ids || !empty($r['add_fieldset_ids'])) {
            $addSetIds = BUtil::arrayCleanInt($p->_fieldset_ids);
            if (!empty($r['add_fieldset_ids'])) {
                $addSetIds += BUtil::arrayCleanInt($r['add_fieldset_ids']);
            }
            $where['OR'][] = "f.id IN (SELECT field_id FROM ".FCom_CustomField_Model_SetField::table()
                ." WHERE set_id IN (".join(',', $addSetIds)."))";
                $p->_fieldset_ids = join(',', array_unique($addSetIds));
        }

        if ($p->_add_field_ids || !empty($r['add_field_ids'])) {
            $addFieldIds = BUtil::arrayCleanInt($p->_add_field_ids);
            if (!empty($r['add_field_ids'])) {
                $addFieldIds += BUtil::arrayCleanInt($r['add_field_ids']);
            }
            $where['OR'][] = "f.id IN (".join(',', $addFieldIds).")";
            $p->_add_field_ids = join(',', array_unique($addFieldIds));
        }

        if ($p->_hide_field_ids || !empty($r['hide_field_ids'])) {
            $hideFieldIds = BUtil::arrayCleanInt($p->_hide_field_ids);
            if (!empty($r['hide_field_ids'])) {
                $hideFieldIds += BUtil::arrayCleanInt($r['hide_field_ids']);
            }
            $where[] = "f.id NOT IN (".join(',', $hideFieldIds).")";
            $p->_hide_field_ids = join(',', array_unique($hideFieldIds));
        }

        if (!$where) {
            $fields = array();
        } else {
            $fields = FCom_CustomField_Model_Field::i()->orm('f')->where_complex($where)->find_many_assoc();
        }
        return $fields;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (!$this->product_id) return false;
        if (!$this->id && ($exists = static::i()->load($this->product_id, 'product_id'))) {
            return false;
        }
        
        return true;
    }

    public static function install()
    {
        $tProdField = static::table();
        $tProd = FCom_Catalog_Model_Product::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$tProdField} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `_fieldset_ids` text,
  `_add_field_ids` text,
  `_hide_field_ids` text,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_{$tProdField}_product` FOREIGN KEY (`product_id`) REFERENCES {$tProd} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}