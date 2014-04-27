<?php

class FCom_Stock_Admin_Controller_Stock extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'catalog/stocks';
    protected $_modelClass = 'FCom_Stock_Model_Sku';
    protected $_gridHref = 'stock';
    protected $_gridTitle = 'Stock Inventory';
    protected $_recordName = 'SKU';
    protected $_mainTableAlias = 's';
    protected $_navPath = 'catalog/stock';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config[ 'columns' ] = [
            [ 'type' => 'row_select' ],
            [ 'name' => 'id', 'label' => 'ID', 'width' => 50, 'index' => 's.id' ],
            [ 'type' => 'input', 'name' => 'sku', 'label' => 'SKU', 'width' => 300, 'index' => 's.sku',
                    'editable' => true, 'addable' => true, 'edit_inline' => true, 'editor' => 'text',
                    'validation' => [ 'required' => true, 'unique' => BApp::href( 'stock/unique' ) ] ],
            [ 'type' => 'input', 'name' => 'qty_in_stock', 'label' => 'Qty In Stock', 'width' => 300,
                    'index' => 's.qty_in_stock', 'editable' => true, 'addable' => true, 'edit_inline' => true,
                    'editor' => 'text', 'validation' => [ 'required' => true, 'number' => true ] ],
            [ 'type' => 'btn_group',
                  'buttons' => [
                                    [ 'name' => 'edit' ],
                                    [ 'name' => 'delete' ],
                                    [ 'name' => 'edit_inline' ]
                                ]
                ]
        ];
        $config[ 'actions' ] = [
//            'new' => array('caption' => 'Add New Customer Group', 'modal' => true),
            'edit' => true,
            'delete' => true
        ];
        $config[ 'filters' ] = [
            [ 'field' => 'sku', 'type' => 'text' ],
            [ 'field' => 'qty_in_stock', 'type' => 'number-range' ],
        ];
        $config[ 'new_button' ] = '#add_new_sku';
        return $config;
    }

    public function gridViewBefore( $args )
    {
        parent::gridViewBefore( $args );
        $this->view( 'admin/grid' )->set( [ 'actions' => [ 'new' => '<button type="button" id="add_new_sku" class="btn grid-new btn-primary _modal">' . BLocale::_( 'New Sku' ) . '</button>' ] ] );
    }

    public function action_unique__POST()
    {
        $post = BRequest::i()->post();
        $data = each( $post );
        $rows = BDb::many_as_array( FCom_Stock_Model_Sku::i()->orm()->where( $data[ 'key' ], $data[ 'value' ] )->find_many() );
        BResponse::i()->json( [ 'unique' => empty( $rows ), 'id' => ( empty( $rows ) ? -1 : $rows[ 0 ][ 'id' ] ) ] );
    }

    public function action_grid_data__POST()
    {
        $r = BRequest::i();
        if ( $r->post( 'oper' ) == 'edit' ) {
            $data = $r->post();
            // avoid error when edit
            unset( $data[ 'id' ], $data[ 'oper' ], $data[ 'bin_id' ] );
            $set = FCom_Stock_Model_Sku::i()->load( $r->post( 'id' ) )->set( $data )->save();
            $result = $set->as_array();

            BResponse::i()->json( $result );
        } else {
            $this->_processGridDataPost( $this->_modelClass );
        }
    }
}
