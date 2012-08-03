<?php

class FCom_Catalog_Model_Category extends FCom_Core_Model_TreeAbstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_category';
    protected static $_cacheAuto = true;

    public function productsORM()
    {
        return FCom_Catalog_Model_Product::i()->factory()->table_alias('p')
            ->join(FCom_Catalog_Model_CategoryProduct::table(), array('pc.product_id','=','p.id'), 'pc')
            ->where('pc.category_id', $this->id);
    }

    public function products()
    {
        return $this->productsORM()->find_many();
    }

    public function url()
    {
        return BApp::href('c/'.$this->url_path);
    }

    public function onReorderAZ($args)
    {
        $c = static::i()->load($args['id']);
        if (!$c) {
            throw new BException('Invalid category ID: '.$args['id']);
        }

        $c->reorderChildrenAZ(!empty($args['recursive']));
        static::i()->cacheSaveDirty();
        return true;
    }

    public static function install()
    {
        $t = static::table();
        BDb::run("
CREATE TABLE IF NOT EXISTS {$t} (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT(10) UNSIGNED DEFAULT NULL,
  `id_path` VARCHAR(50) NOT NULL,
  `sort_order` INT(10) UNSIGNED NOT NULL,
  `node_name` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `url_key` VARCHAR(255) NOT NULL,
  `url_path` VARCHAR(255) NOT NULL,
  `num_children` INT(11) UNSIGNED DEFAULT NULL,
  `num_descendants` INT(11) UNSIGNED DEFAULT NULL,
  `num_products` INT(10) UNSIGNED DEFAULT NULL,
  `is_virtual` TINYINT(3) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_path` (`id_path`),
  UNIQUE KEY `full_name` (`full_name`),
  UNIQUE KEY `parent_id` (`parent_id`,`node_name`),
  KEY `parent_id_2` (`parent_id`,`sort_order`),
  CONSTRAINT `FK_{$t}_parent` FOREIGN KEY (`parent_id`) REFERENCES `{$t}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB DEFAULT CHARSET=utf8;
        ");
    }
}