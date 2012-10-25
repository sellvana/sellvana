<?php

class FCom_Catalog_Model_Product extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_product';

    private $_importErrors = null;
    private $_dataImport = array();

    public static function stockStatusOptions($onlyAvailable=false)
    {
        $options = array(
            'in_stock' => 'In Stock',
            'backorder' => 'On Backorder',
            'special_order' => 'Special Order',
        );
        if (!$onlyAvailable) {
            $options += array(
                'do_not_carry' => 'Do Not Carry',
                'temp_unavail' => 'Temporarily Unavailable',
                'vendor_disc' => 'Supplier Discontinued',
                'manuf_disc' => 'MFR Discontinued',
            );
        }
        return $options;
    }

    public function url($category=null)
    {
        return BApp::href(($category ? $category->url_path.'/' : '').$this->url_key);
    }

    public function imageUrl($full=false)
    {
        $media = BConfig::i()->get('web/media_dir') ? BConfig::i()->get('web/media_dir') : 'media/';
        $url = $full ? BApp::href('/') : '';
        return $url.$media.($this->image_url ? $this->image_url : 'DC642702.jpg');
    }

    public function thumbUrl($w, $h=null)
    {
        return FCom_Core::i()->resizeUrl().'?f='.urlencode($this->imageUrl(true)).'&s='.$w.'x'.$h;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;

        //todo: check out for unique url_key before save
        if (!$this->get('url_key')) $this->generateUrlKey();

        return true;
    }

    public function afterSave()
    {
        if (!parent::afterSave()) return false;

        //todo: setup unique uniq_id
        if (!$this->get('unique_id')) {
            $this->set('unique_id', $this->id);
            $this->save();
        }

        return true;
    }

    public function generateUrlKey()
    {
        //$key = $this->manuf()->manuf_name.'-'.$this->manuf_sku.'-'.$this->product_name;
        $key = $this->product_name;
        $this->set('url_key', BLocale::transliterate($key));
        return $this;
    }

    public function isDisabled()
    {
        return $this->disabled;
    }

    public function onAssociateCategory($args)
    {
        $catId = $args['id'];
        $prodIds = $args['ref'];
        //todo
    }

    public function prepareApiData($products, $includeCategories=false)
    {
        if (!is_array($products)) {
            $products = array($products);
        }
        $result = array();
        foreach($products as $i => $product) {
            $result[$i] = array(
                'id'                => $product->id,
                'product_name'      => $product->product_name,
                'sku'               => $product->manuf_sku,
                'price'             => $product->base_price,
                'url'               => $product->url_key,
                'weight'            => $product->weight,
                'short_description' => !empty($product->short_description) ? $product->short_description : '',
                'description'       => $product->description,
            );
            if ($includeCategories) {
                $categories = $product->categories();
                $result[$i]['categories'] = FCom_Catalog_Model_Category::i()->prepareApiData($categories);
            }
        }
        return $result;
    }

    public function formatApiPost($post)
    {
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
        return $data;
    }

    /**
     * Find all categories which belong to product
     * @return type
     */
    public function categories($includeAscendants=false)
    {
        $categories = FCom_Catalog_Model_CategoryProduct::i()->orm('cp')
            ->join('FCom_Catalog_Model_Category', array('cp.category_id','=','c.id'), 'c')
            ->where('cp.product_id', $this->id())->find_many_assoc();

        if ($includeAscendants) {
            $ascIds = array();
            foreach ($categories as $cat) {
                foreach (explode('/', $cat->id_path) as $id) {
                    if ($id>1 && empty($categories[$id])) {
                        $ascIds[$id] = 1;
                    }
                }
            }
            if ($ascIds) {
                $hlp = FCom_Catalog_Model_CategoryProduct::i();
                $ascendants = FCom_Catalog_Model_Category::i()->orm()->where_in('id', array_keys($ascIds))->find_many();
                foreach ($ascendants as $cat) {
                    $categories[$cat->id] = $hlp->create($cat->as_array());
                }
            }
        }
        return $categories;
    }
