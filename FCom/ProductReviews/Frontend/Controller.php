<?php

class FCom_ProductReviews_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public $formId = 'product-review';

    public function action_add()
    {
        $r = BRequest::i()->get();

        $product = FCom_Catalog_Model_Product::i()->load( $r[ 'pid' ] );
        if ( !$product ) {
            //TODO: add notification
            BResponse::i()->redirect( '' );
            return;
        }

        if ( BModuleRegistry::i()->isLoaded( 'FCom_Customer' ) && false == FCom_Customer_Model_Customer::i()->sessionUser() ) {
            $this->forward( 'unauthenticated' );
            return;
        }
        $pr = FCom_ProductReviews_Model_Review::i()->load( [
            'product_id' => $r[ 'pid' ],
            'customer_id' => FCom_Customer_Model_Customer::i()->sessionUserId()
        ] );
        if ( $pr ) {
            BResponse::i()->redirect( $product->url() );
            return;
        }
        $this->formMessages( $this->formId );
        $this->view( 'prodreviews/review-form' )->set( [
            'prod'   => $product,
            'formId' => $this->formId,
            'action' => 'add',
        ] );
        $this->layout( '/prodreview/form' );
    }

    public function action_add__POST()
    {
        $post = BRequest::i()->post();
        //check if customer have debug
        $pr = FCom_ProductReviews_Model_Review::i()->load( [
            'product_id' => $post[ 'pid' ],
            'customer_id' => FCom_Customer_Model_Customer::i()->sessionUserId()
        ] );

        $product = FCom_Catalog_Model_Product::i()->load( $post[ 'pid' ] );
        if ( !$product || empty( $post[ 'review' ] ) ) {
            BResponse::i()->redirect( '' );
            return;
        }
        if ( !$pr ) {
            if ( BModuleRegistry::i()->isLoaded( 'FCom_Customer' ) ) {
                $customer = FCom_Customer_Model_Customer::i()->sessionUser();
                $customerId = $customer->id();
                $post[ 'review' ][ 'customer_id' ] = $customerId;
            }

            $post[ 'review' ][ 'product_id' ] = $product->id();
            $review = FCom_ProductReviews_Model_Review::i()->create();
            $needApprove = BConfig::i()->get( 'modules/FCom_ProductReviews/need_approve' );
            if ( $valid = $review->validate( $post[ 'review' ], [], $this->formId ) ) {
                if ( !$needApprove ) {
                    $post[ 'review' ][ 'approved' ] = 1;
                }
                $review->set( $post[ 'review' ] )->save();
                $review->notify();
            }

            $successMessage = BLocale::_( 'Thank you for your review!' );
            if ( $needApprove && $valid ) {
                $successMessage = BLocale::_( 'Thank you for your review! We will check and approve this review in 24 hours.' );
            }

            if ( BRequest::i()->xhr() ) { //ajax request
                if ( $valid ) {
                    BResponse::i()->json( [ 'status' => 'success', 'message' => $successMessage ] );
                } else {
                    BResponse::i()->json( [ 'status' => 'error', 'message' => $this->getAjaxErrorMessage() ] );
                }
            } else {
                if ( $valid ) {
                    $this->message( $successMessage );
                    $url = $product->url();
                } else {
                    $this->message( 'Cannot save data, please fix above errors', 'error', 'validator-errors:' . $this->formId );
                    $url = BApp::href( 'prodreviews/add?pid=' . $product->id() );
                }
                BResponse::i()->redirect( $url );
            }
        }


    }

    public function action_helpful__POST()
    {
        $post = BRequest::i()->post();

        if ( BModuleRegistry::i()->isLoaded( 'FCom_Customer' ) && false == FCom_Customer_Model_Customer::i()->sessionUser() ) {
            BResponse::i()->json( [ 'redirect' => BApp::href( 'login' ) ] );
            return;
        }

        if ( empty( $post[ 'rid' ] ) ) {
            BResponse::i()->json( [ 'error' => 'Invalid id' ] );
            return;
        }

        if ( !empty( $post[ 'review_helpful' ] ) ) {
            $review = FCom_ProductReviews_Model_Review::i()->load( $post[ 'rid' ] );
            if ( !$review ) {
                BResponse::i()->json( [ 'error' => 'Invalid id' ] );
                return;
            }
            $mark = -1;
            if ( $post[ 'review_helpful' ] == 'yes' ) {
                $mark = 1;
            }
            $customer = FCom_Customer_Model_Customer::i()->sessionUser();
            $record = FCom_ProductReviews_Model_ReviewFlag::i()->load( [
                'customer_id' => $customer->id,
                'review_id' => $review->id,
            ] );

            if ( !$record ) {
                $review->helpful( $mark );
                $data = [ 'customer_id' => $customer->id, 'review_id' => $review->id, 'helpful' => $mark ];
                FCom_ProductReviews_Model_ReviewFlag::i()->create( $data )->save();
            } elseif ( $record->helpful != $mark ) {
                $review->helpful( $mark );
                $record->set( 'helpful', $mark )->save();
            } else {
                BResponse::i()->json( [ 'error' => "You've already rated this review" ] );
            }


        }
    }

    public function action_offensive()
    {
        //TODO: convert to POST
        $rid = BRequest::i()->get( 'rid' );
        if ( empty( $rid ) ) {
            $this->forward( false );
            return;
        }
        $review = FCom_ProductReviews_Model_Review::i()->load( $rid );

        $customer = FCom_Customer_Model_Customer::i()->sessionUser();
        $record = FCom_ProductReviews_Model_ReviewFlag::i()->load( [
            'customer_id' => $customer->id,
            'review_id' => $review->id
        ] );
        if ( !$record ) {
            $review->offensive++;
            $review->save();
            $data = [ 'customer_id' => $customer->id, 'review_id' => $review->id, 'offensive' => 1 ];
            FCom_ProductReviews_Model_ReviewFlag::i()->create( $data )->save();
        } elseif ( !$record->offensive ) {
            $review->offensive++;
            $review->save();
            $record->set( 'offensive', 1 )->save();
        }
    }

    public function getAjaxErrorMessage()
    {
        $messages = BSession::i()->messages( 'validator-errors:' . $this->formId );
        $errorMessages = [];
        foreach ( $messages as $m ) {
            if ( is_array( $m[ 'msg' ] ) ) {
                $errorMessages[] = $m[ 'msg' ][ 'error' ];
            } else {
                $errorMessages[] = $m[ 'msg' ];
            }
        }

        return implode( "<br />", $errorMessages );
    }

    public function action_reviews_list()
    {
        $r = BRequest::i();
        if ( $r->xhr() ) {
            $pid = $r->param( 'pid', true );
            if ( !$pid ) {
                BDebug::error( BLocale::_( 'Invalid ID' ) );
                die;
            }
            if ( !( $product = FCom_Catalog_Model_Product::i()->load( $pid ) ) ) {
                BDebug::error( BLocale::_( 'Cannot load product with this id' ) );
                die;
            }
            $reviews = $product->reviews();
            BResponse::i()->set( $this->view( 'prodreviews/product-reviews-list' )->set( [
                'reviews' => $reviews,
                'userId'  => FCom_Customer_Model_Customer::i()->sessionUserId(),
                'prod'    => $product
            ] ) );
        }
    }

    public function action_edit()
    {
        $r = BRequest::i()->get();
        $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
        $pr = FCom_ProductReviews_Model_Review::i()->load( [
            'id'          => $r[ 'rid' ],
            'customer_id' => $customerId
        ] );
        if ( !$pr ) {
            $this->message( 'Cannot find your review, please check again', 'error', 'validator-errors:' . $this->formId );
        } else {
            $prod = FCom_Catalog_Model_Product::i()->load( $pr->product_id );

            if ( BModuleRegistry::i()->isLoaded( 'FCom_Customer' ) && false == FCom_Customer_Model_Customer::i()->sessionUser() ) {
                $this->forward( 'unauthenticated' );
                return;
            }

            $this->view( 'prodreviews/review-form' )->set( [
                'prod' => $prod,
                'pr' => $pr,
            ] );
        }
        $this->view( 'prodreviews/review-form' )->set( [
            'formId' => $this->formId,
            'action' => 'edit',
        ] );
        $this->formMessages( $this->formId );
        $this->layout( '/prodreview/form' );
    }

    public function action_edit__POST()
    {
        $post = BRequest::i()->post();
        $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
        $pr = FCom_ProductReviews_Model_Review::i()->load( [
            'id'          => $post[ 'rid' ],
            'customer_id' => $customerId
        ] );
        $prod = FCom_Catalog_Model_Product::i()->load( $pr->product_id );
        if ( !$pr ) {
            $this->message( 'Cannot load your review, please check again', 'error', 'validator-errors:' . $this->formId );
            BResponse::i()->redirect( 'prodreviews/edit?pr=' . $pr->id() );
            return;
        }
        //$valid = $pr->set($post['review'])->save();
        $needApprove = BConfig::i()->get( 'modules/FCom_ProductReviews/need_approve' );
        $post[ 'review' ][ 'product_id' ] = $pr->product_id;
        $post[ 'review' ][ 'customer_id' ] = $customerId;
        if ( $valid = $pr->validate( $post[ 'review' ], [], $this->formId ) ) {
            if ( $needApprove ) {
                $post[ 'review' ][ 'approved' ] = 0;
            }
            $pr->set( $post[ 'review' ] )->save();
            //$pr->notify(); //todo: confirm about send notify
        }
        $successMessage = BLocale::_( 'Edit review successfully!' );
        if ( $needApprove ) {
            $successMessage = BLocale::_( 'Edit review successfully! We will check and approve this review in 24 hours.' );
        }
        if ( BRequest::i()->xhr() ) { //ajax request
            if ( $valid ) {
                BResponse::i()->json( [ 'status' => 'success', 'message' => $successMessage ] );
            } else {
                BResponse::i()->json( [ 'status' => 'error', 'message' => $this->getAjaxErrorMessage() ] );
            }
        } else {
            if ( $valid ) {
                $this->message( $successMessage );
                $url = $prod->url();
            } else {
                $this->message( 'Cannot save data, please fix above errors', 'error', 'validator-errors:' . $this->formId );
                $url = BApp::href( 'prodreviews/edit?pr=' . $pr->id() );
            }
            BResponse::i()->redirect( $url );
        }
    }

    public function action_ajax_review()
    {
        $post = BRequest::i()->post();
        $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
        $pr = FCom_ProductReviews_Model_Review::i()->load( [
            'id'          => $post[ 'rid' ],
            'customer_id' => $customerId
        ] );
        if ( !$pr ) {
            BResponse::i()->json( [ 'status' => 'error', 'message' => 'Cannot load your review, please check again' ] );
        } else {
            BResponse::i()->json( $pr->as_array() + [ 'status' => 'success' ] );
        }

    }
}
