<?php

class FCom_ProductReviews_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_add()
    {
        $r = BRequest::i()->get();

        $product = FCom_Catalog_Model_Product::i()->load($r['pid']);
        if (!$product) {
            BResponse::i()->redirect($href);
        }

        if (Bapp::m('FCom_Customer') && false == FCom_Customer_Model_Customer::sessionUser()) {
            BResponse::i()->redirect(Bapp::href("login"));
        }

        $this->view('prodreviews/add')->pid = $product->id();
        $this->layout('/prodreviews/add');
    }

    public function action_add_post()
    {
        $post = BRequest::i()->post();

        $product = FCom_Catalog_Model_Product::i()->load($post['pid']);
        if (!$product) {
            BResponse::i()->redirect(Bapp::baseUrl());
        }

        if (!empty($post['review'])) {
            $customerId = 0;
            if (Bapp::m('FCom_Customer')) {
                $customer = FCom_Customer_Model_Customer::sessionUser();
                $customerId = $customer->id();
            }
            FCom_ProductReviews_Model_Reviews::i()->add($customerId, $product->id(), $post['review']);
        }
        $href = $product->url_key;
        BResponse::i()->redirect(Bapp::href($href));
    }

    public function action_helpful_post()
    {
        $post = BRequest::i()->post();

        if (Bapp::m('FCom_Customer') && false == FCom_Customer_Model_Customer::sessionUser()) {
            BResponse::i()->json(array('redirect' => BApp::href('login')));
        }

        if (empty($post['rid'])) {
            BResponse::i()->json(array('error' => 'Invalid id'));
        }

        if (!empty($post['review_helpful'])) {
            $review = FCom_ProductReviews_Model_Reviews::i()->load($post['rid']);
            if (!$review) {
                BResponse::i()->json(array('error' => 'Invalid id'));
            }
            if ($post['review_helpful'] == 'yes') {
                $review->helpful(1);
            } else {
                $review->helpful(-1);
            }
        }
    }
}