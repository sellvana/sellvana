<?php

class FCom_CustomField_Model_SetField extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_fieldset_field';
    protected static $_importExportProfile = array(
        'skip'       => array( 'id', 'position', ),
        'unique_key' => array( 'set_id', 'field_id', ),
        'related'    => array( 'set_id'   => 'FCom_CustomField_Model_Set.id',
                               'field_id' => 'FCom_CustomField_Model_Field.id',
        ),
    );
    public function addSetField( $data )
    {
        $link = static::load( BUtil::arrayMask( $data, 'set_id,field_id' ) );
        if ( !$link ) {
            $link = static::create( $data )->save();
        } else {
            $link->set( $data )->save();
        }
        return $link;
    }
}
