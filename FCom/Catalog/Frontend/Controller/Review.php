<?php

class FCom_Catalog_Frontend_Controller_Review extends FCom_Frontend_Controller_Abstract
{
    public function action_add()
    {
        $r = explode('/', BRequest::i()->params('product'));
        $href = $r[0];

        $p = array_pop($r);
        $product = FCom_Catalog_Model_Product::i()->load($p, 'url_key');
        if (!$product) {
            BResponse::i()->redirect($href);
        }

        if (Bapp::m('FCom_Customer') && false == FCom_Customer_Model_Customer::sessionUser()) {
            BResponse::i()->redirect(Bapp::href("login"));
        }

        $this->layout('/catalog/review/add');
        BResponse::i()->render();
    }

    public function action_add_post()
    {
        $r = explode('/', BRequest::i()->params('product'));
        $href = $r[0];

        $p = array_pop($r);
        $product = FCom_Catalog_Model_Product::i()->load($p, 'url_key');
        if (!$product) {
            BResponse::i()->redirect(Bapp::href($href));
        }

        $post = BRequest::post();


        if (!empty($post['review'])) {
            $customerId = 0;
            if (Bapp::m('FCom_Customer')) {
                $customer = FCom_Customer_Model_Customer::sessionUser();
                $customerId = $customer->id();
            }
            FCom_Catalog_Model_ProductReview::i()->add($customerId, $product->id(), $post['review']);
        }
        BResponse::i()->redirect(Bapp::href($href));
    }

    public function action_helpful_post()
    {
        $r = explode('/', BRequest::i()->params('product'));
        $href = $r[0];

        if (Bapp::m('FCom_Customer') && false == FCom_Customer_Model_Customer::sessionUser()) {
            BResponse::i()->json(array('redirect' => BApp::href('login')));
        }

        $post = BRequest::post();

        if (empty($post['rid'])) {
            BResponse::i()->redirect(Bapp::href($href));
        }

        if (!empty($post['review_helpful'])) {
            $review = FCom_Catalog_Model_ProductReview::i()->load($post['rid']);
            if ($post['review_helpful'] == 'yes') {
                $review->helpful(1);
            } else {
                $review->helpful(-1);
            }
        }
        BResponse::i()->redirect(Bapp::href($href));
    }
}