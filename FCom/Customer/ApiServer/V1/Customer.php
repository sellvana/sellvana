<?php

class FCom_Customer_ApiServer_V1_Customer extends FCom_ApiServer_Controller_Abstract
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
            $customers[] = FCom_Customer_Model_Customer::i()->load( $id );
        } else {
            $customers = FCom_Customer_Model_Customer::orm()->limit( $len, $start )->find_many();
        }
        if ( empty( $customers ) ) {
            $this->ok();
        }
        $result = FCom_Customer_Model_Customer::i()->prepareApiData( $customers );
        $this->ok( $result );
    }

    public function action_index__POST()
    {
        $post = BUtil::fromJson( BRequest::i()->rawPost() );

        if ( empty( $post[ 'email' ] ) ) {
            $this->badRequest( "Email is required" );
        }
        if ( empty( $post[ 'password' ] ) ) {
            $this->badRequest( "Password is required" );
        }
        if ( empty( $post[ 'firstname' ] ) ) {
            $this->badRequest( "Firstname is required" );
        }
        if ( empty( $post[ 'lastname' ] ) ) {
            $this->badRequest( "Lastname is required" );
        }

        $data = FCom_Customer_Model_Customer::i()->formatApiPost( $post );

        $customer = FCom_Customer_Model_Customer::orm()->create( $data )->save();

        if ( !$customer ) {
            $this->internalError( "Can't create a customer" );
        }

        $this->created( array( 'id' => $customer->id ) );
    }

    public function action_index__PUT()
    {
        $id = BRequest::i()->param( 'id' );
        $post = BUtil::fromJson( BRequest::i()->rawPost() );

        if ( empty( $id ) ) {
            $this->badRequest( "Customer id is required" );
        }

        $data = FCom_Customer_Model_Customer::i()->formatApiPost( $post );

        $customer = FCom_Customer_Model_Customer::i()->load( $id );
        if ( !$customer ) {
            $this->notFound( "Customer id #{$id} not found" );
        }

        $customer->set( $data )->save();
        $this->ok();
    }

    public function action_index__DELETE()
    {
        $id = BRequest::i()->param( 'id' );

        if ( empty( $id ) ) {
            $this->notFound( "Customer id is required" );
        }

        $customer = FCom_Customer_Model_Customer::i()->load( $id );
        if ( !$customer ) {
            $this->notFound( "Customer id #{$id} not found" );
        }

        $customer->delete();
        $this->ok();
    }


}
