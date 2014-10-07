<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Catalog_ApiServer_V1_Product
 *
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Catalog_Model_CategoryProduct $FCom_Catalog_Model_CategoryProduct
 */
class FCom_Catalog_ApiServer_V1_Product extends FCom_ApiServer_Controller_Abstract
{
    public function action_index()
    {
        $id = $this->BRequest->param('id');
        $len = $this->BRequest->get('len');
        if (!$len) {
            $len = 10;
        }
        $start = $this->BRequest->get('start');
        if (!$start) {
            $start = 0;
        }

        if ($id) {
            $products[] = $this->FCom_Catalog_Model_Product->load($id);
        } else {
            $products = $this->FCom_Catalog_Model_Product->orm()->limit($len, $start)->find_many();
        }
        if (empty($products)) {
            $this->ok();
        }
        $result = $this->FCom_Catalog_Model_Product->prepareApiData($products, true);
        $this->ok($result);
    }

    public function action_index__POST()
    {
        $post = $this->BUtil->fromJson($this->BRequest->rawPost());

        if (empty($post['product_name'])) {
            $this->badRequest("Product name is required");
        }

        $data = $this->FCom_Catalog_Model_Product->formatApiPost($post);
        $product = false;
        try {
            $product = $this->FCom_Catalog_Model_Product->create($data)->save();
        } catch (Exception $e) {
            if (23000 == $e->getCode()) {
                $this->internalError("Duplicate product name");
            } else {
                $this->internalError("Can't create a product");
            }
        }
        if (!$product) {
            $this->internalError("Can't create a product");
        }

        if (!empty($post['categories_id'])) {
            if (!is_array($post['categories_id'])) {
                $post['categories_id'] = [$post['categories_id']];
            }
            foreach ($post['categories_id'] as $catId) {
                $this->FCom_Catalog_Model_CategoryProduct->create([
                    'category_id' => $catId,
                    'product_id' => $product->id
                ])->save();
            }
        }

        $this->created(['id' => $product->id]);
    }

    public function action_index__PUT()
    {
        $id = $this->BRequest->param('id');
        $post = $this->BUtil->fromJson($this->BRequest->rawPost());

        if (empty($id)) {
            $this->badRequest("Product id is required");
        }

        $data = $this->FCom_Catalog_Model_Product->formatApiPost($post);

        $product = $this->FCom_Catalog_Model_Product->load($id);
        if (!$product) {
            $this->notFound("Product id #{$id} not found");
        }

        try {
            $product->set($data)->save();
        } catch (Exception $e) {
            if (23000 == $e->getCode()) {
                $this->internalError("Duplicate product name");
            } else {
                $this->internalError("Can't update a product");
            }
        }
        $this->ok();
    }

    public function action_index__DELETE()
    {
        $id = $this->BRequest->param('id');

        if (empty($id)) {
            $this->notFound("Product id is required");
        }

        $product = $this->FCom_Catalog_Model_Product->load($id);
        if (!$product) {
            $this->notFound("Product id #{$id} not found");
        }

        $product->delete();
        $this->ok();
    }


}
