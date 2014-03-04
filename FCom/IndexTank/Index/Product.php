<?php

class FCom_IndexTank_Index_Product extends FCom_IndexTank_Index_Abstract
{
    /**
     * Name of the index
     * @var string
     */
    protected $_indexName = 'products';

    /**
     * IndexTank API object
     * @var FCom_IndexTank_RemoteApi
     */
    protected $_model;


    /**
     * Defined scoring functions for products index
     * @var array
     */
    protected $_functions  =  array ();


    /**
     * Selected scoring function for current search session
     * @var integer
     */
    protected $_scoringFunction = 0;

    /**
     * Selected filters for current search session
     * @var array
     */
    protected $_filterCategory = null;

    /**
     * Set category which required rollup totals
     * @var array
     */
    protected $_rollupCategory = null;

    /**
     * Selected document variables filter for current search session
     * @var array
     */
    protected $_filterDocvar = null;

    /**
     * Search result object
     * @var object
     */
    protected $_result = null;


    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Index_Product
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    /**
     * Load defined scoring functions
     */
    protected function initFunctions()
    {
        //scoring functions definition for IndexDen
        //todo: move them into configuration
        $functionList  =  FCom_IndexTank_Model_ProductFunction::i()->getList();
        foreach ($functionList as $func) {
            $this->_functions[$func->name] = $func;
        }
    }

    /**
     *
     * @return Indextank_Index
     * @throws Exception if index not found
     */
    public function model()
    {
        if (empty($this->_model)) {
            //init index name
            if (false != ($indexName = BConfig::i()->get('modules/FCom_IndexTank/index_name'))) {
                $this->_indexName = $indexName;
            }
            //init config
            $this->initFunctions();
            //init model
            $this->_model = FCom_IndexTank_RemoteApi::i()->service()->get_index($this->_indexName);
        }
        return $this->_model;
    }

    /**
     * Set scoring function to use in current search session
     * @param string $function
     * @throws Exception
     */
    public function scoringBy($scoringVar)
    {
        if (strpos($scoringVar, "|")) {
            list($field, $order) = explode("|", $scoringVar);
            $function = $field.'_'.$order;
        } else {
            $function = $scoringVar;
        }
        $this->model();

        if (empty($this->_functions[$function])) {
            return;
        }
        $this->_scoringFunction = $this->_functions[$function]->number;
    }

    /**
     * Set filter for current search session
     * @param string $category
     * @param integer $value
     */
    public function filterBy($category, $value)
    {
        $this->_filterCategory[$category][] = $value;
    }

    /**
     * Set range filter for current search session
     * @param integer $var
     * @param float $from
     * @param float $to
     */
    public function filterRange($var, $from, $to)
    {
        $this->_filterDocvar[$var][] = array($from, $to);
    }


    /**
     * Set categories for rollup
     * @param string $category
     */
    public function rollupBy($category)
    {
        $this->_rollupCategory[] = $category;

    }

    /**
     * Reset filters
     */
    public function resetFilters()
    {
        $this->_filterCategory = array();
    }

    /**
     * Get index status
     * @return array
     */
    public function status()
    {
        $metadata = $this->model()->get_metadata();
        $result = array (
            'name'          => $this->_indexName,
            'code'          => $metadata->code,
            'status'        => $metadata->status,
            'size'          => $metadata->size,
            'date'          => $metadata->creation_time
        );
        return $result;
    }


