<?php

class FCom_Catalog_Model_ProductMedia extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_media';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = array(
        'skip'    => array( 'id', 'create_at', 'update_at' ),
        'related' => array(
            'product_id' => 'FCom_Catalog_Model_Product.id',
            'file_id'    => 'FCom_Core_Model_MediaLibrary.id',
        )
    );
    public function getUrl()
    {
        $subfolder = $this->get('subfolder');
        $path = $this->get('folder') . '/' . ($subfolder ? $subfolder . '/' : '') . $this->get('file_name');
        return BApp::src($path);
    }


}
