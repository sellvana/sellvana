<?php

class FCom_Catalog_Model_ProductMedia extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_media';
    protected static $_origClass = __CLASS__;

    public function getUrl()
    {
        $row = BORM::for_table('fcom_media_library')->where('id', $this->file_id)->find_one();
        if (!empty($row->subfolder)) {
            $path = BApp::baseUrl().$row->folder.$row->subfolder.'/'.$row->file_name;
        } else {
            $path = BApp::baseUrl().$row->folder.'/'.$row->file_name;
        }
        return $path;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('create_at', BDb::now(), null);
        $this->set('update_at', BDb::now());

        return true;
    }
}
