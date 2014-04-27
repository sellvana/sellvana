<?php

class FCom_IndexTank_Admin_Controller_ProductFields extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'indextank/product_fields';
    protected $_modelClass = 'FCom_IndexTank_Model_ProductField';
    protected $_mainTableAlias = 'pf';
    protected $_permission = 'index_tank/product_field';

    public function gridConfig()
    {
        try {
            $status = FCom_IndexTank_Index_Product::i()->status();
            BLayout::i()->view( 'indextank/product_fields' )->set( 'status', $status );
        } catch ( Exception $e ) {
            BLayout::i()->view( 'indextank/product_fields' )->set( 'status', false );
        }

        $fld = FCom_IndexTank_Model_ProductField::i();
        $config = parent::gridConfig();
        $config[ 'grid' ][ 'columns' ] += array(
            'field_nice_name' => array( 'label' => 'Name', 'editable' => true, 'formatter' => 'showlink', 'formatoptions' => array(
                'baseLinkUrl' => BApp::href( 'indextank/product_fields/form' ), 'idName' => 'id',
            ) ),
            'search' => array( 'label' => 'Search', 'options' => $fld->fieldOptions( 'search' ) ),
            'facets' => array( 'label' => 'Facets', 'options' => $fld->fieldOptions( 'facets' ) ),
            'scoring' => array( 'label' => 'Scoring', 'options' => $fld->fieldOptions( 'scoring' ) ),
            'var_number' => array( 'label' => 'Scoring variable #' ),
            'priority' => array( 'label' => 'Priority' ),
            'filter' => array( 'label' => 'Filter type' ),
            'source_type' => array( 'label' => 'Source type' ),
            'source_value' => array( 'label' => 'Source' ),
        );

        return $config;
    }

    public function formViewBefore( $args )
    {
        parent::formViewBefore( $args );
        $m = $args[ 'model' ];
        $args[ 'view' ]->set( array(
            'title' => $m->id ? 'Edit Product Field: ' . $m->field_nice_name : 'Create New Product Field',
        ) );
    }

    public function formPostAfter( $args )
    {
        $model = $args[ 'model' ];
        if ( $model ) {
            if ( $model->scoring && ( $model->var_number == -1 || !isset( $model->var_number ) ) ) {
                $maxVarField = FCom_IndexTank_Model_ProductField::orm()->select_expr( "max(var_number) as max_var" )->find_one();
                $model->var_number = $maxVarField->max_var + 1;
                $model->save();
            } elseif ( 0 == $model->scoring && $model->var_number >= 0 ) {
                $model->var_number = -1;
                $model->save();
            }
        }

        parent::formPostAfter( $args );
    }

}
