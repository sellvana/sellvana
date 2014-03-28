<?php

class FCom_Core_Model_MediaLibrary extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_media_library';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = array(
        'skip'       => array( 'id', 'create_at', 'update_at' ),
        'unique_key' => array( 'folder', 'subfolder', 'file_name' )
    );
    public function onAfterLoad()
    {
        parent::onAfterLoad();

        $size = $this->file_size;
        if ($size/(1024*1024) > 1 ) {
            $size = round($size/(1024*1024), 2).' MB';
        } else if ($size/1024 >1 ) {
            $size = round($size/1024, 2).' KB';
        } else {
            $size = $size.' Bytes';
        }
        $this->file_size = $size;

        return $this;
    }
}
