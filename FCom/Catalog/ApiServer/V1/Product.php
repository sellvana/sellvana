<?php

class FCom_Catalog_ApiServer_V1_Product extends FCom_ApiServer_Controller_Abstract
{
    public function action_index()
    {
        $id = BRequest::i()->param( 'id' );
        $len = BRequest::i()->get( 'len' );
        if ( !$len ) {
            $len = 10;
        }
        $start = BRequest::i()->get( 'start' );
        if ( !$start ) {
            $start = 0;
        }

        if ( $id ) {
            $products[] = FCom_Catalog_Model_Product::i()->load( $id );
        } else {
            $products = FCom_Catalog_Model_Product::i()->orm()->limit( $len, $start )->find_many();
        }
        if ( empty( $products ) ) {
            $this->ok();
        }
        $result = FCom_Catalog_Model_Product::i()->prepareApiData( $products, true );
        $this->ok( $result );
    }

    public function action_index__post()
    {
        $post = BUtil::fromJson( BRequest::i()->rawPost() );

        if ( empty( $post[ 'product_name' ] ) ) {
            $this->badRequest( "Product name is required" );
        }

        $data = FCom_Catalog_Model_Product::i()->formatApiPost( $post );
        $product = false;
        try {
            $product = FCom_Catalog_Model_Product::i()->orm()->create( $data )->save();
        } catch ( Exception $e ) {
            if ( 23000 == $e->getCode() ) {
                $this->internalError( "Duplicate product name" );
            } else {
                $this->internalError( "Can't create a product" );
            }
        }
        if ( !$product ) {
            $this->internalError( "Can't create a product" );
        }

        if ( !empty( $post[ 'categories_id' ] ) ) {
            if ( !is_array( $post[ 'categories_id' ] ) ) {
                $post[ 'categories_id' ] = [ $post[ 'categories_id' ] ];
            }
            foreach ( $post[ 'categories_id' ] as $catId ) {
                FCom_Catalog_Model_CategoryProduct::i()->orm()->create( [ 'category_id' => $catId, 'product_id' => $product->id ] )->save();
            }
        }

        $this->created( [ 'id' => $product->id ] );
    }

    public function action_index__put()
    {
        $id = BRequest::i()->param( 'id' );
        $post = BUtil::fromJson( BRequest::i()->rawPost() );

        if ( empty( $id ) ) {
            $this->badRequest( "Product id is required" );
        }

        $data = FCom_Catalog_Model_Product::i()->formatApiPost( $post );

        $product = FCom_Catalog_Model_Product::i()->load( $id );
        if ( !$product ) {
            $this->notFound( "Product id #{$id} not found" );
        }

        try {
            $product->set( $data )->save();
        } catch ( Exception $e ) {
            if ( 23000 == $e->getCode() ) {
                $this->internalError( "Duplicate product name" );
            } else {
                $this->internalError( "Can't update a product" );
            }
        }
        $this->ok();
    }

    public function action_index__delete()
    {
        $id = BRequest::i()->param( 'id' );

        if ( empty( $id ) ) {
            $this->notFound( "Product id is required" );
        }

        $product = FCom_Catalog_Model_Product::i()->load( $id );
        if ( !$product ) {
            $this->notFound( "Product id #{$id} not found" );
        }

        $product->delete();
        $this->ok();
    }


}
