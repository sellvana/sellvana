<?php

class FCom_Promo_Model_Promo extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo';
    protected static $_fieldOptions = [
        'buy_type' => [
            'qty' => 'Quantity',
            '$' => '$ AMT',
        ],
        'buy_group' => [
            'one' => 'Single Group',
            'any' => 'ANY Group',
            'all' => 'ALL Groups',
            'cat' => 'Categories',
            'anyp' => 'ANY Product'
        ],
        'get_type' => [
            'qty' => 'Quantity',
            '$' => '$ AMT',
            '%' => '% OFF',
            'free' => 'Free Shipping',
        ],
        'get_group' => [
            'same_prod' => 'Same Product',
            'same_group' => 'Same Group',
            'any_group' => 'Any Group',
            'diff_group' => 'Different Group',
        ],
        'status' => [
            'template' => 'Template',
            'pending' => 'Pending',
            'active' => 'Active',
            'expired' => 'Expired',
        ],
    ];

    protected static $_validationRules = [
        [ 'description', '@required' ],
//        array('manuf_vendor_id', '@required'),

        [ 'description', '@string', null, [ 'max' => 255 ] ],

        [ 'buy_amount', '@integer' ],
        [ 'get_amount', '@integer' ],
    ];

    public function getPromosByCart( $cartId )
    {
        return static::orm( 'p' )
                ->join( FCom_Promo_Model_Cart::table(), "p.id = pc.promo_id", "pc" )
                ->where( 'cart_id', $cartId )
                ->select( 'p.id' )
                ->select( 'p.description' )
                ->find_many();
    }

    public function manuf()
    {
        //todo: load vendors here
    }

    public function groups()
    {
        return FCom_Promo_Model_Group::i()->orm()
            ->where( 'promo_id', $this->id )
            ->order_by_asc( 'group_type' )
            ->find_many_assoc();
    }

    public function mediaORM()
    {
        return FCom_Promo_Model_Media::i()->orm( 'pa' )
            ->join( FCom_Core_Model_MediaLibrary::table(), [ 'a.id', '=', 'pa.file_id' ], 'a' )
            ->select( 'a.id' )->select( 'a.file_name' )->select( 'a.folder' )
            ->where( 'pa.promo_id', $this->id );
    }

    public function media()
    {
        return $this->mediaORM()->find_many();
    }

    public function createClone()
    {
        $grHlp = FCom_Promo_Model_Group::i();
        $prodHlp = FCom_Promo_Model_Product::i();
        $attHlp = FCom_Promo_Model_Media::i();
        $clone = static::i()->create( $this->as_array() )->set( [
            'id' => 'null',
            'status' => 'pending',
        ] )->save();
        foreach ( $this->groups() as $gr ) {
            $clGr = $grHlp->create( $gr->as_array() )->set( [
                'id' => null,
                'promo_id' => $clone->id,
            ] )->save();
            foreach ( $gr->products() as $gp ) {
                $clProd = $prodHlp->create( $gp->as_array() )->set( [
                    'id' => null,
                    'promo_id' => $clone->id,
                    'group_id' => $clGr->id,
                ] )->save();
            }
        }
        foreach ( $this->media() as $att ) {
            $attHlp->create( $att->as_array() )->set( [
                'id' => null,
                'promo_id' => $clone->id,
            ] )->save();
        }
        return $clone;
    }

    public function onAfterCreate()
    {
        parent::onAfterCreate();
        $this->from_date = gmdate( 'Y-m-d' );
        $this->to_date   = gmdate( 'Y-m-d', time() + 30 * 86400 );
        $this->status    = 'pending';
    }

    public function onBeforeSave()
    {
        parent::onBeforeSave();

        $this->setDate( $this->get( "from_date" ), 'from_date' );
        $this->setDate( $this->get( "to_date" ), 'to_date' );
        $this->set( 'update_at', date( 'Y-m-d H:i:s' ) );
        if ( BUtil::isEmptyDate( $this->get( 'create_at' ) ) ) {
            $this->set( 'create_at', date( 'Y-m-d H:i:s' ) );
        }
        return true;
    }

    /**
     * Set date field
     * By default dates are returned as strings, therefore we need to convert them for mysql
     *
     * @param $fieldDate
     * @param $field
     */
    public function setDate( $fieldDate, $field )
    {
        $date = strtotime( $fieldDate );
        if ( -1 != $date ) {
            $this->set( $field, date( "Y-m-d", $date ) );
        }
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        $groups = [];
        if ( !$this->_newRecord ) {
            $groupsRaw = FCom_Promo_Model_Group::i()->orm()->where( 'promo_id', $this->id )->find_many();
            foreach ( $groupsRaw as $g ) {
                $groups[ $g->group_type ][] = $g;
            }
        }
        $delete = [];
        if ( empty( $groups[ 'buy' ] ) ) {
            FCom_Promo_Model_Group::i()->create( [
                'promo_id' => $this->id,
                'group_type' => 'buy',
                'group_name' => 'BUY Group',
            ] )->save();
        } elseif ( $this->buy_group === 'one' && sizeof( $groups[ 'buy' ] ) > 1 ) {
            foreach ( $groups[ 'buy' ] as $i => $g ) {
                if ( $i ) $delete[] = $g->id;
            }
        }
        if ( empty( $groups[ 'get' ] ) && $this->get_group === 'diff_group' ) {
            FCom_Promo_Model_Group::i()->create( [
                'promo_id' => $this->id,
                'group_type' => 'get',
                'group_name' => 'GET Group',
            ] )->save();
        } elseif ( !empty( $groups[ 'get' ] ) && $this->get_group !== 'diff_group' ) {
            $delete[] = $groups[ 'get' ][ 0 ]->id;
        }
        if ( !empty( $delete ) ) {
            FCom_Promo_Model_Group::i()->delete_many( [ 'id' => $delete ] );
        }
    }

    public function getActive()
    {
        return static::orm()->where( 'status', 'active' )
                ->order_by_desc( 'buy_amount' )
                ->find_many();
    }
}
