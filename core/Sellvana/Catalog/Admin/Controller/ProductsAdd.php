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

                if ($prodData['images']) {
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

    public function action_categories()
    {
        $r = $this->BRequest;
        $q = explode(' ', $r->get('q'));

        /** @var BORM $orm */
        $orm = $this->Sellvana_Catalog_Model_Category->orm('c')
            ->select(['id', 'full_name', 'sort_order', 'is_enabled'])
            ->order_by_asc('sort_order')
            ->where('is_enabled', 1);

        if (is_array($q)) {
            foreach($q as $value) {
                $orm->where_like("full_name", "'%".$value."%'");
            }
        } else {
            $orm->where_like("full_name", "'%".$q."%'");
        }

        $categories = $orm->find_many_assoc('id', 'full_name');

        $this->BResponse->json($categories);
        exit;
    }

    /*public function action_upload__POST()
    {
        $r = $this->BRequest;
        $ds = DIRECTORY_SEPARATOR;

        if ( !isset($_FILES['upload']['error']) || is_array($_FILES['upload']['error']) ) {
            $this->BResponse->status(500, 'Invalid parameters.', 'Invalid parameters.');
        }

        switch ($_FILES['upload']['error']) {
            case UPLOAD_ERR_NO_FILE:
                $this->BResponse->status(500, 'No file sent.', 'No file sent.');
                break;

            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $this->BResponse->status(500, 'Exceeded filesize limit.', 'Exceeded filesize limit.');
                break;
        }

        $acceptMIMEType = [
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp'
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if ( false === $ext = array_search($finfo->file($_FILES['upload']['tmp_name']),$acceptMIMEType, true)) {
            $this->BResponse->status(500, 'Invalid file format.', 'Invalid file format.');
        }

        // Cheking file size.
        if ($_FILES['upload']['size'] > 3e+6 ) { // 3 MB
            $this->BResponse->status(500, 'Exceeded filesize limit.', 'Exceeded filesize limit.');
        }

        if (!empty($_FILES)) {

            $tempFile = $_FILES['upload']['tmp_name'];
            $targetPath = FULLERON_ROOT_DIR . sprintf('/media/product/temp');
            if (!file_exists($targetPath)) {
                mkdir($targetPath, 0777, true);
            }

            $targetPath = $targetPath . $ds . md5($r->get('id'));
            if (!file_exists($targetPath)) {
                mkdir($targetPath, 0777, true);
            }

            $targetFile =  $targetPath . $ds . $_FILES['upload']['name'];

            move_uploaded_file($tempFile, $targetFile);

        }
    }*/
}