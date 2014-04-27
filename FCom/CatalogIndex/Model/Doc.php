<?php

class FCom_CatalogIndex_Model_Doc extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_index_doc';
    protected static $_importExportProfile = [ 'related' => [ 'id' => 'FCom_Catalog_Model_Product.id', ] ];

    static public function flagReindex( $productIds )
    {
        if ( !$productIds ) {
            return;
        }
        static::update_many( [ 'flag_reindex' => 1 ], [ 'id' => $productIds ] );
    }
}