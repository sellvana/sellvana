<?php

class FCom_Catalog_Model_Product extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_product';

    public static function stockStatusOptions($onlyAvailable=false)
    {
        $options = array(
            'in_stock' => 'In Stock',
            'backorder' => 'On Backorder',
            'special_order' => 'Special Order',
        );
        if (!$onlyAvailable) {
            $options += array(
                'do_not_carry' => 'Do Not Carry',
                'temp_unavail' => 'Temporarily Unavailable',
                'vendor_disc' => 'Supplier Discontinued',
                'manuf_disc' => 'MFR Discontinued',
            );
        }
        return $options;
    }

    public function url($category=null)
    {
        return BApp::href(($category ? $category->url_path.'/' : '').$this->url_key);
    }

    public function imageUrl($full=false)
    {
        $url = $full ? BApp::src('FCom_Catalog').'/' : '';
        return $url.'media/'.($this->image_url ? $this->image_url : 'DC642702.jpg');
    }

    public function thumbUrl($w, $h=null)
    {
        return FCom_Core::i()->resizeUrl().'?f='.urlencode($this->imageUrl()).'&s='.$w.'x'.$h;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;

        if (!$this->get('url_key')) $this->generateUrlKey();

        return true;
    }

    public function generateUrlKey()
    {
        //$key = $this->manuf()->manuf_name.'-'.$this->manuf_sku.'-'.$this->product_name;
        $key = $this->product_name;
        $this->set('url_key', BLocale::transliterate($key));
        return $this;
    }

    public function onAssociateCategory($args)
    {
        $catId = $args['id'];
        $prodIds = $args['ref'];
        if (!$copy) {

        }
    }

    public function categories($pId)
    {
        return FCom_Catalog_Model_CategoryProduct::i()->orm('cp')
                ->join('FCom_Catalog_Model_Category', array('cp.category_id','=','c.id'), 'c')
                ->where('cp.product_id', $pId)->find_many();
    }

    public function customFields($product)
    {
        return FCom_CustomField_Model_ProductField::i()->productFields($product);
    }

    public function customFieldsShowOnFrontend()
    {
        $result = array();
        $fields = FCom_CustomField_Model_ProductField::i()->productFields($this);
        if ($fields) {
            foreach ($fields as $f) {
                if ($f->frontend_show) {
                    $result[] = $f;
                }
            }
        }
        return $result;
    }


    public function mediaORM($type)
    {
        return FCom_Catalog_Model_ProductMedia::i()->orm()->table_alias('pa')
            ->where('pa.product_id', $this->id)->where('pa.media_type', $type)
            //->select(array('pa.manuf_vendor_id'))
            ->join('FCom_Core_Model_MediaLibrary', array('a.id','=','pa.file_id'), 'a')
            ->select(array('a.id', 'a.file_name', 'a.file_size'));
    }

    public function media($type)
    {
        return $this->mediaORM($type)->find_many_assoc();
    }

    public static function install()
    {
        BDb::run("

CREATE TABLE IF NOT EXISTS ".static::table()." (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT(10) UNSIGNED DEFAULT NULL,
  `entity_id` INT(10) UNSIGNED DEFAULT NULL,
  `manuf_id` INT(10) UNSIGNED DEFAULT NULL,
  `manuf_vendor_id` INT(10) UNSIGNED DEFAULT NULL,
  `manuf_sku` VARCHAR(100) NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `url_key` VARCHAR(255) DEFAULT NULL,
  `base_price` DECIMAL(12,4) NOT NULL,
  `notes` TEXT,
  `uom` VARCHAR(10) NOT NULL DEFAULT 'EACH',
  `create_dt` DATETIME DEFAULT NULL,
  `update_dt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `image_url` TEXT,
  `calc_uom` VARCHAR(15) DEFAULT NULL,
  `calc_qty` DECIMAL(12,4) UNSIGNED DEFAULT NULL,
  `base_uom` VARCHAR(15) DEFAULT NULL,
  `base_qty` INT(10) UNSIGNED DEFAULT NULL,
  `pack_uom` VARCHAR(15) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url_key` (`url_key`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;
        ");
    }

    public static function upgrade_0_1_2()
    {
        $tProduct = static::table();
        BDb::ddlClearCache();
        $field = BDb::ddlFieldInfo($tProduct, 'weight');
        if ($field){
            return;
        }
        BDb::run("
            ALTER TABLE ".$tProduct." ADD `weight` DECIMAL( 10, 4 ) NOT NULL
        ");

    }
}

