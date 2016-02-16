<?php

/**
 * Class Sellvana_Catalog_Admin_Controller_ProductsAdd
 *
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductMedia $Sellvana_Catalog_Model_ProductMedia
 * @property Sellvana_Catalog_Model_CategoryProduct $Sellvana_Catalog_Model_CategoryProduct
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 */
class Sellvana_Catalog_Admin_Controller_ProductsAdd extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'catalog/products-quick-add';

    public function action_index()
    {
        $this->layout('/catalog/products/quick-add');
    }

    public function action_index__POST()
    {
#echo "<xmp>"; var_dump($this->BRequest->post()); echo "</xmp>";
        $postProducts = $this->BRequest->post('products');
        $postInventory = $this->BRequest->post('inventory');
        $postCategories = $this->BRequest->post('categories');
        $prodHlp = $this->Sellvana_Catalog_Model_Product;
        $prodMediaHlp = $this->Sellvana_Catalog_Model_ProductMedia;
        $prodCatHlp = $this->Sellvana_Catalog_Model_CategoryProduct;
        $invHlp = $this->Sellvana_Catalog_Model_InventorySku;
        $created = 0;
        $errors = 0;
        $products = [];

        foreach ($postProducts as $i => $prodData) {
            if (empty($prodData['product_sku'])) {
                continue;
            }
            try {
                $p = $prodHlp->load($prodData['product_sku'], 'product_sku');
                if ($p) {
                    $this->message($this->BLocale->_('Product with SKU %s already exists', $prodData['product_sku']), 'error');
                    $errors++;
                    continue;
                }
                $this->BDb->transaction();
                if ($prodData['manage_inventory'] && $prodData['inventory_sku'] === '') {
                    $prodData['inventory_sku'] = $prodData['product_sku'];
                }
                $product = $prodHlp->create($prodData)->save();
                $products[] = $product;

                if (!empty($prodData['images'])) {
                    $prodMediaData = [];
                    $mediaIds = explode(',', $prodData['images']);
                    foreach($mediaIds as $mediaId) {
                        $prodMediaData[] = [
                            'product_id' => $product->id(),
                            'media_type' => Sellvana_Catalog_Model_ProductMedia::MEDIA_TYPE_IMG,
                            'file_id' => $mediaId,
                            'in_gallery' => 1,
                            'create_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    $prodMediaHlp->create_many($prodMediaData);
                }
                if (!empty($prodData['manage_inventory'])) {
                    $postInventory[$i]['inventory_sku'] = $prodData['inventory_sku'];
                    $postInventory[$i]['title'] = $prodData['product_name'];
                    $invHlp->create($postInventory[$i])->save();
                }

                if (!empty($postCategories[$i])) {
                    $catIds = $this->BUtil->arrayCleanInt($postCategories[$i]);
                    foreach ($catIds as $cId) {
                        $prodCatHlp->create(['product_id' => $product->id(), 'category_id' => $cId])->save();
                    }
                }

                $this->BDb->commit();
                $created++;
            } catch (Exception $e) {
                $this->BDb->rollback();
                $this->message($e->getMessage(), 'error');
                $errors++;
            }
        }

        $this->BEvents->fire(__METHOD__ . ':after', ['products' => $products]);

        $this->message($this->BLocale->_('Total %s product(s) created, with %s error(s)', [$created, $errors]));
        $this->BResponse->redirect('/catalog/products/quick-add');
    }

    public function action_unique_sku__POST()
    {
        $r = $this->BRequest;
        $p = $r->post();
        try {
            if (empty($p['_sku'])) {
                throw new BException('Invalid field name');
            }
            $sku = $this->BDb->sanitizeFieldName($p['_sku']);
            $key = $p['_key'];
            if (empty($p['products'][$key][$sku])) {
                throw new BException('Invalid field value');
            }

            $val = $p['products'][$key][$sku];
            $exists = $this->Sellvana_Catalog_Model_Product->orm()->where($sku, $val)->find_one();
            $result = ['unique' => !$exists, 'id' => !$exists ? -1 : $exists->id()];
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage()];
        }
        $this->BResponse->json($result);
    }

    public function action_categories_search()
    {
        $q = $this->BRequest->get('q');
        if (!$q) {
            $this->BResponse->json([]);
            return;
        }

        $index = $this->_indexCategories($q);

        $this->BResponse->json($index);
        exit;
    }

    protected function _indexCategories($q)
    {
        $cacheKey = 'categories-index-'
            . $this->FCom_Admin_Model_User->sessionUserId()
            . '-' . str_replace(' ', '-', trim($q));

        $cached = $this->BCache->load($cacheKey);
        if ($cached) {
            return $cached;
        }

        $q = explode(' ', $q);
        /** @var BORM $orm */
        $orm = $this->Sellvana_Catalog_Model_Category->orm()
            ->select(['id', 'full_name', 'sort_order', 'is_enabled'])
            ->order_by_asc('sort_order')
            ->where('is_enabled', 1);

        if (is_array($q)) {
            foreach($q as $value) {
                $orm->where_like('full_name', "%{$value}%");
            }
        } else {
            $orm->where_like('full_name', "%{$q}%");
        }

        $categories = $orm->find_many_assoc('id', 'full_name');

        $this->BCache->save($cacheKey, $categories);
        return $categories;
    }
}