<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Model class for table "fcom_product".
 *
 * The followings are the available columns in table 'fcom_product':
 *
*@property string  $id
 * @property string  $product_sku
 * @property string  $product_name
 * @property string  $short_description
 * @property string  $description
 * @property string  $url_key
 * @property string  $cost
 * @property string  $msrp
 * @property string  $map
 * @property string  $markup
 * @property string  $base_price
 * @property string  $sale_price
 * @property string  $net_weight
 * @property string  $ship_weight
 * @property integer $is_hidden
 * @property string  $notes
 * @property string  $uom
 * @property string  $thumb_url
 * @property string  $create_at
 * @property string  $update_at
 * @property string  $data_serialized
 * @property string  $avg_rating
 * @property integer $num_reviews
 *
 * DI
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property Sellvana_Catalog_Model_CategoryProduct $Sellvana_Catalog_Model_CategoryProduct
 * @property Sellvana_Catalog_Model_ProductLink $Sellvana_Catalog_Model_ProductLink
 * @property Sellvana_CustomField_Model_ProductField $Sellvana_CustomField_Model_ProductField
 * @property Sellvana_Catalog_Model_ProductMedia $Sellvana_Catalog_Model_ProductMedia
 * @property Sellvana_CustomField_Model_FieldOption $Sellvana_CustomField_Model_FieldOption
 * @property Sellvana_CustomField_Model_Field $Sellvana_CustomField_Model_Field
 * @property Sellvana_ProductReviews_Model_Review $Sellvana_ProductReviews_Model_Review
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property FCom_Core_Main $FCom_Core_Main
 * @property FCom_Core_Model_MediaLibrary $FCom_Core_Model_MediaLibrary
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
*/
class Sellvana_Catalog_Model_Product extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_product';

    protected static $_cacheAuto = true;

    protected static $_fieldOptions = [
        'stock_status' => [
            'in_stock' => 'In Stock',
            'backorder' => 'On Backorder',
            'special_order' => 'Special Order',
            'do_not_carry' => 'Do Not Carry',
            'temp_unavail' => 'Temporarily Unavailable',
            'vendor_disc' => 'Supplier Discontinued',
            'mfr_disc' => 'MFR Discontinued',
        ],
    ];

    protected static $_validationRules = [
        ['product_name', '@required'],
        ['base_price', '@required'],
        ['product_sku', '@required'],
        ['product_sku', '@string', null, ['max' => 100]],
        ['product_sku', 'Sellvana_Catalog_Model_Product::validateDupSku'],
        ['url_key', 'Sellvana_Catalog_Model_Product::validateDupUrlKey'],
        //TODO validation fails on is_hidden field
        /*array('is_hidden', '@required'),*/
        /*array('uom', '@required'),*/

        /*array('is_hidden', '@integer'),*/
        ['num_reviews', '@integer'],


        ['cost', '@numeric'],
        ['msrp', '@numeric'],
        ['map', '@numeric'],
        ['markup', '@numeric'],
        ['sale_price', '@numeric'],
        ['net_weight', '@numeric'],
        ['ship_weight', '@numeric'],
        ['avg_rating', '@numeric'],
    ];

    protected static $_importExportProfile = [
        'skip' => [
            'create_at',
            'update_at',
            'indextank_indexed',
            'indextank_indexed_at',
        ],
        'unique_key' => 'product_sku'
    ];

    protected $_importErrors = null;
    protected $_dataImport = [];

    protected static $_urlPrefix;

    public function validateDupSku($data, $args)
    {
        if (!empty(static::$_flags['skip_duplicate_checks'])) {
            return true;
        }
        if (empty($data[$args['field']])) {
            return true;
        }
        $orm = $this->orm('p')->where('product_sku', $data[$args['field']]);
        if (!empty($data['id'])) {
            $orm->where_not_equal('p.id', $data['id']);
        }
        if ($orm->find_one()) {
            return $this->BLocale->_('The SKU number entered is already in use. Please enter a valid SKU number.');
        }
        return true;
    }

    public function validateDupUrlKey($data, $args)
    {
        if (!empty(static::$_flags['skip_duplicate_checks'])) {
            return true;
        }
        if (empty($data[$args['field']])) {
            return true;
        }
        $orm = $this->orm('p')->where('url_key', $data[$args['field']]);
        if (!empty($data['id'])) {
            $orm->where_not_equal('p.id', $data['id']);
        }
        if ($orm->find_one()) {
            return $this->BLocale->_('The URL Key entered is already in use. Please enter a valid URL Key.');
        }
        return true;
    }

    public function stockStatusOptions($onlyAvailable = false)
    {
        $options = $this->fieldOptions('stock_status');
        if ($onlyAvailable) {
            return $this->BUtil->arrayMask($options, 'in_stock,backorder,special_order');
        }
        return $options;
    }

    public function urlPrefix()
    {
        if (empty(static::$_urlPrefix)) {
            static::$_urlPrefix = $this->BConfig->get('modules/Sellvana_Catalog/url_prefix');
        }
        return static::$_urlPrefix;
    }

    /**
     * @param Sellvana_Catalog_Model_Category $category
     * @return string
     */
    public function url($category = null)
    {
        $prefix = $this->urlPrefix();
        return $this->BApp->frontendHref($prefix . ($category ? $category->get('url_path') . '/' : '') . $this->get('url_key'));
    }

    public function imageUrl($full = false)
    {
        static $default;

        $media = $this->BConfig->get('web/media_dir');# ? $this->BConfig->get('web/media_dir') : 'media/';
        $url = $full ? $this->BRequest->baseUrl() : '';
        $thumbUrl = $this->get('thumb_url');
        if ($thumbUrl) {
            return $url . $media . '/' . $thumbUrl;
        }

        if (!$default) {
            $default = $this->BConfig->get('modules/Sellvana_Catalog/default_image');
            if ($default) {
                if ($default[0] === '@') {
                    $default = $this->BApp->src($default, 'baseSrc', false);
                }
            } else {
                $default = $url . $media . '/image-not-found.jpg';
            }
        }
        return $default;
    }

    public function thumbUrl($w, $h = null, $full = false)
    {
        return $this->FCom_Core_Main->resizeUrl($this->imageUrl(false), ['s' => $w . 'x' . $h, 'full_url' => $full]);
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        //todo: check out for unique url_key before save
        if (!$this->get('url_key')) $this->generateUrlKey();

        // Cleanup possible bad input
        if ($this->get('sale_price') === '') {
            $this->set('sale_price', null);
        }
        if ($this->get('cost') === '') {
            $this->set('cost', null);
        }
        if ($this->get('msrp') === '') {
            $this->set('msrp', null);
        }
        if ($this->get('map') === '') {
            $this->set('map', null);
        }
        if ($this->get('markup') === '') {
            $this->set('markup', null);
        }

        return true;
    }

    public function onAfterLoad()
    {
        parent::onAfterLoad();
        $thumbPath = $this->FCom_Core_Main->resizeUrl($this->imageUrl(), ['s' => 48]);
        $this->set('thumb_path', $thumbPath);

    }

    public function onAfterSave()
    {
        if (!parent::onAfterSave()) return false;

        $saveAgain = false;

        //todo: setup unique uniq_id
        if (!$this->get('product_sku')) {
            $this->set('product_sku', $this->id());
            $saveAgain = true;
        }
        if (!$this->get('position')) {
            $this->set('position', $this->calcPosition());
        }
        if ($saveAgain) {
            $this->save();
        }

        return true;
    }

    public function calcPosition()
    {
        $maxCurrentPosition = $this->Sellvana_Catalog_Model_Product->orm()->select_expr('max(position) as max_pos')->find_one();
        if (!$maxCurrentPosition) {
            $maxCurrentPosition = 1;
        } else {
            $maxCurrentPosition = $maxCurrentPosition->get('max_pos');
        }
        return $maxCurrentPosition + 1;
    }

    public function generateUrlKey()
    {
        //$key = $this->manuf()->manuf_name.'-'.$this->product_sku.'-'.$this->product_name;
        $key = $this->product_name;
        $urlKey = $this->BLocale->transliterate($key);
        $t = static::$_table;
        $existsSql = "SELECT COUNT(*) as cnt from {$t} WHERE url_key=?";
        if ($this->id()) {
            $existsSql .= ' and id!=' . (int)$this->id();
        }
        $exists = $this->orm()->raw_query($existsSql, [$urlKey])->find_one();
        if ($exists && $exists->cnt > 0) {
            $matchSql        = "SELECT url_key FROM {$t} WHERE url_key LIKE ?";
            if ($this->id()) {
                $matchSql .= ' and id!=' . (int)$this->id();
            }
            $result           = $this->orm()->raw_query($matchSql, [$urlKey . '%'])->find_many();
            $similarUrlKeys = [];
            foreach ($result as $row) {
                $similarUrlKeys[$row->get('url_key')] = 1;
            }

            for ($i = 1; $i < 1001; $i++) {
                $tmp = $urlKey . '-' . $i;
                if (!isset($similarUrlKeys[$tmp])) {
                    $urlKey = $tmp;
                    break;
                }
            }
        }
        $this->set('url_key', $urlKey);
        return $this;
    }

    public function isDisabled()
    {
        return $this->is_hidden;
    }

    public function onAssociateCategory($args)
    {
        $catId = $args['id'];
        $prodIds = $args['ref'];
        //todo
    }

    /**
     * @param $products
     * @param bool $includeCategories
     * @return array
     */
    public function prepareApiData($products, $includeCategories = false)
    {
        if (!is_array($products)) {
            $products = [$products];
        }
        $result = [];
        foreach ($products as $i => $product) {
            $result[$i] = [
                'id'                => $product->id,
                'product_name'      => $product->product_name,
                'sku'               => $product->product_sku,
                'price'             => $product->base_price,
                'url'               => $product->url_key,
                'weight'            => $product->weight,
                'short_description' => !empty($product->short_description) ? $product->short_description : '',
                'description'       => $product->description,
            ];
            if ($includeCategories) {
                $categories = $product->categories();
                $result[$i]['categories'] = $this->Sellvana_Catalog_Model_Category->prepareApiData($categories);
            }
        }
        return $result;
    }

    public function formatApiPost($post)
    {
        $data = [];
        if (!empty($post['product_name'])) {
            $data['product_name'] = $post['product_name'];
        }
        if (!empty($post['sku'])) {
            $data['product_sku'] = $post['sku'];
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
     * @param bool $includeAscendants
     * @return mixed
     */
    public function categories($includeAscendants = false)
    {
        $categories = $this->Sellvana_Catalog_Model_CategoryProduct->orm('cp')
            ->join('Sellvana_Catalog_Model_Category', ['cp.category_id', '=', 'c.id'], 'c')
            ->where('cp.product_id', $this->id())->find_many_assoc();

        if ($includeAscendants) {
            $ascIds = [];
            foreach ($categories as $cat) {
                foreach (explode('/', $cat->id_path) as $id) {
                    if ($id > 1 && empty($categories[$id])) {
                        $ascIds[$id] = 1;
                    }
                }
            }
            if ($ascIds) {
                $hlp = $this->Sellvana_Catalog_Model_CategoryProduct;
                $ascendants = $this->Sellvana_Catalog_Model_Category->orm()->where_in('id', array_keys($ascIds))->find_many();
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
        return $this->Sellvana_CustomField_Model_ProductField->productFields($product);
    }
*/

    /**
     * @return array
     */
    public function customFieldsShowOnFrontend()
    {
        $result = [];
        $fields = $this->Sellvana_CustomField_Model_ProductField->productFields($this);
        if ($fields) {
            foreach ($fields as $f) {
                if ($f->get('frontend_show')) {
                    $result[] = $f;
                }
            }
        }
        return $result;
    }

    /**
     * @param string $q
     * @param array $filter
     * @param null $category
     * @return BORM
     */
    public function searchProductOrm($q = '', $filter = [], $category = null)
    {
        $qs = preg_split('#\s+#', $q, 0, PREG_SPLIT_NO_EMPTY);

        if ($category && is_object($category)) {
            $productsORM = $category->productsORM();
        } else {
            $productsORM = $this->orm();
        }

        $and = [];
        if ($qs) {
            foreach ($qs as $k) $and[] = ['product_name like ?', '%' . (string)$k . '%'];
            $productsORM->where(['OR' => ['product_sku' => (string)$q, 'AND' => $and]]);
        }

        if (!empty($filter)) {
            foreach ($filter as $field => $fieldVal) {
                if (is_array($fieldVal)) {
                    $productsORM->where_in($field, array_values($fieldVal));
                } else {
                    $productsORM->where($field, $fieldVal);
                }
            }
        }
        return $productsORM;
    }


    /**
     * @param $type
     * @return ORM
     */
    public function mediaORM($type)
    {
        return $this->Sellvana_Catalog_Model_ProductMedia->orm()->table_alias('pa')
            ->where('pa.product_id', $this->id)->where('pa.media_type', $type)
            //->select(array('pa.manuf_vendor_id'))
            ->join('FCom_Core_Model_MediaLibrary', ['a.id', '=', 'pa.file_id'], 'a')
            ->select(['a.id', 'a.folder', 'a.subfolder', 'a.file_name', 'a.file_size', 'pa.label'])
            ->order_by_asc('position');
    }

    /**
     * @param $type
     * @return mixed
     */
    public function media($type)
    {
        return $this->mediaORM($type)->find_many_assoc();
    }

    /**
     * @param array $data
     * @param array $config
     * @return array|null
     */
    public function import($data, $config = [])
    {
        if (empty($data) || !is_array($data)) {
            return null;
        }
//        $this->BResponse->startLongResponse(false);
        //HANDLE CONFIG

        $this->BEvents->fire(__METHOD__ . ':before', ['data' => &$data, 'config' => &$config]);

        //multi value separator used to separate values in one column like for images
        //For example: image.png; image2.png; image3.png
        if (!isset($config['format']['multivalue_separator'])) {
            $config['format']['multivalue_separator'] = ';';
        }
        $ms = $config['format']['multivalue_separator'];

        //nesting level separator used to separate nesting of categories
        //For example: Category1 > Category2; Category3 > Category4 > Category5;
        if (!isset($config['format']['nesting_separator'])) {
            $config['format']['nesting_separator'] = '>';
        }

        $ns = $config['format']['nesting_separator'];

        //product import actions: create, update, create_or_update
        if (!isset($config['import']['actions'])) {
            $config['import']['actions'] = 'create_or_update';
        }

        //import images - default true
        if (!isset($config['import']['images']['import'])) {
            $config['import']['images']['import'] = true;
        }

        //reatain image subfolders - default true
        if (!isset($config['import']['images']['with_subfolders'])) {
            $config['import']['images']['with_subfolders'] = true;
        }

        if (!isset($config['import']['images']['url_thumb_prefix'])) {
            $config['import']['images']['url_thumb_prefix'] = 'product/image/';
        }

        // import related products - default true
        if (!isset($config['import']['related']['import'])) {
            $config['import']['related']['import'] = true;
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
        $result = [];
        //$result['status'] = '';

        $customFieldsOptions = $this->Sellvana_CustomField_Model_FieldOption->getListAssoc();

        //HANDLE IMPORT
        static $cfIntersection = '';
        $customFields = [];
        $productIds = [];
        $errors = [];
        $relatedProducts = [];
        for ($i = 0, $c = count($data); $i < $c; $i++) {
            $d = $data[$i];
            // if must have fields not defined, then skip the record
            if (empty($d['product_name']) || empty($d['product_sku'])) {
                $errors[] = sprintf("Missing product name or product sku: %s ...", substr(print_r($d, 1), 0, 50));
                continue;
            }

            $categoriesPath = [];
            if ($config['import']['categories']['import'] && !empty($d['categories'])) {
                $categoriesPath = explode($ms, $d['categories']);
                unset($d['categories']);
            }

            $imagesNames = [];
            if ($config['import']['images']['import'] && !empty($d['images'])) {
                $imagesNames = explode($ms, $d['images']);
                unset($d['images']);
            }

            if (!empty($config['import']['images']['url_thumb_prefix']) && !empty($d['thumb_url'])) {
                if (!strpos($d['thumb_url'], $config['import']['images']['url_thumb_prefix']) !== 0) {
                    $d['thumb_url'] = $config['import']['images']['url_thumb_prefix'] . $d['thumb_url'];
                }
            }


            //HANDLE CUSTOM FIELDS
            if ($config['import']['custom_fields']['import']) {
                //find intersection of custom fields with data fields
                    $cfFields = $this->Sellvana_CustomField_Model_Field->getListAssoc();
                    $cfKeys = array_keys($cfFields);
                    $dataKeys = array_keys($d);
                    $cfIntersection = array_intersect($cfKeys, $dataKeys);

                    if ($cfIntersection) {
                        //get custom fields values from data
                        foreach ($cfIntersection as $cfk) {
                            $field = $cfFields[$cfk];
                            $dataValue = $d[$cfk];
                            if ($config['import']['custom_fields']['create_missing_options']) {
                                //create missing custom field options
                                if (!empty($customFieldsOptions[$field->id()])) {
                                    if (!in_array($dataValue, $customFieldsOptions[$field->id()])) {
                                        try {
                                            $this->Sellvana_CustomField_Model_FieldOption->orm()
                                                    ->create(['field_id' => $field->id(), 'label' => $dataValue])
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
                if (isset($d['product_sku'])) {
                    $p = $this->orm()->where("product_sku", $d['product_sku'])->find_one();
                }
//                if (!$p && isset($d['product_name'])) {
//                    $p = $this->orm()->where("product_name", $d['product_name'])->find_one();
//                }
                if (!$p && isset($d['url_key'])) {
                    $p = $this->orm()->where("url_key", $d['url_key'])->find_one();
                }
            }
            /** @var Sellvana_Catalog_Model_Product $p */
            if (!$p && 'update' == $config['import']['actions']) {
                continue;
            } elseif (!$p) {
                try {
                    $p = $this->create($d)->save();
                    $result[]['status'] = 'created';
                } catch (Exception $e) {
                    $this->BDebug->log($e->getMessage());
                    $errors[] = $e->getMessage();
                    $result[]['status'] = 'error';
                    continue;
                }
            } else {
                $result[]['status'] = 'updated';
            }
            $pId = $p->id();

            //$memstart = memory_get_usage();
            //echo $memstart/1024 . "kb<br>";
            if ($config['import']['related']['import'] && !empty($d['related'])) {
                $relatedProducts[$pId] = explode($ms, $d['related']);
                unset($d['related']);
            }

            $p->set($d);
            if ($p->is_dirty()) {
                $p->save();
            }

            //set custom fields for product
            if (!empty($cfIntersection)) {
                foreach ($cfIntersection as $cfk) {
                    $customFields[$pId][$cfk] = $d[$cfk];
                }
            }

            //echo memory_get_usage()/1024 . "kb<br>";
            //echo (memory_get_usage()-$memstart)/1024 . "kb - diff<br><hr/>";

            //HANDLE CATEGORIES
            if (!empty($categoriesPath)) {

                //check if parent category exist
                static $topParentCategory = '';
                static $categoriesList = [];
                if (!$topParentCategory) {
                    $topParentCategory = $this->Sellvana_Catalog_Model_Category->orm()->where_null("parent_id")->find_one();
                    if (!$topParentCategory) {
                        try {
                            $topParentCategory = $this->Sellvana_Catalog_Model_Category->orm()
                                    ->create(['parent_id' => null])
                                    ->save();
                        } catch (Exception $e) {
                            $errors[] = $e->getMessage();
                        }
                    }
                    $categoriesList = $this->Sellvana_Catalog_Model_Category->parentNodeList();
                }
                if ($topParentCategory) {
                    //check if categories exists
                    //create new categories if not
                    $categories = [];
                    foreach ($categoriesPath as $catpath) {
                        /** @var Sellvana_Catalog_Model_Category $parent */
                        $parent = $topParentCategory;
                        $catNodes = explode($ns, $catpath);
                        /*print_r($catpath);
                        echo "\n";
                        print_r($ns);
                        echo "\n";
                        print_r($catNodes);
                         *
                         */
                        foreach ($catNodes as $catnode) {
                    /*        $category = $this->Sellvana_Catalog_Model_Category->orm()
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
                            $cId                = $category->id();
                            $categories[$cId] = $category;
                            if ($config['import']['categories']['menu'] && $categories[$cId]->inMenu() == false) {
                                $categories[$cId]->setInMenu(true);
                            }
                        }

                    }

                    //assign products to categories
                    if (!empty($categories)) {
                        foreach ($categories as $category) {
                            $catProduct = $this->Sellvana_Catalog_Model_CategoryProduct->orm()
                                    ->where('product_id', $pId)
                                    ->where('category_id', $category->id())
                                    ->find_one();
                            if (!$catProduct) {
                                try {
                                    $this->Sellvana_Catalog_Model_CategoryProduct->orm()
                                        ->create(['product_id' => $pId, 'category_id' => $category->id()])
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
                $imagesConfig = !empty($config['import']['images']) ? $config['import']['images'] : [];
                $imagesResult = $this->importImages($imagesNames, $imagesConfig, $p);
                if (is_array($imagesResult)) {
                    $errors[] += $imagesResult;
                }
            }

            $productIds[] = $pId;
        }

        //HANDLE CUSTOM FIELDS to product relations
        if ($config['import']['custom_fields']['import']
            && !empty($cfIntersection) && !empty($productIds) && !empty($cfFields)) {
            //get custom fields values from data
            $fieldIds = [];
            foreach ($cfIntersection as $cfk) {
                $field = $cfFields[$cfk];
                $fieldIds[] = $field->id();
            }

            //get or create product custom field
            $customsResult = $this->Sellvana_CustomField_Model_ProductField->orm()->where_in("product_id", $productIds)->find_many();
            foreach ($customsResult as $cus) {
                $customsResult[$cus->product_id] = $cus;
            }
            $productCustomFields = [];
            foreach ($productIds as $pId) {
                if (!empty($customFields[$pId])) {
                    $productCustomFields = $customFields[$pId];
                }
                $productCustomFields['_add_field_ids'] = implode(",", $fieldIds);
                $productCustomFields['product_id'] = $pId;
                if (!empty($customsResult[$pId])) {
                    $custom = $customsResult[$pId];
                } else {
                    $custom = $this->Sellvana_CustomField_Model_ProductField->create();
                }
                $custom->set($productCustomFields);
                $custom->save();
                unset($custom);
            }
            unset($customFields);
            unset($customsResult);
        }

        if (!empty($relatedProducts)) {
            $relatedResult = $this->_importRelatedProducts($relatedProducts);
            if (is_array($relatedResult)) {
                $errors[] += $relatedResult;
            }
        }
        unset($data);
        $this->_importErrors = $errors;
        if ($errors) {
            $result['errors'] = $errors;
        }
        $this->BEvents->fire(__METHOD__ . ':after', ['product_ids' => $productIds, 'config' => &$config, 'result' => &$result]);

        return $result;
    }

    /**
     * @param $categoryIds
     * @return $this
     */
    public function addToCategories($categoryIds)
    {
        $hlp = $this->Sellvana_Catalog_Model_CategoryProduct;
        foreach ((array)$categoryIds as $cId) {
            $hlp->create(['product_id' => $this->id, 'category_id' => $cId])->save();
        }
        return $this;
    }

    /**
     * @param $categoryIds
     * @return $this
     */
    public function removeFromCategories($categoryIds)
    {
        $this->Sellvana_Catalog_Model_CategoryProduct->delete_many(['product_id' => $this->id, 'category_id' => $categoryIds]);
        return $this;
    }

    /**
     * @return float
     */
    public function getAverageStars()
    {
        return $this->get('avg_rating');;
    }

    /**
     * @return float
     */
    public function getAverageRatingPercent()
    {
        return $this->get('avg_rating') / 5 * 100;
    }

    /**
     * @return mixed
     */
    public function getNumReviews()
    {
        return $this->get('num_reviews');
    }

    /**
     * @param bool $incAvgRating
     * @return array
     */
    public function reviews($incAvgRating = true)
    {
        $reviews = $this->Sellvana_ProductReviews_Model_Review->orm('pr')->select(['pr.*', 'c.firstname', 'c.lastname'])
            ->join('Sellvana_Customer_Model_Customer', ['pr.customer_id', '=', 'c.id'], 'c')
            ->where(['pr.product_id' => $this->id(), 'approved' => 1])->order_by_expr('pr.create_at DESC')->find_many();

        if ($incAvgRating) {
            $avgRating = $this->calcAverageRating($reviews);
        }
        return [
            'items' => $reviews,
            'avgRating' => isset($avgRating) ? $avgRating : [],
            'numReviews' => count($reviews),
        ];
    }

    /**
     * @param array $reviews
     * @return array
     */
    public function calcAverageRating($reviews = [])
    {
        $rs = [
            'rating' => 0,
            'rating1' => 0,
            'rating2' => 0,
            'rating3' => 0,
        ];
        if (!empty($reviews)) {
            $numReviews = count($reviews);
            foreach ($reviews as $review) {
                $rs['rating'] += $review->rating;
                $rs['rating1'] += $review->rating1;
                $rs['rating2'] += $review->rating2;
                $rs['rating3'] += $review->rating3;
            }

            $rs['rating'] = number_format($rs['rating'] / $numReviews, 2);
            $rs['rating1'] = number_format($rs['rating1'] / $numReviews, 2);
            $rs['rating2'] = number_format($rs['rating2'] / $numReviews, 2);
            $rs['rating3'] = number_format($rs['rating3'] / $numReviews, 2);
        }

        return $rs;
    }

    /**
     * @return array
     */
    public function getProductLinks()
    {
        $arrProduct = $this->Sellvana_Catalog_Model_Product->orm('p')->select('pl.link_type')
            ->left_outer_join('Sellvana_Catalog_Model_ProductLink', ['p.id', '=', 'pl.linked_product_id'], 'pl')
            ->where('pl.product_id', $this->id)->find_many();
        $productLink = [
            'related'=> ['title' => $this->BLocale->_('Related Products'), 'products' => [] ],
            'similar' => ['title' => $this->BLocale->_('You may also like these items'), 'products' => [] ],
            'cross_sell' => ['title' => $this->BLocale->_('You may also like these items'), 'products' => [] ]
        ];
        foreach ($arrProduct as $product) {
            if (isset($productLink[$product->get('link_type')])) {
                array_push($productLink[$product->get('link_type')]['products'], $product);
            }
        }
        return $productLink;
    }

    /**
     * get options data to create options html in select
     * @param bool $labelIncId
     * @return array
     */
    public function getOptionsData($labelIncId = false)
    {
        $results = $this->orm('p')->find_many();
        $data = [];
        if (count($results)) {
            foreach ($results as $r) {
                $data[$r->id] = $labelIncId ? $r->id . ' - ' . $r->product_name : $r->product_name;
            }
        }

        return $data;
    }

    /**
     * check customer has already reviewed this product
     * @param  $customerId
     * @return boolean|Sellvana_ProductReviews_Model_Review
     */
    public function isAlreadyReviewed($customerId)
    {
        return $this->Sellvana_ProductReviews_Model_Review->loadWhere(['product_id' => $this->id, 'customer_id' => (int)$customerId]);
    }

    /**
     * Create product relations from import
     *
     * Import initially collects all imported products relations,
     * to be sure that relations are created regardless of import order of products
     * $relatedProducts is an array with all imported product ids as keys and all
     * related product skus as values
     *
     * [ 2 => [ 'rel_sku_1', 'rel_sku_2', ... ] ]
     *
     * Returns either true if no errors occurred during process, or an array with error messages
     *
     * @param array $relatedProducts
     * @return array|bool
     */
    protected function _importRelatedProducts($relatedProducts)
    {
        $relatedIds = [];
        $relation   = $this->Sellvana_Catalog_Model_ProductLink;
        $errors     = [];
        try {
            foreach ($relatedProducts as $pId => $relatedSkus) {
                $temp   = [];
                $linked = [];

                foreach ($relatedSkus as $sku) {
                    // loop $relatedSkus and if they are not in fetched ids, add them to $temp to retrieve them
                    // if id is fetched already then add it to $linked array
                    if (!isset($relatedIds[$sku])) {
                        $temp[] = $sku;
                    } else {
                        $linked[] = $relatedIds[$sku];
                    }
                }

                if (!empty($temp)) {
                    // fetch related sku objects
                    $related = $this->orm()->where_in("product_sku", $temp)
                                    ->find_many();

                    foreach ($related as $r) {
                        /* @var Sellvana_Catalog_Model_Product $r */
                        $linked[] = $r->id();
                        $relatedIds[$r->product_sku] = $r->id();
                    }
                }

                foreach ($linked as $rId) {
                    // try to create links of type 'related'
                    try {
                        $relation->create(
                                 [
                                     'link_type'         => 'related',
                                     'product_id'        => $pId,
                                     'linked_product_id' => $rId,
                                 ]
                        );
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }

                } // end foreach $linked

            } // end foreach $relatedProducts

        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        return empty($errors) ? true : $errors;
    }

    /**
     * @todo Fix hardcoded folder names
     * @param $config
     * @param $imagesNames
     * @param Sellvana_Catalog_Model_Product $p
     * @return array|bool
     */
    public function importImages($imagesNames, $config = [], $p = null)
    {
        if (is_null($p)) {
            $p = $this;
        }
        $mediaLib     = $this->FCom_Core_Model_MediaLibrary;
        $productMedia = $this->Sellvana_Catalog_Model_ProductMedia;
        $rootDir      = $this->BConfig->get('fs/root_dir');
        $imageFolder  = $this->BConfig->get('fs/image_folder');
        $thumbUrl = str_ireplace('media/product/image', '', $p->get('thumb_url'));
        $errors = [];

        foreach ($imagesNames as $fileName) {
            $pathInfo  = pathinfo($fileName);
            $subFolder = $pathInfo['dirname'] == '.' ? null : $pathInfo['dirname'];
            $att       = $mediaLib->loadWhere([
                'folder'    => (string)$imageFolder,
                'subfolder' => (string)$subFolder,
                'file_name' => (string)$pathInfo['basename']
            ]);
            if (!$att) {
                $fullPathToFile = $rootDir . '/' . $imageFolder . '/' . $fileName;
                $size           = 0;
                if (file_exists($fullPathToFile)) {
                    $size = filesize($fullPathToFile);
                }

                $subFolder = null;
                if (!empty($config['with_subfolders'])) {
                    $subFolder = $pathInfo['dirname'] == '.' ? null : $pathInfo['dirname'];
                }
                try {
                    $att = $mediaLib->create([
                        'folder'    => $imageFolder,
                        'subfolder' => $subFolder,
                        'file_name' => $pathInfo['basename'],
                        'file_size' => $size,
                    ])->save();
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
            $fileId = $productMedia->orm()->where('product_id', $p->id())
                                   ->where('file_id', $att->id())->find_one();
            $isThumb = ('product/image/' . $fileName == $thumbUrl);
            if (!$fileId) {
                try {
                    $productMedia->create([
                        'product_id' => $p->id(),
                        'media_type' => Sellvana_Catalog_Model_ProductMedia::MEDIA_TYPE_IMG,
                        'file_id'    => $att->id(),
                        'main_thumb' => $isThumb ? 1 : 0
                    ])->save();
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            } else if ($fileId->get('main_thumb') == 0 && $isThumb) {
                $fileId->set('main_thumb', 1)->save();
            }
        }
        return empty($errors) ? true : $errors;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        if ($this->get('sale_price')) {
            return $this->get('sale_price');
        }
        return $this->get('base_price');
    }

    public function getFrontendPrices()
    {
        /**
         * - type: old, new, reg, msrp, extax
         * - label: Old Price, New Price, Price, MSRP, Ex.Tax
         * - value: 1234.56
         * - formatted: $1,234.56
         * - pos: 0,1,2,3
         */
        $prices = [];

        if ($this->get('sale_price') !== null) {
            $prices['base'] = ['type' => 'old', 'label' => 'Was', 'pos' => 10, 'value' => $this->get('base_price')];
            $prices['sale'] = ['type' => 'new', 'label' => 'Price', 'pos' => 20, 'value' => $this->get('sale_price'), 'final' => 1];
        } else {
            $prices['base'] = ['type' => 'reg', 'label' => 'Price', 'pos' => 10, 'value' => $this->get('base_price'), 'final' => 1];
        }

        $this->BEvents->fire(__METHOD__, ['product' => $this, 'prices' => &$prices]);

        uasort($prices, function($v1, $v2) {
            $p1 = !empty($v1['pos']) ? $v1['pos'] : 999;
            $p2 = !empty($v2['pos']) ? $v2['pos'] : 999;
            return $p1 < $p2 ? -1 : ($p1 > $p2 ? 1 : 0);
        });
        return $prices;
    }

    public function getAllPrices()
    {
        $priceModel= $this->Sellvana_Catalog_Model_ProductPrice;
        $prices = $priceModel->getProductPrices($this);
        return $prices;
    }

    /**
     * @return array
     */
    public function backOrders()
    {
        return [
            "NOT_BACK_ORDERS"         => $this->BLocale->_("No Back Orders"),
            "ALLOW_QUANTITY_BELOW" => $this->BLocale->_("Allow Quantity Below 0")
        ];
    }

    /**
     * @return Sellvana_Catalog_Model_InventorySku
     * @throws BException
     */
    public function getInventoryModel()
    {
        $invModel = $this->get('inventory_model');
        if ($invModel) {
            return $invModel;
        }
        $invHlp = $this->Sellvana_Catalog_Model_InventorySku;
        // get inventory SKU from inventory SKU or product SKU if not specified
        $invSku = $this->get('inventory_sku');
        if (null === $invSku || '' === $invSku) {
            $invSku = $this->get('product_sku');
            if (!$invSku) {
                $invModel = $invHlp->create();
                $this->set('inventory_model', $invModel);
                return $invModel;
            }
            $this->set('inventory_sku', $invSku);
        }
        // find inventory model
        $invModel = $invHlp->load($invSku, 'inventory_sku');
        // if doesn't exist yet, create
        if (!$invModel) {
            $invModel = $invHlp->create(['inventory_sku' => $invSku, 'title' => $this->get('product_name')])->save();
        }
        $this->set('inventory_model', $invModel);
        return $invModel;
    }
}
