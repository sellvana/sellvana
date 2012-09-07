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

        if (BModuleRegistry::isLoaded('FCom_Customer') && false == FCom_Customer_Model_Customer::sessionUser()) {
            BResponse::i()->redirect(Bapp::href("login"));
        }

        $this->view('prodreviews/add')->pid = $product->id();
        $this->layout('/prodreviews/add');
    }

    public function action_add__POST()
    {
        $post = BRequest::i()->post();

        $product = FCom_Catalog_Model_Product::i()->load($post['pid']);
        if (!$product) {
            BResponse::i()->redirect(Bapp::baseUrl());
        }

        if (!empty($post['review'])) {
            $customerId = 0;
            if (BModuleRegistry::isLoaded('FCom_Customer')) {
                $customer = FCom_Customer_Model_Customer::sessionUser();
                $customerId = $customer->id();
            }
            FCom_ProductReviews_Model_Reviews::i()->add($customerId, $product->id(), $post['review']);
        }
        $href = $product->url_key;
        BResponse::i()->redirect(Bapp::href($href));
    }

    public function action_helpful__POST()
    {
        $post = BRequest::i()->post();

        if (BModuleRegistry::isLoaded('FCom_Customer') && false == FCom_Customer_Model_Customer::sessionUser()) {
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
                $mark = 1;
            } else {
                $mark = -1;
            }
            $customer = FCom_Customer_Model_Customer::sessionUser();
            $record = FCom_ProductReviews_Model_Helpful2Customer::orm()
                    ->where('customer_id', $customer->id)
                    ->where('review_id', $review->id)
                    ->find_one();

            if (!$record) {
                $review->helpful($mark);
                $data = array('customer_id' => $customer->id, 'review_id' => $review->id, 'mark' => $mark);
                FCom_ProductReviews_Model_Helpful2Customer::orm()->create($data)->save();
            }
        }
    }

    public function action_offensive()
    {
        $rid = BRequest::i()->get('rid');
        if (empty($rid)) {
            $this->forward(true);
            return;
        }
        $review = FCom_ProductReviews_Model_Reviews::i()->load($rid);

        $customer = FCom_Customer_Model_Customer::sessionUser();
        $record = FCom_ProductReviews_Model_Offensive2Customer::orm()
                    ->where('customer_id', $customer->id)
                    ->where('review_id', $review->id)
                    ->find_one();
        if (!$record) {
            $review->offensive++;
            $review->save();
            $data = array('customer_id' => $customer->id, 'review_id' => $review->id, 'offensive' => 1);
            FCom_ProductReviews_Model_Offensive2Customer::orm()->create($data)->save();
        }
    }
}