/*
    public function customFields($product)
    {
        return FCom_CustomField_Model_ProductField::i()->productFields($product);
    }
*/
    public function customFieldsShowOnFrontend()
    {
        $result = array();
        $fields = FCom_CustomField_Model_ProductField::i()->productFields($this);
        if ($fields) {
            foreach ($fields as $f) {
                if ($f->frontend_show) {
                    $result[] = $f;
                }
            }
        }
        return $result;
    }

    public function searchProductOrm($q='', $filter='', $category = null)
    {
        $qs = preg_split('#\s+#', $q, 0, PREG_SPLIT_NO_EMPTY);

        if ($category && is_object($category)) {
            $productsORM = $category->productsORM();
        } else {
            $productsORM = $this->orm();
        }

        $and = array();
        if ($qs) {
            foreach ($qs as $k) $and[] = array('product_name like ?', '%'.$k.'%');
            $productsORM->where(array('OR'=>array('manuf_sku'=>$q, 'AND'=>$and)));
        }

        if (!empty($filter)){
            foreach($filter as $field => $fieldVal) {
                if (is_array($fieldVal)) {
                    $productsORM->where_in($field, array_values($fieldVal));
                } else {
                    $productsORM->where($field, $fieldVal);
                }
            }
        }
        return $productsORM;
    }


    public function mediaORM($type)
    {
        return FCom_Catalog_Model_ProductMedia::i()->orm()->table_alias('pa')
            ->where('pa.product_id', $this->id)->where('pa.media_type', $type)
            //->select(array('pa.manuf_vendor_id'))
            ->join('FCom_Core_Model_MediaLibrary', array('a.id','=','pa.file_id'), 'a')
            ->select(array('a.id', 'a.file_name', 'a.file_size'));
    }

    public function media($type)
    {
        return $this->mediaORM($type)->find_many_assoc();
    }

    public function import($data, $config=array())
    {
        if (empty($data) || !is_array($data)) {
            return;
        }

        //HANDLE CONFIG

        //multi value separator used to separate values in one column like for images
        //For example: image.png; image2.png; image3.png
        if (!isset($config['format']['multivalue_separator'])) {
            $config['format']['multivalue_separator'] = ';';
        }

        //nesting level separator used to separate nesting of categories
        //For example: Category1 > Category2; Category3 > Category4 > Category5;
        if (!isset($config['format']['nesting_separator'])) {
            $config['format']['nesting_separator'] = '>';
        }

        //product import actions: create, update, create_or_update
        if (!isset($config['import']['actions'])) {
            $config['import']['actions'] = 'create_or_update';
        }

        //import images - default true
        if (!isset($config['import']['images']['import'])) {
            $config['import']['images']['import'] = true;
        }

        //reatain image subfolders - default false
        if (!isset($config['import']['images']['with_subfolders'])) {
            $config['import']['images']['with_subfolders'] = true;
        }

        //import categories - default true
        if (!isset($config['import']['categories']['import'])) {
            $config['import']['categories']['import'] = true;
        }

        //include in menu
        if (!isset($config['import']['categories']['menu'])) {
            $config['import']['categories']['menu'] = true;
        }

        //create missing categories - default true
        if (!isset($config['import']['categories']['create'])) {
            $config['import']['categories']['create'] = true;
        }

        //import custom fields - default true
        if (!isset($config['import']['custom_fields']['import'])) {
            $config['import']['custom_fields']['import'] = true;
        }

        //create missing options for custom fields
        if (!isset($config['import']['custom_fields']['create_missing_options'])) {
            $config['import']['custom_fields']['create_missing_options'] = true;
        }
        $result = array();
        //$result['status'] = '';

        $customFieldsOptions = FCom_CustomField_Model_FieldOption::i()->getListAssoc();

        //HANDLE IMPORT
        static $cfIntersection = '';
        $customFields = array();
        $productIds = array();
        $errors = array();
        foreach($data as $d) {
            //if must have fields not defined then skip the record
            if (empty($d['product_name']) && empty($d['unique_id']) && empty($d['url_key'])) {
                continue;
            }

            $categoriesPath = array();
            if ($config['import']['categories']['import'] && !empty($d['categories'])) {
                $categoriesPath = explode($config['format']['multivalue_separator'], $d['categories']);
                unset($d['categories']);
            }

            $imagesNames = array();
            if ($config['import']['images']['import'] && !empty($d['images'])) {
                $imagesNames = explode($config['format']['multivalue_separator'], $d['images']);
                unset($d['images']);
            }

            //HANDLE CUSTOM FIELDS
            if ($config['import']['custom_fields']['import']) {
                //find intersection of custom fields with data fields
                    $cfFields = FCom_CustomField_Model_Field::i()->getListAssoc();
                    $cfKeys = array_keys($cfFields);
                    $dataKeys = array_keys($d);
                    $cfIntersection = array_intersect($cfKeys, $dataKeys);

                    if ($cfIntersection) {
                        //get custom fields values from data
                        foreach($cfIntersection as $cfk) {
                            $field = $cfFields[$cfk];
                            $dataValue = $d[$cfk];
                            if ($config['import']['custom_fields']['create_missing_options']) {
                                //create missing custom field options
                                if(!empty($customFieldsOptions[$field->id()])) {
                                    if (!in_array($dataValue, $customFieldsOptions[$field->id()])) {
                                        try {
                                            FCom_CustomField_Model_FieldOption::orm()
                                                    ->create(array('field_id' => $field->id(), 'label'=>$dataValue))
                                                    ->save();
                                        } catch (Exception $e) {
                                            $errors[] = $e->getMessage();
                                        }
                                    }
                                }
                            }
                        }
                    }
            }

            //HANDLE PRODUCT
            $p = false;
            if ('create_or_update' == $config['import']['actions'] ||
                    'update' == $config['import']['actions']
                    ) {
                if (isset($d['unique_id'])) {
                    $p = $this->orm()->where("unique_id", $d['unique_id'])->find_one();
                }
                if (!$p && isset($d['product_name'])) {
                    $p = $this->orm()->where("product_name", $d['product_name'])->find_one();
                }
                if (!$p && isset($d['url_key'])) {
                    $p = $this->orm()->where("url_key", $d['url_key'])->find_one();
                }
            }

            if (!$p && 'update' == $config['import']['actions']) {
                continue;
            } elseif (!$p) {
                try {
                    $p = $this->orm()->create($d)->save();
                    $result[]['status'] = 'created';
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                    $result[]['status'] = 'error';
                    continue;
                }
            } else {
                $result[]['status'] = 'updated';
            }

            //$memstart = memory_get_usage();
            //echo $memstart/1024 . "kb<br>";

            $p->set($d);
            if ($p->is_dirty()) {
                $p->save();
            }

            //set custom fields for product
            if (!empty($cfIntersection)) {
                foreach($cfIntersection as $cfk) {
                    $customFields[$p->id()][$cfk] = $d[$cfk];
                }
            }
            //echo memory_get_usage()/1024 . "kb<br>";
            //echo (memory_get_usage()-$memstart)/1024 . "kb - diff<br><hr/>";

            //HANDLE CATEGORIES
            if (!empty($categoriesPath)) {

                //check if parent category exist
                static $topParentCategory = '';
                static $categoriesList = array();
                if (!$topParentCategory) {
                    $topParentCategory = FCom_Catalog_Model_Category::orm()->where_null("parent_id")->find_one();
                    if (!$topParentCategory) {
                        try {
                            $topParentCategory = FCom_Catalog_Model_Category::orm()
                                    ->create(array('parent_id'=>null))
                                    ->save();
                        } catch (Exception $e) {
                            $errors[] = $e->getMessage();
                        }
                    }
                    $categoriesList = FCom_Catalog_Model_Category::i()->parentNodeList();
                }
                if ($topParentCategory) {
                    //check if categories exists
                    //create new categories if not
                    $categories = array();
                    foreach($categoriesPath as $catpath) {
                        $parent = $topParentCategory;
                        $catNodes = explode($config['format']['nesting_separator'], $catpath);
                        /*print_r($catpath);
                        echo "\n";
                        print_r($config['format']['nesting_separator']);
                        echo "\n";
                        print_r($catNodes);
                         *
                         */
                        foreach($catNodes as $catnode) {
                    /*        $category = FCom_Catalog_Model_Category::orm()
                                        ->where('parent_id', $parent->id())
                                        ->where("node_name", $catnode)
                                        ->find_one();
                     *
                     */
                            $category = false;
                            if (!empty($categoriesList[$parent->id()][$catnode])) {
                                $category = $categoriesList[$parent->id()][$catnode];
                            }
                            if ($config['import']['categories']['create'] && !$category) {
                                try {
                                    $category = $parent->createChild($catnode);
                                } catch (Exception $e) {
                                    $errors[] = $e->getMessage();
                                }
                            }
                            if (!$category) {
                                break;
                            }
                            $parent = $category;
                            $categories[$category->id()] = $category;
                        }

                        if ($config['import']['categories']['menu'] && $categories[$category->id()]->inMenu() == false) {
                            $categories[$category->id()]->setInMenu(true);
                        }
                    }

                    //assign products to categories
                    if (!empty($categories)) {
                        foreach($categories as $category) {
                            $catProduct = FCom_Catalog_Model_CategoryProduct::i()->orm()
                                    ->where('product_id', $p->id())
                                    ->where('category_id', $category->id())
                                    ->find_one();
                            if (!$catProduct) {
                                try {
                                    FCom_Catalog_Model_CategoryProduct::orm()
                                        ->create(array('product_id' => $p->id(), 'category_id'=>$category->id()))
                                        ->save();
                                } catch (Exception $e) {
                                    $errors[] = $e->getMessage();
                                }
                            }
                            unset($catProduct);
                        }
                    }
                    unset($categories);
                    unset($category);
                }
            }


            //HANDLE IMAGES
            if (!empty($imagesNames)) {
                $mediaLib = FCom_Core_Model_MediaLibrary::i();
                $productMedia = FCom_Catalog_Model_ProductMedia::i();
                $imageFolder = BConfig::i()->get('fs/image_folder');

                foreach($imagesNames as $fileName) {
                    $pathinfo = pathinfo($fileName);
                    $subfolder = $pathinfo['dirname'] == '.' ? null : $pathinfo['dirname'];
                    $att = $mediaLib->load(array('folder'=>$imageFolder, 'subfolder' => $subfolder, 'file_name'=>$pathinfo['basename']));
                    if (!$att) {
                        $fullPathToFile = FULLERON_ROOT_DIR.'/'.$imageFolder.'/'.$fileName;
                        $size = 0;
                        if (file_exists($fullPathToFile)) {
                            $size = filesize($fullPathToFile);
                        }

                        $subfolder = null;
                        if ($config['import']['images']['with_subfolders']) {
                            $subfolder = $pathinfo['dirname'] == '.' ? null : $pathinfo['dirname'];
                        }
                        try {
                            $att = $mediaLib->create(array(
                                    'folder'    => $imageFolder,
                                    'subfolder' => $subfolder,
                                    'file_name' => $pathinfo['basename'],
                                    'file_size' => $size,
                            ))->save();
                        } catch(Exception $e) {
                            $errors[] = $e->getMessage();
                        }
                    }
                    $fileId = $productMedia->orm()->where('product_id', $p->id())
                                ->where('file_id', $att->id())->find_one();
                    if (!$fileId) {
                        try {
                            $fileId = $productMedia->create(array(
                                    'product_id' => $p->id(),
                                    'media_type' => 'images',
                                    'file_id' => $att->id(),
                            ))->save();
                        } catch (Exception $e) {
                            $errors[] = $e->getMessage();
                        }
                    }
                }
            }

            $productIds[] = $p->id();
            unset($fileId);
            unset($att);
        }

        //HANDLE CUSTOM FIELDS to product relations
        if ($config['import']['custom_fields']['import'] && !empty($cfIntersection) && !empty($productIds)) {
            //get custom fields values from data
            $fieldIds = array();
            foreach($cfIntersection as $cfk) {
                $field = $cfFields[$cfk];
                $fieldIds[] = $field->id();
            }

            //get or create product custom field
            $customsResult = FCom_CustomField_Model_ProductField::orm()->where_in("product_id", $productIds)->find_many();
            foreach($customsResult as $cus) {
                $customsResult[$cus->product_id] = $cus;
            }
            $productCustomFields = array();
            foreach($productIds as $pId) {
                if (!empty($customFields[$pId])) {
                    $productCustomFields = $customFields[$pId];
                }
                $productCustomFields['_add_field_ids'] = implode(",",$fieldIds);
                $productCustomFields['product_id'] = $pId;
                if (!empty($customsResult[$pId])) {
                    $custom = $customsResult[$pId];
                } else {
                    $custom = FCom_CustomField_Model_ProductField::i()->create();
                }
                $custom->set($productCustomFields);
                $custom->save();
                unset($custom);
            }
            unset($customFields);
            unset($customsResult);
        }
        unset($data);
        $this->_importErrors = $errors;
        if ($errors) {
            $result['errors'] = $errors;
        }
        return $result;
    }

}

