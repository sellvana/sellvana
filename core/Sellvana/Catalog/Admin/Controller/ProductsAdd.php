<?php

/**
 * Class Sellvana_Catalog_Admin_Controller_ProductsAdd
 *
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_CategoryProduct $Sellvana_Catalog_Model_CategoryProduct
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
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
        $prodCatHlp = $this->Sellvana_Catalog_Model_CategoryProduct;
        $invHlp = $this->Sellvana_Catalog_Model_InventorySku;
        $created = 0;
        $errors = 0;
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
                $product = $prodHlp->create($prodData)->save();
                if ($prodData['manage_inventory']) {
                    $postInventory[$i]['inventory_sku'] = $prodData['inventory_sku'];
                    $postInventory[$i]['title'] = $prodData['product_name'];
                    $invHlp->create(['product_id' => $product->id()])->set($postInventory[$i])->save();
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
        $this->message($this->BLocale->_('Total %s product(s) created, with %s error(s)', [$created, $errors]));
        $this->BResponse->redirect('/catalog/products/quick-add');
    }
}