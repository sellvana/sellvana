<?php
/**
 * Model class for table "fcom_product".
 *
 * The followings are the available columns in table 'fcom_product':
 * @property string  $id
 * @property string  $local_sku
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
 */
class FCom_Catalog_Model_Product extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_product';

    protected static $_fieldOptions = array(
        'stock_status' => array(
            'in_stock' => 'In Stock',
            'backorder' => 'On Backorder',
            'special_order' => 'Special Order',
            'do_not_carry' => 'Do Not Carry',
            'temp_unavail' => 'Temporarily Unavailable',
            'vendor_disc' => 'Supplier Discontinued',
            'manuf_disc' => 'MFR Discontinued',
        ),
    );

    protected static $_validationRules = array(
        array('product_name', '@required'),
        array('base_price', '@required'),
        array('local_sku', '@required'),
        array('local_sku', '@string', null, array('max' => 100)),
        array('local_sku', 'FCom_Catalog_Model_Product::validateDupSku'),
        array('url_key', 'FCom_Catalog_Model_Product::validateDupUrlKey'),
        //TODO validation fails on is_hidden field
        /*array('is_hidden', '@required'),*/
        /*array('uom', '@required'),*/

        /*array('is_hidden', '@integer'),*/
        array('num_reviews', '@integer'),


        array('cost', '@numeric'),
        array('msrp', '@numeric'),
        array('map', '@numeric'),
        array('markup', '@numeric'),
        array('sale_price', '@numeric'),
        array('net_weight', '@numeric'),
        array('ship_weight', '@numeric'),
        array('avg_rating', '@numeric'),
    );

    protected static $_importExportProfile = array(
        'skip' => array(
            'create_dt',
            'update_dt',
            'indextank_indexed',
            'indextank_indexed_at',
        ),
        'unique_key' => 'local_sku'
    );

    protected $_importErrors = null;
    protected $_dataImport = array();

    protected static $_urlPrefix;

    /**
     * Shortcut to help with IDE autocompletion
     * @param bool  $new
     * @param array $args
     * @return FCom_Catalog_Model_Product
     */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    public static function validateDupSku($data, $args)
    {
        if (!empty(static::$_flags['skip_duplicate_checks'])) {
            return true;
        }
        if (empty($data[$args['field']])) {
            return true;
        }
        $orm = static::orm('p')->where('local_sku', $data[$args['field']]);
        if (!empty($data['id'])) {
            $orm->where_not_equal('p.id', $data['id']);
        }
        if ($orm->find_one()) {
            return BLocale::_('The SKU number entered is already in use. Please enter a valid SKU number.');
        }
        return true;
    }

    public static function validateDupUrlKey($data, $args)
    {
        if (!empty(static::$_flags['skip_duplicate_checks'])) {
            return true;
        }
        if (empty($data[$args['field']])) {
            return true;
        }
        $orm = static::orm('p')->where('url_key', $data[$args['field']]);
        if (!empty($data['id'])) {
            $orm->where_not_equal('p.id', $data['id']);
        }
        if ($orm->find_one()) {
            return BLocale::_('The URL Key entered is already in use. Please enter a valid URL Key.');
        }
        return true;
    }

    public static function stockStatusOptions($onlyAvailable=false)
    {
        $options = static::fieldOptions('stock_status');
        if ($onlyAvailable) {
            return BUtil::arrayMask($options, 'in_stock,backorder,special_order');
        }
        return $options;
    }

    static public function urlPrefix()
    {
        if (empty(static::$_urlPrefix)) {
            static::$_urlPrefix = BConfig::i()->get('modules/FCom_Catalog/url_prefix');
        }
        return static::$_urlPrefix;
    }

    /**
     * @param FCom_Catalog_Model_Category $category
     * @return string
     */
    public function url($category=null)
    {
        $prefix = static::urlPrefix();
        return BApp::href($prefix . ($category ? $category->get('url_path').'/' : '') . $this->get('url_key'));
    }

    public function imageUrl($full=false)
    {
        $media = BConfig::i()->get('web/media_dir');# ? BConfig::i()->get('web/media_dir') : 'media/';
        $url = $full ? BApp::href('/') : '';
        $thumbUrl = $this->get('thumb_url');
        return $url.$media.'/'.($thumbUrl ? $thumbUrl : 'image-not-found.jpg');
    }

    public function thumbUrl($w, $h=null, $full=false)
    {
        return FCom_Core_Main::i()->resizeUrl().'?f='.urlencode(trim($this->imageUrl($full), '/')).'&s='.$w.'x'.$h;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        //todo: check out for unique url_key before save
        if (!$this->get('url_key')) $this->generateUrlKey();


        if (!$this->get('create_at'))  $this->set('create_at', BDb::now());
        $this->set('update_at', BDb::now());

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
        $thumbPath = FCom_Core_Main::i()->resizeUrl().'?f='.urlencode(trim($this->imageUrl(), '/')).'&s=48x48';
        $this->set('thumb_path', $thumbPath);

    }

    public function onAfterSave()
    {
        if (!parent::onAfterSave()) return false;

        $saveAgain = false;

        //todo: setup unique uniq_id
        if (!$this->get('local_sku')) {
            $this->set('local_sku', $this->id);
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
        $maxCurrentPosition = FCom_Catalog_Model_Product::i()->orm()->select_expr('max(position) as max_pos')->find_one();
        if (!$maxCurrentPosition) {
            $maxCurrentPosition = 1;
        } else {
            $maxCurrentPosition = $maxCurrentPosition->get('max_pos');
        }
        return $maxCurrentPosition + 1;
    }

    public function generateUrlKey()
    {
        //$key = $this->manuf()->manuf_name.'-'.$this->local_sku.'-'.$this->product_name;
        $key = $this->product_name;
        $urlKey = BLocale::transliterate( $key );
        $t = static::$_table;
        $existsSql = "SELECT COUNT(*) as cnt from {$t} WHERE url_key=?";
        if ($this->id()) {
            $existsSql .= ' and id!='.(int)$this->id();
        }
        $exists = $this->orm()->raw_query( $existsSql, array( $urlKey ) )->find_one();
        if ( $exists && $exists->cnt > 0 ) {
            $matchSql        = "SELECT url_key FROM {$t} WHERE url_key LIKE ?";
            if ($this->id()) {
                $matchSql .= ' and id!='.(int)$this->id();
            }
            $result           = $this->orm()->raw_query( $matchSql, array( $urlKey . '%' ) )->find_many();
            $similarUrlKeys = array();
            foreach ( $result as $row ) {
                $similarUrlKeys[ $row->get( 'url_key' ) ] = 1;
            }

            for ( $i = 1; $i < 1001; $i++ ) {
                $tmp = $urlKey . '-' . $i;
                if ( !isset( $similarUrlKeys[ $tmp ] ) ) {
                    $urlKey = $tmp;
                    break;
                }
            }
        }
        $this->set('url_key', $urlKey );
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
                'sku'               => $product->local_sku,
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
            $data['local_sku'] = $post['sku'];
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
                if ($f->get('frontend_show')) {
                    $result[] = $f;
                }
            }
        }
        return $result;
    }

    public function searchProductOrm($q='', $filter=array(), $category = null)
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
            $productsORM->where(array('OR'=>array('local_sku'=>$q, 'AND'=>$and)));
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
            ->select(array('a.id', 'a.folder', 'a.subfolder', 'a.file_name', 'a.file_size', 'pa.label'))
            ->order_by_asc('position');
    }

    public function media($type)
    {
        return $this->mediaORM($type)->find_many_assoc();
    }

    /**
     * @param array $data
     * @param array $config
     * @return array|null
     */
    public function import($data, $config=array())
    {
        if (empty($data) || !is_array($data)) {
            return null;
        }
//        BResponse::i()->startLongResponse(false);
        //HANDLE CONFIG

        BEvents::i()->fire(__METHOD__.':before', array('data' => &$data, 'config' => &$config));

        //multi value separator used to separate values in one column like for images
        //For example: image.png; image2.png; image3.png
        if (!isset( $config[ 'format' ][ 'multivalue_separator' ] )) {
            $config[ 'format' ][ 'multivalue_separator' ] = ';';
        }
        $ms = $config[ 'format' ][ 'multivalue_separator' ];

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

        if ( !isset($config['import']['images']['url_thumb_prefix']) ) {
            $config['import']['images']['url_thumb_prefix'] = 'product/image/';
        }

        // import related products - default true
        if ( !isset( $config[ 'import' ][ 'related' ][ 'import' ] ) ) {
            $config[ 'import' ][ 'related' ][ 'import' ] = true;
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
        $relatedProducts = array();
        for( $i = 0, $c = count($data); $i < $c; $i++ ) {
            $d = $data[$i];
            //if must have fields not defined then skip the record
            if (empty($d['product_name']) && empty($d['local_sku']) && empty($d['url_key'])) {
                continue;
            }

            $categoriesPath = array();
            if ($config['import']['categories']['import'] && !empty($d['categories'])) {
                $categoriesPath = explode( $ms, $d['categories']);
                unset($d['categories']);
            }

            $imagesNames = array();
            if ($config['import']['images']['import'] && !empty($d['images'])) {
                $imagesNames = explode( $ms, $d['images']);
                unset($d['images']);
            }

            if(!empty($config['import']['images']['url_thumb_prefix']) && !empty($d['thumb_url'])){
                if(!strpos($d['thumb_url'], $config['import']['images']['url_thumb_prefix']) !== 0){
                    $d['thumb_url'] = $config['import']['images']['url_thumb_prefix'] . $d['thumb_url'];
                }
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
                if (isset($d['local_sku'])) {
                    $p = $this->orm()->where("local_sku", $d['local_sku'])->find_one();
                }
//                if (!$p && isset($d['product_name'])) {
//                    $p = $this->orm()->where("product_name", $d['product_name'])->find_one();
//                }
                if (!$p && isset($d['url_key'])) {
                    $p = $this->orm()->where("url_key", $d['url_key'])->find_one();
                }
            }
            /** @var FCom_Catalog_Model_Product $p */
            if (!$p && 'update' == $config['import']['actions']) {
                continue;
            } elseif (!$p) {
                try {
                    $p = $this->orm()->create($d)->save();
                    $result[]['status'] = 'created';
                } catch (Exception $e) {
                    BDebug::log($e->getMessage());
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
            if ( $config[ 'import' ][ 'related' ][ 'import' ] && !empty( $d[ 'related' ] ) ) {
                $relatedProducts[ $pId ] = explode( $ms, $d[ 'related' ] );
                unset( $d[ 'related' ] );
            }

            $p->set($d);
            if ($p->is_dirty()) {
                $p->save();
            }

            //set custom fields for product
            if (!empty($cfIntersection)) {
                foreach($cfIntersection as $cfk) {
                    $customFields[ $pId ][$cfk] = $d[$cfk];
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
                        /** @var FCom_Catalog_Model_Category $parent */
                        $parent = $topParentCategory;
                        $catNodes = explode($ns, $catpath);
                        /*print_r($catpath);
                        echo "\n";
                        print_r($ns);
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
                            $cId                = $category->id();
                            $categories[ $cId ] = $category;
                            if ($config['import']['categories']['menu'] && $categories[ $cId ]->inMenu() == false) {
                                $categories[ $cId ]->setInMenu(true);
                            }
                        }

                    }

                    //assign products to categories
                    if (!empty($categories)) {
                        foreach($categories as $category) {
                            $catProduct = FCom_Catalog_Model_CategoryProduct::i()->orm()
                                    ->where('product_id', $pId )
                                    ->where('category_id', $category->id())
                                    ->find_one();
                            if (!$catProduct) {
                                try {
                                    FCom_Catalog_Model_CategoryProduct::orm()
                                        ->create(array('product_id' => $pId, 'category_id'=>$category->id()))
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
                $imagesConfig = !empty($config['import']['images']) ? $config['import']['images'] : array();
                $imagesResult = $this->importImages( $imagesNames, $imagesConfig, $p );
                if(is_array($imagesResult)){
                    $errors[] += $imagesResult;
                }
            }

            $productIds[] = $pId;
        }

        //HANDLE CUSTOM FIELDS to product relations
        if ($config['import']['custom_fields']['import']
            && !empty($cfIntersection) && !empty($productIds) && !empty($cfFields)) {
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

        if ( !empty( $relatedProducts ) ) {
            $relatedResult = $this->_importRelatedProducts( $relatedProducts );
            if ( is_array( $relatedResult ) ) {
                $errors[ ] += $relatedResult;
            }
        }
        unset($data);
        $this->_importErrors = $errors;
        if ($errors) {
            $result['errors'] = $errors;
        }
        BEvents::i()->fire(__METHOD__.':after', array('product_ids' => $productIds, 'config' => &$config, 'result' => &$result));

        return $result;
    }

    public function addToCategories($categoryIds)
    {
        $hlp = FCom_Catalog_Model_CategoryProduct::i();
        foreach ((array)$categoryIds as $cId) {
            $hlp->create(array('product_id'=>$this->id, 'category_id'=>$cId))->save();
        }
        return $this;
    }

    public function removeFromCategories($categoryIds)
    {
        FCom_Catalog_Model_CategoryProduct::i()->delete_many(array('product_id'=>$this->id, 'category_id'=>$categoryIds));
        return $this;
    }

    public function getAverageStars()
    {
        return $this->get('avg_rating')/5*100;
    }

    public function getNumReviews()
    {
        return $this->get('num_reviews');
    }

    public function reviews($incAvgRating = true)
    {
        $reviews = FCom_ProductReviews_Model_Review::i()->orm('pr')->select(array('pr.*', 'c.firstname', 'c.lastname'))
            ->join('FCom_Customer_Model_Customer', array('pr.customer_id','=','c.id'), 'c')
            ->where(array('pr.product_id' => $this->id(), 'approved' => 1))->order_by_expr('pr.create_at DESC')->find_many();

        if ($incAvgRating) {
            $avgRating = $this->calcAverageRating($reviews);
        }
        return array(
            'items' => $reviews,
            'avgRating' => isset($avgRating) ? $avgRating : array(),
            'numReviews' => count($reviews),
        );
    }

    public function calcAverageRating($reviews = array())
    {
        $rs = array(
            'rating' => 0,
            'rating1' => 0,
            'rating2' => 0,
            'rating3' => 0,
        );
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

    public function getProductLink()
    {
        $arrProduct = FCom_Catalog_Model_Product::i()->orm('p')->select('pl.link_type')
            ->left_outer_join('FCom_Catalog_Model_ProductLink', array('p.id', '=', 'pl.linked_product_id'), 'pl')
            ->where('pl.product_id', $this->id)->find_many();
        $productLink = array(
            'related'=> array('title' => BLocale::_('Related Products'), 'product' => array()),
            'similar' => array('title' => BLocale::_('You may also like these items'), 'product' => array()),
            'cross_sell' => array('title' => BLocale::_('You may also like these items'), 'product' => array())
        );
        foreach ($arrProduct as $product) {
            if (isset($productLink[$product->get('link_type')])) {
                array_push($productLink[$product->get('link_type')]['product'], $product);
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
        $data = array();
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
     * @return boolean|FCom_ProductReviews_Model_Review
     */
    public function isAlreadyReviewed($customerId)
    {
        return FCom_ProductReviews_Model_Review::i()->load(array('product_id' => $this->id, 'customer_id' => $customerId));
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
    protected function _importRelatedProducts( $relatedProducts )
    {
        $relatedIds = array();
        $relation   = FCom_Catalog_Model_ProductLink::i();
        $errors     = array();
        try {
            foreach ( $relatedProducts as $pId => $relatedSkus ) {
                $temp   = array();
                $linked = array();

                foreach ( $relatedSkus as $sku ) {
                    // loop $relatedSkus and if they are not in fetched ids, add them to $temp to retrieve them
                    // if id is fetched already then add it to $linked array
                    if ( !isset( $relatedIds[ $sku ] ) ) {
                        $temp[ ] = $sku;
                    } else {
                        $linked[ ] = $relatedIds[ $sku ];
                    }
                }

                if ( !empty( $temp ) ) {
                    // fetch related sku objects
                    $related = $this->orm()->where_in( "local_sku", $temp )
                                    ->find_many();

                    foreach ( $related as $r ) {
                        /* @var FCom_Catalog_Model_Product $r */
                        $linked[ ] = $r->id();
                        $relatedIds[$r->local_sku] = $r->id();
                    }
                }

                foreach ( $linked as $rId ) {
                    // try to create links of type 'related'
                    try {
                        $relation->create(
                                 array(
                                     'link_type'         => 'related',
                                     'product_id'        => $pId,
                                     'linked_product_id' => $rId,
                                 )
                        );
                    } catch ( Exception $e ) {
                        $errors[ ] = $e->getMessage();
                    }

                } // end foreach $linked

            } // end foreach $relatedProducts

        } catch ( Exception $e ) {
            $errors[ ] = $e->getMessage();
        }
        return empty( $errors ) ? true : $errors;
    }

    /**
     * @todo Fix hardcoded folder names
     * @param $config
     * @param $imagesNames
     * @param FCom_Catalog_Model_Product $p
     * @return array|bool
     */
    public function importImages( $imagesNames, $config = array(), $p = null )
    {
        if (is_null($p)) {
            $p = $this;
        }
        $mediaLib     = FCom_Core_Model_MediaLibrary::i();
        $productMedia = FCom_Catalog_Model_ProductMedia::i();
        $rootDir      = BConfig::i()->get( 'fs/root_dir' );
        $imageFolder  = BConfig::i()->get( 'fs/image_folder' );
        $thumbUrl = str_ireplace('media/product/image', '', $p->get('thumb_url'));
        $errors = array();

        foreach ( $imagesNames as $fileName ) {
            $pathInfo  = pathinfo( $fileName );
            $subFolder = $pathInfo[ 'dirname' ] == '.' ? null : $pathInfo[ 'dirname' ];
            $att       = $mediaLib->load(array(
                'folder'    => $imageFolder,
                'subfolder' => $subFolder,
                'file_name' => $pathInfo[ 'basename' ]
            ));
            if ( !$att ) {
                $fullPathToFile = $rootDir . '/' . $imageFolder . '/' . $fileName;
                $size           = 0;
                if ( file_exists( $fullPathToFile ) ) {
                    $size = filesize( $fullPathToFile );
                }

                $subFolder = null;
                if ( !empty($config[ 'with_subfolders' ]) ) {
                    $subFolder = $pathInfo[ 'dirname' ] == '.' ? null : $pathInfo[ 'dirname' ];
                }
                try {
                    $att = $mediaLib->create(array(
                        'folder'    => $imageFolder,
                        'subfolder' => $subFolder,
                        'file_name' => $pathInfo[ 'basename' ],
                        'file_size' => $size,
                    ))->save();
                } catch ( Exception $e ) {
                    $errors[ ] = $e->getMessage();
                }
            }
            $fileId = $productMedia->orm()->where( 'product_id', $p->id() )
                                   ->where( 'file_id', $att->id() )->find_one();
            $isThumb = ( 'product/image/' . $fileName == $thumbUrl );
            if ( !$fileId ) {
                try {
                    $productMedia->create(array(
                        'product_id' => $p->id(),
                        'media_type' => 'images',
                        'file_id'    => $att->id(),
                        'main_thumb' => $isThumb ? 1 : 0
                    ))->save();
                } catch ( Exception $e ) {
                    $errors[ ] = $e->getMessage();
                }
            } else if ( $fileId->get('main_thumb') == 0 && $isThumb ){
                $fileId->set('main_thumb', 1)->save();
            }
        }
        return empty( $errors ) ? true : $errors;
    }

    public function getDataSerialized($data)
    {
        $data_serialized = BUtil::objectToArray(json_decode($this->data_serialized));
        if ($data == 'custom_fields' && isset($data_serialized[$data])) {
            return BUtil::objectToArray(json_decode($data_serialized[$data]));
        }
        return isset($data_serialized[$data]) ? $data_serialized[$data] : array() ;
    }
}

