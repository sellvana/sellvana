<?php

class FCom_Catalog_Model_ProductLink extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_link';
    protected static $_origClass = __CLASS__;

    protected static $_importExportProfile = array(
        __CLASS__ => array(
            'model'   => __CLASS__,
            'skip'    => array( 'id' ),
            'related' => array(
                'product_id'        => 'FCom_Catalog_Model_Product.id',
                'linked_product_id' => 'FCom_Catalog_Model_Product.id',
            )
        )
    );

    public function productsByType($id, $type)
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')
            ->select('*');
        $orm->join('FCom_Catalog_Model_ProductLink', array('pl.linked_product_id','=','p.id'), 'pl')
                ->where('link_type', $type)
                ->where('pl.product_id', $id);
        return $orm->find_many();
    }
}