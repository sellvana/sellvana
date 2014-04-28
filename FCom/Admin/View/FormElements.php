<?php

class FCom_Admin_View_FormElements extends FCom_Admin_View_Abstract
{
    public function getInputId( $p )
    {
        //p.id|default(p.id_prefix|default('model') ~ '-' ~ p.field)
        if ( !empty( $p[ 'id' ] ) ) {
            return $p[ 'id' ];
        }
        if ( empty( $p[ 'field' ] ) ) {
            return '';
        }
        return ( !empty( $p[ 'id_prefix' ] ) ? $p[ 'id_prefix' ] : 'model' ) . '-' . $p[ 'field' ];
    }
    
    public function getInputName( $p )
    {
        if ( !empty( $p[ 'name' ] ) ) {
            return $p[ 'name' ];
        }
        if ( empty( $p[ 'field' ] ) ) {
            return '';
        }
        return ( !empty( $p[ 'name_prefix' ] ) ? $p[ 'name_prefix' ] : 'model' ) . '[' . $p[ 'field' ] . ']';
    }
    
    public function getInputValue( $p )
    {
        if ( isset( $p[ 'value' ] ) ) {
            return $p[ 'value' ];
        }
        if ( empty( $p[ 'field' ] ) ) {
            return '';
        }
        if ( !empty( $p[ 'validator' ] ) ) {
            return $p[ 'validator' ]->fieldValue( $p[ 'field' ] );
        }
        if ( !empty( $p[ 'model' ] ) ) {
            return $p[ 'model' ]->get( $p[ 'field' ] );
        }
        return '';
    }
}