    /**
     *
     * @param string $query
     * @return array $products of FCom_Catalog_Model_Product objects
     * @throws Exception
     */
    public function search($query, $start=null, $len=null)
    {
        if (!empty($query)) {

            $productFields = FCom_IndexTank_Model_ProductField::i()->getSearchList();
            $queryString = '';

            foreach ($productFields as $pfield) {
                $priority = '';
                if ($pfield->priority > 1) {
                    $priority = ' ^'.$pfield->priority;
                }
                if (!empty($queryString)) {
                    $queryString .= " OR ";
                } else {
                   // $queryString = $query . " OR ";
                }

                $queryString .= " {$pfield->field_name}:$query" . $priority." ";
            }

        } else {
            $queryString = "match:all";
        }
//echo $queryString;exit;
        try {
            //search($query, $start = NULL, $len = NULL, $scoring_function = NULL,
            //$snippet_fields = NULL, $fetch_fields = NULL, $category_filters = NULL,
            //$variables = NULL, $docvar_filters = NULL, $function_filters = NULL, $category_rollup = NULL, $match_any_field = NULL )
            $categoryRollup = null;
            if ($this->_rollupCategory) {
                $categoryRollup = implode(",", $this->_rollupCategory);
            }

            $result = $this->model()->search($queryString, $start, $len, $this->_scoringFunction,
                    null, null, $this->_filterCategory,
                    null, $this->_filterDocvar, null, $categoryRollup, true );
#var_dump($this->_filterCategory, $this->_filterDocvar, $categoryRollup, $result); exit;
        } catch(Exception $e) {
            throw $e;
        }

        $this->_result = $result;
        //print_r( $this->_result);exit;
        if (!$result || $result->matches <= 0) {
            return FCom_Catalog_Model_Product::i()->orm('p')->where_in('p.id',array(-1));
        }

        $products = array();
        //$product_model = FCom_Catalog_Model_Product::i();
        foreach ($result->results as $res) {
            $products[] = $res->docid;
        }

        if (!$products) {
            return FCom_Catalog_Model_Product::i()->orm('p')->where_in('p.id',array(-1));
        }
        $productsORM = FCom_Catalog_Model_Product::i()->orm('p')->where_in("p.id", $products)
                ->order_by_expr("FIELD(p.id, ".implode(",", $products).")");
        return $productsORM;
    }

    public function totalFound()
    {
        return !empty($this->_result) ? $this->_result->matches : 0;
    }

    /**
     * Return facets with merged rollups
     * @return array
     */
    public function getFacets()
    {
        if (!isset($this->_result->facets)) {
            return false;
        }
#echo "<pre>"; print_r($this->_result->facets); exit;
        $facets = get_object_vars($this->_result->facets);
        $res = array();
        foreach ($facets as $k => $v) {
            $res[$k] = get_object_vars($v);
        }
        if (!empty($this->_result->facets_rollup)) {
            foreach ($this->_result->facets_rollup as $k => $v) {
                $res[$k] = get_object_vars($v);
            }
        }

        return $res;
    }

    /**
     * Collect all data (text fields, categoreis, variables) for $product and add it to the index
     * @param array $products of FCom_Catalog_Model_Product objects
     */
    public function add($products, $limit = 0)
    {
        if (!is_array($products)){
            $products = array($products);
        }

        $counter = 0;
        $documents = array();
        foreach ($products as $i => $product) {
            if ($product->disabled) {
                continue;
            }
            $categories     = $this->_prepareCategories($product);
            $variables      = $this->_prepareVariables($product);
            $fields         = $this->_prepareFields($product);

            $documents[$i]['docid'] = $product->id();
            $documents[$i]['fields'] = $fields;
            if (!empty($categories)) {
                $documents[$i]['categories'] = $categories;
            }
            if (!empty($variables)) {
                $documents[$i]['variables'] = $variables;
            }

            //submit every N products to IndexDen - this protect from network overloading
            if ( $limit && 0 == ++$counter % $limit ) {
                BEvents::i()->fire(__METHOD__, array('docs'=>&$documents));
                $this->model()->add_documents($documents);
                $documents = array();
            }
        }

        if ($documents) {
            BEvents::i()->fire(__METHOD__, array('docs'=>&$documents));
            $this->model()->add_documents($documents);
        }
    }

    static public function onProductIndexAdd($args)
    {
        // prepare products assoc array
        $products = array();
        foreach ($args['docs'] as &$doc) {
            $products[$doc['docid']] =& $doc;
        }
        unset($doc);
        $pIds = array_keys($products);

        //add categories
        $categories = FCom_Catalog_Model_CategoryProduct::orm('cp')->where_in('cp.product_id', $pIds)
                ->join('FCom_Catalog_Model_Category', array('c.id','=','cp.category_id'), 'c')
                ->select('c.id')->select('cp.product_id')->select('cp.category_id')->select('c.node_name')->find_many();
        if (empty($categories)) {
            return;
        }
        foreach($categories as $cat) {
            $pId = $cat->product_id;
            $products[$pId]['categories'][self::i()->getCategoryKey($cat)] = $cat->node_name;
            if (empty($products[$pId]['fields']['ct_categories'])) {
                $products[$pId]['fields']['ct_categories'] = '';
            }
            $products[$pId]['fields']['ct_categories'] .= '/'.$cat->node_name;
        }
    }

