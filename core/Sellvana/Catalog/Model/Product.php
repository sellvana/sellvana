<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Model class for table "fcom_product".
 *
 * The followings are the available columns in table 'fcom_product':
 *
 * @property string  $id
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
 * @property FCom_Core_Main                          $FCom_Core_Main
 * @property FCom_Core_Model_MediaLibrary            $FCom_Core_Model_MediaLibrary
 * @property Sellvana_Catalog_Model_Category         $Sellvana_Catalog_Model_Category
 * @property Sellvana_Catalog_Model_CategoryProduct  $Sellvana_Catalog_Model_CategoryProduct
 * @property Sellvana_Catalog_Model_InventorySku     $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Catalog_Model_Product          $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductLink      $Sellvana_Catalog_Model_ProductLink
 * @property Sellvana_Catalog_Model_ProductMedia     $Sellvana_Catalog_Model_ProductMedia
 * @property Sellvana_Catalog_Model_ProductPrice     $Sellvana_Catalog_Model_ProductPrice
 * @property Sellvana_Customer_Model_Customer        $Sellvana_Customer_Model_Customer
 * @property Sellvana_CustomerGroups_Model_Group     $Sellvana_CustomerGroups_Model_Group
 * @property Sellvana_ProductReviews_Model_Review    $Sellvana_ProductReviews_Model_Review
 * @property Sellvana_MultiSite_Frontend             $Sellvana_MultiSite_Frontend
 * @property Sellvana_MultiCurrency_Main             $Sellvana_MultiCurrency_Main
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
        'rollover_effects' => [
            'fade' => 'Fade',
            'clip' => 'Clip',
            'blind' => 'Blinds',
            'drop' => 'Drop',
            'fold' => 'Fold',
            'highlight' => 'Highlight',
            'puff' => 'Puff',
            'pulsate' => 'Pulsate',
            'slide' => 'Slide'
        ],
    ];

    protected static $_validationRules = [
        ['product_name', '@required'],
        //['base_price', '@required'],
        ['product_sku', '@required'],
        ['product_sku', '@string', null, ['max' => 100]],
        ['product_sku', 'Sellvana_Catalog_Model_Product::validateDupSku'],
        ['url_key', 'Sellvana_Catalog_Model_Product::validateDupUrlKey'],
        //TODO validation fails on is_hidden field
        /*array('is_hidden', '@required'),*/
        /*array('uom', '@required'),*/

        /*array('is_hidden', '@integer'),*/
        ['num_reviews', '@integer'],

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
        'unique_key' => 'product_sku',
        'custom_data' => true,
    ];

    protected static $_dataFieldsMap = [
        'product_name_lang_fields' => 'name_lang_fields',
        'short_description_lang_fields' => 'short_desc_lang_fields',
        'description_lang_fields' => 'desc_lang_fields',
    ];

    protected $_importErrors = null;
    protected $_dataImport = [];

    protected $_priceModels;

    protected $_images;

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
            $prefix = $this->BConfig->get('modules/Sellvana_Catalog/url_prefix');
            switch ($this->BConfig->get('web/language_in_url')) {
                case 'lang':
                    $prefix .= $this->BLocale->getCurrentLanguage() . '/';
                    break;
                case 'locale':
                    $prefix .= $this->BLocale->getCurrentLocale() . '/';
                    break;
            }
            static::$_urlPrefix = $prefix;
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

    public function imageUrl($full = false, $imgType = 'default')
    {
        static $default;

        $media = $this->BConfig->get('web/media_dir');# ? $this->BConfig->get('web/media_dir') : 'media/';
        //$url = $full ? $this->BRequest->baseUrl() : $this->BRequest->webRoot();// what is the point in this? Image resources are always in same path
        $url = '';
        //$thumbUrl = $this->get('thumb_url');
        //if ($thumbUrl) {
        //    return $url . $media . '/' . $thumbUrl;
        //}
        switch ($imgType) {
            case 'thumb':
                $thumbUrl = $this->getThumbPath();
                break;
            case 'rollover':
                $thumbUrl = $this->getRolloverPath();
                break;
            default :
                $thumbUrl = $this->getDefaultImagePath();
                break;
        }
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

    public function defaultImgUrl($w, $h = null, $full = false)
    {
        $imgUrl = $this->imageUrl(false, 'default');
        $resizedUrl = $this->FCom_Core_Main->resizeUrl($imgUrl, ['s' => $w . 'x' . $h, 'full_url' => $full]);
        return $resizedUrl;
    }

    public function thumbUrl($w, $h = null, $full = false)
    {
        $imgUrl = $this->imageUrl(false, 'thumb');
        $resizedUrl = $this->FCom_Core_Main->resizeUrl($imgUrl, ['s' => $w . 'x' . $h, 'full_url' => $full]);
        return $resizedUrl;
    }

    public function rolloverUrl($w, $h = null, $full = false)
    {
        $imgUrl = $this->imageUrl(false, 'rollover');
        $resizedUrl = $this->FCom_Core_Main->resizeUrl($imgUrl, ['s' => $w . 'x' . $h, 'full_url' => $full]);
        return $resizedUrl;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        //todo: check out for unique url_key before save
        if (!$this->get('url_key')) $this->generateUrlKey();

        // Cleanup possible bad input
        //if ($this->get('sale_price') === '') {
        //    $this->set('sale_price', null);
        //}
        //if ($this->get('cost') === '') {
        //    $this->set('cost', null);
        //}
        //if ($this->get('msrp') === '') {
        //    $this->set('msrp', null);
        //}
        //if ($this->get('map') === '') {
        //    $this->set('map', null);
        //}
        //if ($this->get('markup') === '') {
        //    $this->set('markup', null);
        //}

        return true;
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
            $saveAgain = true;
        }
        if ($saveAgain) {
            $this->save(false);
        }
        $this->Sellvana_Catalog_Model_ProductPrice->parseAndSaveDefaultPrices($this);

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
        $key = $this->get('product_name');
        $urlKey = $this->BLocale->transliterate($key);
        $t = $this->BDb->t(static::$_table);
        $existsSql = "SELECT COUNT(*) as cnt from {$t} WHERE url_key=?";
        if ($this->id()) {
            $existsSql .= ' and id!=' . (int)$this->id();
        }
        $exists = $this->orm()->raw_query($existsSql, [$urlKey])->find_one();
        if ($exists && $exists->cnt > 0) {
            $matchSql = "SELECT url_key FROM {$t} WHERE url_key LIKE ?";
            if ($this->id()) {
                $matchSql .= ' and id!=' . (int)$this->id();
            }
            $result = $this->orm()->raw_query($matchSql, [$urlKey . '%'])->find_many();
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
     * @return BORM
     */
    public function mediaORM($type)
    {
        $orm = $this->Sellvana_Catalog_Model_ProductMedia->orm('pa')
                    ->where('pa.product_id', $this->id)
                    ->join('FCom_Core_Model_MediaLibrary', ['a.id', '=', 'pa.file_id'], 'a')
                    ->select(['a.id', 'a.folder', 'a.subfolder', 'a.file_name', 'a.file_size', 'pa.label', 'pa.media_type', 'a.data_serialized']);

        if (is_array($type)) {
            list($I, $V) = $type;
            // $orm->where_raw("pa.media_type = '$I' OR (pa.media_type = '$V' AND pa.is_default = 1)")
            $orm->where_raw("(pa.media_type = '$I' OR pa.media_type = '$V')")
                ->order_by_desc('pa.media_type')
                ->order_by_asc('position');
        } else {
            $orm->where('pa.media_type', $type)
                ->order_by_asc('position');
        }

        return $orm;
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
     * @param bool|false $isVideoIncluded
     */
    public function gallery($isVideoIncluded = false)
    {
        $type = 'I';
        if ($isVideoIncluded) {
            $type = ['I', 'V'];
        }

        $mediaItems = $this->mediaORM($type)
            ->where(["pa.in_gallery" => 1])
            ->find_many_assoc();

        // Remove default width and height for responsive
        foreach ($mediaItems as $k => $media) {
            if (!empty($media->data_serialized)) {
                $mediaItems[$k]->data_serialized = preg_replace('/(width=\\\"\d+\\\")|(height=\\\"\d+\\\")/', '',
                    $media->data_serialized);
            }
        }

        return $mediaItems;
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
            $this->BEvents->fire(__METHOD__ . ':row', ['config' => $config, 'data' => $d]);

            //HANDLE PRODUCT
            $p = false;
            if ('create_or_update' == $config['import']['actions'] || 'update' == $config['import']['actions']) {
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
        $this->BEvents->fire(__METHOD__ . ':after_loop', ['config' => $config, 'data' => $d]);

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
     * @param bool|int $limit
     * @param bool $incAvgRating
     * @param bool|int $filterByRating
     * @return array
     */
    public function reviews($limit = false, $incAvgRating = true, $filterByRating = false)
    {
        $numReviews = 0;
        $pageData = [];

        if ($this->BModuleRegistry->isLoaded('Sellvana_ProductReviews')) {
            $ratings = $this->Sellvana_ProductReviews_Model_Review->orm('pr')->select('pr.rating')->select_expr('COUNT(pr.id)', 'count')
                ->join('Sellvana_Customer_Model_Customer', ['pr.customer_id', '=', 'c.id'], 'c')
                ->where(['pr.product_id' => $this->id(), 'approved' => 1])
                ->group_by('pr.rating')->find_many();

            $stats = $this->getRatingStats($ratings, $incAvgRating);
            $numReviews = $stats['num_reviews'];
            $avgRating = $stats['avg_rating'];
            $ratingByStars = $stats['rating_by_stars'];

            $reviews = $this->Sellvana_ProductReviews_Model_Review->orm('pr')->select(['pr.*', 'c.firstname', 'c.lastname'])
                ->join('Sellvana_Customer_Model_Customer', ['pr.customer_id', '=', 'c.id'], 'c')
                ->where(['pr.product_id' => $this->id(), 'approved' => 1])
                ->order_by_expr('(pr.helpful / pr.helpful_voices) DESC');

            if ((int)$filterByRating) {
                $reviews->where('rating', (int)$filterByRating);
            }

            if ((int)$limit) {
                $reviews->limit($limit);
            }

            $reviews = $reviews->order_by_expr('pr.create_at DESC');

            $pageData = $reviews->paginate($this->BRequest->get(), [
                'ps' => 3,
            ]);
        }

        return [
            'items' => $pageData,
            'ratings' => isset($ratingByStars) ? $ratingByStars : [],
            'avgRating' => isset($avgRating) ? $avgRating : [],
            'numReviews' => $numReviews,
        ];
    }

    /**
     * @param array $ratings
     * @param bool $incAvgRating
     * @return array
     */
    public function getRatingStats($ratings = [], $incAvgRating = true)
    {
        $avgRating = false;
        $numReviews = 0;
        $reviewConfig = $this->Sellvana_ProductReviews_Model_Review->config();
        $ratingByStars = [];
        for ($i = $reviewConfig['max']; $i >= $reviewConfig['min']; $i -= $reviewConfig['step']) {
            $ratingByStars[$i] = 0;
        }

        if (!empty($ratings)) {
            foreach ($ratings as $review) {
                $avgRating += $review->rating * $review->count;
                $numReviews += $review->count;
                $ratingByStars[$review->rating] = $review->count;
            }

            $avgRating = trim(number_format($avgRating / $numReviews, 2), '0.');
        }

        return [
            'num_reviews' => $numReviews,
            'avg_rating' => $avgRating,
            'rating_by_stars' => $ratingByStars
        ];
    }

    /**
     * @return array
     */
    public function getProductLinks()
    {
        $arrProduct = $this->Sellvana_Catalog_Model_Product->orm('p')->select(['p.*', 'pl.link_type'])
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
        if (!$this->BModuleRegistry->isLoaded('Sellvana_ProductReviews')) {
            return false;
        }
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
     * @return array
     */
    public function getRolloverEffects()
    {
        // fade,clip,blind, drop, fold, highlight, puff, pulsate,slide
        return $this->fieldOptions('rollover_effects');
    }

    /**
     * @return Sellvana_Catalog_Model_InventorySku
     * @throws BException
     */
    public function getInventoryModel()
    {
        $invModel = $this->get('inventory_model');
        if ($invModel !== null) {
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

    /**
     * @param boolean|Sellvana_Catalog_Model_ProductPrice[] $priceModels
     * @return $this
     */
    public function setPriceModels($priceModels)
    {
        $this->_priceModels = $priceModels;
        return $this;
    }

    /**
     * @param string $type
     * @param array $context
     * @param bool $useDefault
     * @return Sellvana_Catalog_Model_ProductPrice
     */
    public function getPriceModelByType($type, $context = [], $useDefault = true)
    {
        $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
        if (!isset($this->_priceModels)) {
            $priceHlp->collectProductsPrices([$this]);
        }
        return $priceHlp->getPriceModelByType($this->_priceModels, $type, $context, $useDefault);
    }

    /**
     * Get final price of product in catalog
     *
     * @param array $context
     * @return mixed
     */
    public function getCatalogPrice($context = [])
    {
        if (!empty($context['currency_code']) && $context['currency_code'] !== '*') {
            $currency = $context['currency_code'];
        } else {
            $currency = null;
        }

        $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
        if (!isset($this->_priceModels)) {
            $priceHlp->collectProductsPrices([$this]);
        }
        $price = $priceHlp->getCatalogPrice($this->_priceModels, $context);

        $this->BEvents->fire(__METHOD__, [
            'product' => $this,
            'context' => $context,
            'currency' => $currency,
            'price' => &$price,
        ]);

        return $price;
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
        $context = [];

        $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
        if (!isset($this->_priceModels)) {
            $priceHlp->collectProductsPrices([$this]);
        }
        $basePriceModel = $priceHlp->getPriceModelByType($this->_priceModels, 'base', $context);
        $mapPriceModel = $priceHlp->getPriceModelByType($this->_priceModels, 'map', $context);
        $msrpPriceModel = $priceHlp->getPriceModelByType($this->_priceModels, 'msrp', $context);
        $basePrice = $basePriceModel ? $basePriceModel->getPrice() : 0;

        $finalPrice = $this->getCatalogPrice();
        $finalText = null;

        if ($mapPriceModel) {
            $mapPrice = $mapPriceModel->getPrice();
            if ($mapPrice > $finalPrice) {
                $finalText = $this->BLocale->_('Add to cart');
            }
        }

        if ($msrpPriceModel) {
            $msrpPrice = $msrpPriceModel->getPrice();
            $prices['msrp'] = ['type' => 'old', 'label' => 'List Price', 'pos' => 10, 'value' => $msrpPrice];
        }

        if ($finalPrice !== null && $finalPrice < $basePrice) {
            $prices['base'] = ['type' => 'old', 'label' => $msrpPriceModel ? 'Our Price' : 'Price', 'pos' => 20, 'value' => $basePrice];
            $prices['sale'] = ['type' => 'new', 'label' => 'Sale', 'pos' => 30, 'value' => $finalPrice,
                'formatted' => $finalText, 'final' => 1];
        } else {
            $prices['base'] = ['type' => 'reg', 'label' => 'Price', 'pos' => 20, 'value' => $basePrice, 'final' => 1];
        }

        $this->BEvents->fire(__METHOD__, [
            'product' => $this,
            'context' => $context,
            'prices' => &$prices,
            'final_price' => &$finalPrice,
        ]);

        if ($finalPrice !== null && $finalPrice < $basePrice && (!$mapPriceModel || $finalPrice > $mapPrice)) {
            if (!empty($msrpPrice)) {
                $basePrice = max($basePrice, $msrpPrice);
            }
            $diff = $basePrice - $finalPrice;
            $saveText = $this->BLocale->currency($diff) . ' (' . number_format($diff / $basePrice * 100) . '%)';
            $prices['save'] = ['type' => 'save', 'label' => 'You Save', 'pos' => 90, 'formatted' => $saveText];
        }

        uasort($prices, function($v1, $v2) {
            $p1 = !empty($v1['pos']) ? $v1['pos'] : 999;
            $p2 = !empty($v2['pos']) ? $v2['pos'] : 999;
            return $p1 < $p2 ? -1 : ($p1 > $p2 ? 1 : 0);
        });
        return $prices;
    }

    public function getAllTierPrices($context = [])
    {
        return $this->getPriceModelByType('tier', $context);
    }

    /**
     * Get product tier price
     *
     * @param float  $qty quantity of product in cart
     * @param array|boolean $context
     * @return null|float
     */
    public function getTierPrice($qty, $context = [])
    {
        /** @var Sellvana_Catalog_Model_ProductPrice[] $tierPrices */
        $tierPrices = $this->getPriceModelByType('tier', $context);
        if (!$tierPrices) {
            return null;
        }
        $maxTierQty = 0;
        foreach ($tierPrices as $tierQty => $r) {
            if ($tierQty <= $qty) {
                $maxTierQty = max($maxTierQty, $tierQty);
            }
        }
        if ($maxTierQty) {
            return $tierPrices[$maxTierQty]->getPrice();
        }
        return null;
    }

    public function priceTypeOptions()
    {
        return $this->Sellvana_Catalog_Model_ProductPrice->fieldOptions('price_types');
    }

    public function variantPrice($itemPrice, $variant_id)
    {
        //TODO: implement
        return $itemPrice;
    }

    public function getName()
    {
        return $this->get('product_name');
    }

    public function getDescription()
    {
        return $this->get('description');
    }

    public function getShortDescription()
    {
        return $this->get('short_description');
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_priceModels);
    }

    public function setProductImages($images)
    {
        if (empty($this->_images)) {
            $this->_images = $images;
        } else {
            $this->_images = array_merge($this->_images, $images);
        }
        return $this;
    }

    public function getThumbPath()
    {
        if (!isset($this->_images['thumb'])) {
            $this->Sellvana_Catalog_Model_ProductMedia->collectProductsImages([$this]/*, ['thumb']*/);
        }
        return $this->_images['thumb'];
    }

    public function getRolloverPath()
    {
        if (!isset($this->_images['rollover'])) {
            $this->Sellvana_Catalog_Model_ProductMedia->collectProductsImages([$this]/*, ['rollover']*/);
        }
        return $this->_images['rollover'];
    }

    public function getDefaultImagePath()
    {
        if (!isset($this->_images['default'])) {
            $this->Sellvana_Catalog_Model_ProductMedia->collectProductsImages([$this]/*, ['default']*/);
        }
        return $this->_images['default'];
    }
}
