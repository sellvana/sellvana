<?php

class FCom_CustomField_Model_ProductField extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_product_custom';
    protected static $_importExportProfile = [ 'skip' => [],
     'related' => [ 'product_id' => 'FCom_Catalog_Model_Product.id', ],  ];

    public function productFields( $p, $r = [] )
    {
        $where = [];
        if ( $p->get( '_fieldset_ids' ) || !empty( $r[ 'add_fieldset_ids' ] ) ) {
            $addSetIds = BUtil::arrayCleanInt( $p->get( '_fieldset_ids' ) );
            if ( !empty( $r[ 'add_fieldset_ids' ] ) ) {
                //$addSetIds += BUtil::arrayCleanInt($r['add_fieldset_ids']);
                $addSetIds = array_merge( $addSetIds, BUtil::arrayCleanInt( $r[ 'add_fieldset_ids' ] ) );
            }
            $where[ 'OR' ][] = "f.id IN (SELECT field_id FROM " . FCom_CustomField_Model_SetField::table()
                . " WHERE set_id IN (" . join( ',', $addSetIds ) . "))";
                $p->set( '_fieldset_ids', join( ',', array_unique( $addSetIds ) ) );
        }

        if ( $p->get( '_add_field_ids' ) || !empty( $r[ 'add_field_ids' ] ) ) {
            $addFieldIds = BUtil::arrayCleanInt( $p->get( '_add_field_ids' ) );
            if ( !empty( $r[ 'add_field_ids' ] ) ) {
                //$addFieldIds += BUtil::arrayCleanInt($r['add_field_ids']);
                $addFieldIds = array_merge( $addFieldIds, BUtil::arrayCleanInt( $r[ 'add_field_ids' ] ) );
            }

            $where[ 'OR' ][] = "f.id IN (" . join( ',', $addFieldIds ) . ")";
            $p->set( '_add_field_ids', join( ',', array_unique( $addFieldIds ) ) );
        }

        if ( $p->get( '_hide_field_ids' ) || !empty( $r[ 'hide_field_ids' ] ) ) {
            $hideFieldIds = BUtil::arrayCleanInt( $p->get( '_hide_field_ids' ) );
            if ( !empty( $r[ 'hide_field_ids' ] ) ) {
                //$hideFieldIds += BUtil::arrayCleanInt($r['hide_field_ids']);
                $hideFieldIds = array_merge( $hideFieldIds, BUtil::arrayCleanInt( $r[ 'hide_field_ids' ] ) );
            }
            if ( !empty( $r[ 'add_field_ids' ] ) ) {
                //don't hide hidden fileds which user wants to add even
                $addFieldIdsUnset = BUtil::arrayCleanInt( $p->_add_field_ids );
                $hideFieldIds = array_diff( $hideFieldIds, $addFieldIdsUnset );
            }
            if ( !empty( $hideFieldIds ) ) {
                $where[] = "f.id NOT IN (" . join( ',', $hideFieldIds ) . ")";
            }
            $p->set( '_hide_field_ids', join( ',', array_unique( $hideFieldIds ) ) );
        }

        if ( !$where ) {
            $fields = [];
        } else {
            $fields = FCom_CustomField_Model_Field::i()->orm( 'f' )
                    ->select( "f.*" )
                    ->left_outer_join( FCom_CustomField_Model_SetField::table(), 'f.id = sf.field_id', 'sf' )
                    ->where( $where )
                    ->order_by_asc( 'sf.position' )
                    ->find_many_assoc();
        }
        return $fields;
    }

    public function onBeforeSave()
    {
        if ( !parent::onBeforeSave() ) return false;
        if ( !$this->get( 'product_id' ) ) return false;
        if ( !$this->id() && ( $exists = static::i()->load( $this->get( 'product_id' ), 'product_id' ) ) ) {
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

    public function removeField( $p, $hide_field )
    {
        $field = FCom_CustomField_Model_Field::i()->load( $hide_field );
        $p->set( $field->get( 'field_code' ), '' );

        $field_unset = false;
        if ( $p->get( '_add_field_ids' ) ) {
            $add_fields = explode( ",", $p->get( '_add_field_ids' ) );
            foreach ( $add_fields as $id => $af ) {
                if ( $af == $hide_field ) {
                    $field_unset = true;
                    unset( $add_fields[ $id ] );
                }
            }
            $p->set( '_add_field_ids', implode( ",", $add_fields ) );
        }
        if ( false == $field_unset ) {
            if ( $p->get( '_hide_field_ids' ) ) {
                $p->set( '_hide_field_ids', $p->get( '_hide_field_ids' ) . ',' . $hide_field );
            } else {
                $p->set( '_hide_field_ids', $hide_field );
            }
        }
        $p->save();
    }
}