    public function updateTextField($products, $field, $fieldValue)
    {
        if (!is_array($products)){
            $products = array($products);
        }

        $limit = 500;
        $counter = 0;
        $documents = array();
        foreach ($products as $i => $product) {
            $fields[$field] = $fieldValue;
            $documents[$i]['docid'] = $product->id();
            $documents[$i]['fields'] = $fields;

            //submit every N products to IndexDen - this protect from network overloading
            if ( 0 == $counter++ % $limit ) {
                $this->model()->add_documents($documents);
                $documents = array();
            }
        }

        if ($documents) {
            $this->model()->add_documents($documents);
        }
    }

    public function updateCategories($product)
    {
        $categories = $this->_prepareCategories($product);
        $this->model()->update_categories($product->id(), $categories);
    }

    public function getCategoryKey($category)
    {
        //return 'ct_categories___'.str_replace("/","__",$category->url_path);
        return 'ct_'.$category->id;
    }

    public function getCustomFieldKey($cf_model)
    {
        //return 'cf_'.$cf_model->field_type.'___'.$cf_model->field_code;
        return 'cf_'.$cf_model->id();
    }

    /**
     *
     * @param FCom_Catalog_Model_Product $product
     * @param FCom_Catalog_Model_Category $category
     */
    public function deleteCategories($product, $category)
    {
        $this->deleteCategory($product, $this->getCategoryKey($category));
    }

    /**
     *
     * @param FCom_Catalog_Model_Product $product
     * @param string $categoryField in IndexDen
     */
    public function deleteCategory($product, $categoryField)
    {
        $category = array($categoryField => "");
        $this->model()->update_categories($product->id(), $category);
    }

    public function updateVariables($product)
    {
        $variables = $this->_prepareVariables($product);
        $this->model()->update_variables($product->id(), $variables);
    }

    public function updateFunctions()
    {
        $functions = FCom_IndexTank_Model_ProductFunction::i()->getList();
        if (!$functions) {
            return;
        }
        foreach ($functions as $func) {
            $this->updateFunction($func->number, $func->definition);
        }
    }
    public function updateFunction($number, $definition)
    {
        if ('' === $definition) {
            return $this->model()->delete_function($number);
        } else {
            return $this->model()->add_function($number, $definition);
        }
    }

    public function deleteProducts($products)
    {
        if (!is_array($products)) {
            $products = array($products);
        }
        $docids = array();
        foreach ($products as $product) {
            $docids[] = $product->id();
        }
        $this->model()->delete_documents($docids);
    }

    /**
     * Process facets filters to show in view
     * @param type $facets
     * @return type
     */
    public function collectFacets($facets)
    {
        $facetsData = array();
        if ($facets) {

            $facetsFields = FCom_IndexTank_Model_ProductField::i()->getFacetsList();

            //todo: think how to sort custom fields
            //$facetCustomFieldsSorted = FCom_IndexTank_Model_ProductField::i()->getCustomFieldsSorted();

            foreach($facetsFields as $fname => $field) {
                if (isset($facets[$fname])) {
                    foreach ($facets[$fname] as $fvalue => $fcount) {
                        $obj = new stdClass();
                        $obj->name = $fvalue;
                        $obj->count = $fcount;
                        $obj->key = $fname;
                        $obj->category = false;
                        if ('inclusive' == $field->filter || empty($field->filter)) {
                            $obj->param = "f[{$obj->key}][{$obj->name}]";
                        } else {
                            $obj->param = "f[{$obj->key}][]";
                        }
                        $facetsData[$field->field_nice_name][] = $obj;
                    }
                }
            }

            $cmp = function($a, $b)
            {
                return strnatcmp($a->name, $b->name);
            };

            foreach ($facetsData as &$values) {
                usort($values, $cmp);
            }

        }
        return $facetsData;
    }

