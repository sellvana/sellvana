<?php

class FCom_Catalog_ApiServer_V1_Product extends FCom_Admin_Controller_ApiServer_Abstract
{
    public function action_get()
    {
        $id = BRequest::i()->param('id');
        $len = BRequest::i()->get('len');
        if (!$len) {
            $len = 10;
        }
        $start = BRequest::i()->get('start');
        if (!$start) {
            $start = 0;
        }

        if ($id) {
            $products[] = FCom_Catalog_Model_Product::load($id);
        } else {
            $products = FCom_Catalog_Model_Product::orm()->limit($len, $start)->find_many();
        }
        if (empty($products)) {
            $this->ok();
        }
        $result = FCom_Catalog_Model_Product::i()->prepareApiData($products, true);
        $this->ok($result);
    }

    public function action_post()
    {
        $post = BUtil::fromJson(BRequest::i()->rawPost());

        if (empty($post['product_name'])) {
            $this->badRequest("Product name is required");
        }

        $data = array();
        $data['product_name'] = $post['product_name'];

        if (!empty($post['sku'])) {
            $data['manuf_sku'] = $post['sku'];
        }

        if (!empty($post['price'])) {
            $data['base_price'] = $post['price'];
        }
        if (!empty($post['weight'])) {
            $data['weight'] = $post['weight'];
        }
        if (!empty($post['short_description'])) {
            $data['short_description'] = $post['short_description'];
        }
        if (!empty($post['description'])) {
            $data['description'] = $post['description'];
        }

        $product = FCom_Catalog_Model_Product::orm()->create($data)->save();
        if (!$product) {
            $this->internalError("Can't create a product");
        }

        if (!empty($post['categories_id'])) {
            if (!is_array($post['categories_id'])) {
                $post['categories_id'] = array($post['categories_id']);
            }
            foreach($post['categories_id'] as $catId) {
                FCom_Catalog_Model_CategoryProduct::orm()->create(array('category_id'=>$catId,'product_id'=>$product->id))->save();
            }
        }

        $this->created(array('id' => $product->id));
    }

    public function action_put()
    {
        $id = BRequest::i()->param('id');
        $post = BUtil::fromJson(BRequest::i()->rawPost());

        if (empty($id)) {
            $this->badRequest("Product id is required");
        }



        $data = array();

        if (!empty($post['product_name'])) {
            $data['product_name'] = $post['product_name'];
        }
        if (!empty($post['sku'])) {
            $data['manuf_sku'] = $post['sku'];
        }
        if (!empty($post['price'])) {
            $data['base_price'] = $post['price'];
        }
        if (!empty($post['weight'])) {
            $data['weight'] = $post['weight'];
        }
        if (!empty($post['short_description'])) {
            $data['short_description'] = $post['short_description'];
        }
        if (!empty($post['description'])) {
            $data['description'] = $post['description'];
        }


        $product = FCom_Catalog_Model_Product::load($id);
        if (!$product) {
            $this->notFound("Product id #{$id} not found");
        }

        $product->set($data)->save();
        $this->ok();
    }

    public function action_delete()
    {
        $id = BRequest::i()->param('id');

        if (empty($id)) {
            $this->notFound("Product id is required");
        }

        $product = FCom_Catalog_Model_Product::load($id);
        if (!$product) {
            $this->notFound("Product id #{$id} not found");
        }

        $product->delete();
        $this->ok();
    }


}