    public function collectCategories($facets, $categorySelected='')
    {
        $categoryData = array();
        if ($facets) {
            $urlPath = '';
            //get categories
            $catIds = array();
            foreach ($facets as $fname => $fvalues) {
                //hard coded ct_categories prefix
                if (strpos($fname, 'ct_') !== false) {
                    $catIds[] = substr($fname, 3);
                }
            }
            if (empty($catIds)) {
                return array();
            }
            $categories = FCom_Catalog_Model_Category::i()->orm()->where_in('id', $catIds)->find_many_assoc();
            // fetch all ascendants that do not have products
            /*
            $ascIds = array();
            foreach ($categories as $cat) {
                foreach (explode('/', $cat->id_path) as $id) {
                    if ($id>1 && empty($categories[$id])) {
                        $ascIds[$id] = 1;
                    }
                }
            }
            if ($ascIds) {
                $ascendants = FCom_Catalog_Model_Category::i()->orm()->where_in('id', array_keys($ascIds))->find_many_assoc();
                foreach ($ascendants as $id=>$cat) {
                    $categories[$id] = $cat;
                }
            }
            */
            // sort by full name (including hierarchy)
            uasort($categories, function($a, $b) {
                return $a->full_name<$b->full_name ? -1 : ($a->full_name>$b->full_name ? 1 : 0);
            });
            foreach ($categories as $cat) {
                $level = count(explode("/", $cat->id_path))-1;
                $fvalues = !empty($facets['ct_'.$cat->id]) ? $facets['ct_'.$cat->id] : array($cat->node_name => '');
                foreach ($fvalues as $fvalue => $fcount) {
                    $obj = new stdClass();
                    $obj->show_count = true;
                    $obj->name = $fvalue;
                    $obj->url_path = $cat->url_path;
                    $obj->count = $fcount;
                    $obj->key = $this->getCategoryKey($cat);
                    $obj->level = $level;
                    $obj->category = true;
                    if ($categorySelected == $obj->key) {
                        $urlPath = $cat->url_path;
                    }
                    $obj->param = "f[category]";
                    $categoryData['Categories'][$cat->id_path] = $obj;
                }
            }

            /*
            foreach ($facets as $fname => $fvalues) {
                //hard coded ct_categories prefix
                $pos = strpos($fname, 'ct_');
                if ($pos !== false) {
                    $cat_id = substr($fname, 3);
                    if (empty($categories[$cat_id])) {
                        continue;
                    }
                    $category = $categories[$cat_id];
                    $level = count(explode("/", $category->id_path))-1;
                    foreach ($fvalues as $fvalue => $fcount) {
                        $obj = new stdClass();
                        $obj->show_count = false;
                        $obj->name = $fvalue;
                        $obj->url_path = $category->url_path;
                        $obj->count = $fcount;
                        $obj->key = $this->getCategoryKey($category);
                        $obj->level = $level;
                        $obj->category = true;
                        if ($categorySelected == $obj->key) {
                            $urlPath = $category->url_path;
                        }
                        $obj->param = "f[category]";
                        $categoryData['Categories'][$category->id_path] = $obj;
                    }
                }
            }
            if (!empty($categoryData['Categories'])) {
                //ksort($categoryData['Categories']);

                //show total count only for children categoris
                foreach ($categoryData['Categories'] as $obj) {
                    if (!$obj->url_path || !$urlPath) {
                        continue;
                    }
                    if (strpos($obj->url_path, $urlPath) === 0) {
                        $obj->show_count = true;
                    }
                }
            }
            */
        }
        return $categoryData;
    }


    protected function _processFields($fieldsList, $product, $type='')
    {
        $result = array();
        foreach ($fieldsList as $field) {
            if (empty($field->source_value)) {
                continue;
            }
            switch ($field->source_type) {
                case 'product':
                case 'custom_field':
                    //get value of product object
                    $value = $product->{$field->source_value};
                    if ('variables' == $type && false == is_numeric($value)) {
                        $result[$field->field_name] = $this->getStringToOrdinal($value) ;
                    } else {
                        $result[$field->field_name] = $value;
                    }
                    break;
                case 'function':
                    //call function
                    if (strpos($field->source_value, '::')) {
                        $callback = $field->source_value;
                    } elseif (strpos($field->source_value, '.')) {
                        $callback = BUtil::extCallback($field->source_value);
                    } else {
                        $callback = array($this, $field->source_value);
                    }
                    //check callback
                    if (!BClassRegistry::isCallable($callback)) {
                        //BDebug::warning('Invalid IndexTank custom field callback: '.$field->source_value);
                        continue;
                    }
                    $valuesList = call_user_func($callback, $product, $type, $field->field_name);
                    //process results
                    if ($valuesList) {
                        if (is_array($valuesList)) {
                            foreach ($valuesList as $searchName => $searchValue) {
                                $result[$searchName] = $searchValue;
                            }
                        }  else {
                            $result[$field->field_name] = $valuesList;
                        }
                    } else {
                        $result[$field->field_name] = '';
                    }
                    break;
            }
        }
        return $result;
    }

    protected function _prepareFields($product)
    {
        $fieldsList = FCom_IndexTank_Model_ProductField::i()->getSearchList();
        $searches = $this->_processFields($fieldsList, $product, 'search');
        //add two special fields
        $searches['timestamp'] = strtotime($product->update_at);
        $searches['match'] = "all";

        return $searches;
    }

    /**
     *
     * @param FCom_Catalog_Model_Product $product
     * @return array
     */
    protected function _prepareCategories($product)
    {
        $fieldsList = FCom_IndexTank_Model_ProductField::i()->getFacetsList();
        $categories = $this->_processFields($fieldsList, $product, 'categories');
        return $categories;

    }

    protected function _prepareVariables($product)
    {
        $fieldsList = FCom_IndexTank_Model_ProductField::i()->getVariablesList();
        $variablesList = $this->_processFields($fieldsList, $product, 'variables');

        $variables = array();
        foreach ($fieldsList as $field) {
            $variables[$field->var_number] = $variablesList[$field->field_name];
        }

        return $variables;
    }

    /**
     * Run by migration script.
     * Create index name 'products' and install scoring functions.
     */
    public function install()
    {
        //init index name
        if (false != ($indexName = BConfig::i()->get('modules/FCom_IndexTank/index_name'))) {
            $this->_indexName = $indexName;
        }

        try {
            //create an index
            $this->_model = FCom_IndexTank_RemoteApi::i()->service()->create_index($this->_indexName);
        } catch(Exception $e) {
            $this->_model = FCom_IndexTank_RemoteApi::i()->service()->get_index($this->_indexName);
        }

        $this->updateFunctions();
    }

    public function dropIndex()
    {
        if (false != ($indexName = BConfig::i()->get('modules/FCom_IndexTank/index_name'))) {
            $this->_indexName = $indexName;
        }
        $this->model()->delete_index();
    }

    public function createIndex()
    {
        $this->install();
    }


    /*************** Field init functions *******************
     * Example:
     * For field with source_type 'function' and source_value 'getLabel'
     * create following function
     * private function getLabel()
     * {
     *      return 'Text label';
     * }
     */



    public function fieldPriceRange($product, $type='', $field='')
    {
        $m = isset($product->{$field}) ? $product->{$field} : $product->base_price;
        if ($m <   100) return '$0 to $99';
        if ($m <   200) return '$100 to $199';
        if ($m <   300) return '$200 to $299';
        if ($m <   400) return '$300 to $399';
        if ($m <   500) return '$400 to $499';
        if ($m <   600) return '$500 to $599';
        if ($m <   700) return '$600 to $699';
        if ($m <   800) return '$700 to $799';
        if ($m <   900) return '$800 to $899';
        if ($m <  1000) return '$900 to $999';
        if ($m <  2000) return '$1000 to $1999';
        if ($m <  3000) return '$2000 to $2999';
        if ($m <  4000) return '$3000 to $3999';
        if ($m <  5000) return '$4000 to $4999';
        if ($m <  6000) return '$5000 to $5999';
        if ($m <  7000) return '$6000 to $6999';
        if ($m <  8000) return '$7000 to $7999';
        if ($m <  9000) return '$8000 to $8999';
        if ($m < 10000) return '$9000 to $9999';
        return '$10000 or more';
    }

    public function fieldStringToOrdinal($product, $type='', $field='')
    {
        if (!empty($product->$field)) {
            return $this->getStringToOrdinal($product->$field);
        }
    }

    public function getStringToOrdinal($string)
    {
        $string = BLocale::transliterate($string, '');

        if (empty($string)) {
            return '';
        }

        $indexLen = 6;
        $cycles = $indexLen < strlen($string) ? $indexLen : strlen($string);
        $result = 0;
        $pow = $indexLen;
        for($i = 0; $i < $cycles ; $i++){
            $result += (ord($string[$i])-48)*pow(36, $pow--);
        }
        return $result;
    }
}
