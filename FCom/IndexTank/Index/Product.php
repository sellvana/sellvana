<?php

class FCom_IndexTank_Index_Product extends FCom_IndexTank_Index_Abstract
{
    /**
     * Name of the index
     * @var string
     */
    protected $_index_name = 'products';

    /**
     * IndexTank API object
     * @var FCom_IndexTank_Api
     */
    protected $_model;

    /************** Index configuration *******************/
    /**
     * Every document in index should contain docid field
     */
    const DOC_ID = 'docid'; //never change DOC_ID value

    /**
     * Product name
     */
    const FT_PRODUCT_NAME = 'product_name';
    /**
     * Product description
     */
    const FT_DESCRIPTION = 'text';
    /**
     *Product notes
     */
    const FT_NOTES = 'notes';
    /**
     * Manufactory sku
     */
    const FT_MANUF_SKU = 'manuf_sku';
    /**
     * Document create date by default using current time
     */
    const FT_TIMESTAMP = 'timestamp';
    /**
     * Contain full-text representaion of categories like:
     * /Books/fantasy
     * /Electronics/accessories
     */
    const FT_CATEGORIES = 'categories';
    /**
     * This field should always contain word 'all'
     * Using this field we could fetch all documents from the index by search query:
     * match:all
     */
    const FT_MATCH = 'match';


    /**
     * Categories filter prefix
     * Usage example: array ( self::CT_CATEGORY_PREFIX . $CAT_ID => 'Electronics' )
     */
    const CT_CATEGORY_PREFIX = 'ct_category_';

    /**
     * Custom fields filter prefix
     * Usage example: array ( self::CT_CUSTOM_FIELD_PREFIX . $FIELD_NAME.'_'.$FIELD_CODE => 'E-mail client' )
     */
    const CT_CUSTOM_FIELD_PREFIX = 'ct_custom_field_';

    /**
     * Textual price range representation.
     * Example: '$100 to $199'
     */
    const CT_PRICE_RANGE = 'ct_price_range';

    /**
     * Brand name filter
     */
    const CT_BRAND = 'ct_brand';


    /**
     * Price variable number
     */
    const VAR_PRICE         = 0;
    /**
     * Rating variable number
     */
    const VAR_RATING        = 1;

    /**
     * Defined scoring functions for products index
     * @var array
     */
    protected $_functions  =  array ();


    /**
     * Selected scoring function for current search session
     * @var integer
     */
    protected $_scoring_function = 0;

    /**
     * Selected filters for current search session
     * @var array
     */
    protected $_filter_category = null;

    /**
     * Set category which required rollup totals
     * @var array
     */
    protected $_rollup_category = null;

    /**
     * Selected document variables filter for current search session
     * @var array
     */
    protected $_filter_docvar = null;

    /**
     * Search result object
     * @var object
     */
    protected $_result = null;

    /**
     * Indicator which tell us was query simplified or not
     * Query simplified only when nothing was found by general query
     * @var boolean
     */
    protected $_simple_query = false;


    /**
     * Load defined scoring functions
     */
    protected function _init()
    {
        //scoring functions definition for IndexDen
        //todo: move them into configuration
        $this->_functions  =  array (
                'age'                   => array('number' => 0, 'definition' => '-age'         ),
                'relevance'             => array('number' => 1, 'definition' => 'relevance'    ),
                'base_price_asc'        => array('number' => 2, 'definition' => '-d[' . self::VAR_PRICE . ']'  ),
                'base_price_desc'       => array('number' => 3, 'definition' => 'd[' . self::VAR_PRICE . ']'   )
        );
    }

    /**
     * Run by migration script.
     * Create index name 'products' and install scoring functions.
     */
    public function install()
    {
        //init configuration
        $this->_init();

        try {
            //create an index
            $this->_model = FCom_IndexTank_Api::i()->service()->create_index($this->_index_name);
        } catch(Exception $e) {
            $this->_model = FCom_IndexTank_Api::i()->service()->get_index($this->_index_name);
        }

        //run once to install scoring functions
        foreach($this->_functions as $func){
            $this->_model->add_function($func['number'], $func['definition']);
        }
    }


    /**
     *
     * @return Indextank_Index
     * @throws Exception if index not found
     */
    public function model()
    {
        if (empty($this->_model)){
            //init config
            $this->_init();
            //init model
            $this->_model = FCom_IndexTank_Api::i()->service()->get_index($this->_index_name);
        }
        return $this->_model;
    }

    public function isSimpleQuery()
    {
        return $this->_simple_query;
    }

    /**
     * Set scoring function to use in current search session
     * @param string $function
     * @throws Exception
     */
    public function scoring_by($function)
    {
        $this->model();
        if (empty($this->_functions[$function])){
            throw new Exception('Scoring function does not exist: ' . $function);
        }
        $this->_scoring_function = $this->_functions[$function]['number'];
    }

    /**
     * Set filter for current search session
     * @param string $category
     * @param integer $value
     */
    public function filter_by($category, $value)
    {
        $this->_filter_category[$category][] = $value;
    }

    /**
     * Set range filter for current search session
     * @param integer $var
     * @param float $from
     * @param float $to
     */
    public function filter_range($var, $from, $to)
    {
        $this->_filter_docvar[$var][] = array($from, $to);
    }


    /**
     * Set categories for rollup
     * @param string $category
     */
    public function rollup_by($category)
    {
        $this->_rollup_category[] = $category;

    }

    /**
     * Reset filters
     */
    public function reset_filters()
    {
        $this->_filter_category = array();
    }

    /**
     * Get index status
     * @return array
     */
    public function status()
    {
        $metadata = $this->model()->get_metadata();
        $result = array (
            'name'          => $this->_index_name,
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
        if (!empty($query)){
            $product_fields = FCom_IndexTank_Model_ProductFields::i()->get_fulltext_list();
            $query_string = '';
            foreach($product_fields as $pfield){
                $priority = '';
                if($pfield->priority > 1){
                    $priority = ' ^'.$pfield->priority;
                }
                if(!empty($query_string)){
                    $query_string .= " OR ";
                }
                $query_string .= " {$pfield->field_name}:{$query} " . $priority." ";
            }

        } else {
            $query_string = "match:all";
        }

        try {
            //search($query, $start = NULL, $len = NULL, $scoring_function = NULL,
            //$snippet_fields = NULL, $fetch_fields = NULL, $category_filters = NULL,
            //$variables = NULL, $docvar_filters = NULL, $function_filters = NULL, $category_rollup = NULL )
            $result = $this->model()->search($query_string, $start, $len, $this->_scoring_function,
                    null, null, $this->_filter_category,
                    null, $this->_filter_docvar, null, implode(",", $this->_rollup_category) );

        } catch(Exception $e) {
            throw $e;
        }

        $this->_result = $result;
        //print_r( $this->_result);exit;
        if ($result->matches <= 0){
            return false;
        }

        $products = array();
        //$product_model = FCom_Catalog_Model_Product::i();
        foreach ($result->results as $res){
            $products[] = $res->{self::DOC_ID};
        }
        $productsORM = FCom_Catalog_Model_Product::i()->orm('p')->where_in("p.id", $products)
                ->order_by_expr("FIELD(p.id, ".implode(",", $products).")");
        return $productsORM;
    }

    /**
     * Return facets with merged rollups
     * @return array
     */
    public function getFacets()
    {
        if (!isset($this->_result->facets)){
            return false;
        }
        $facets = get_object_vars($this->_result->facets);
        $res = array();
        foreach($facets as $k => $v){
            $res[$k] = get_object_vars($v);
        }
        if (!empty($this->_result->facets_rollup)){
            foreach($this->_result->facets_rollup as $k => $v){
                $res[$k] = get_object_vars($v);
            }
        }

        return $res;
    }

    /**
     * Collect all data (text fields, categoreis, variables) for $product and add it to the index
     * @param array $products of FCom_Catalog_Model_Product objects
     */
    public function add($products)
    {
        if (!is_array($products))
        {
            $products = array($products);
        }

        $limit_docs_per_query = 500;
        $counter = 0;
        $documents = array();
        $products_structure = array();
        foreach($products as $i => $product){
            $categories     = $this->_prepareCategories($product);
            $variables      = $this->_prepareVariables($product);
            $fields         = $this->_prepareFields($product);

            $documents[$i][self::DOC_ID] = $product->id();
            $documents[$i]['fields'] = $fields;
            if (!empty($categories)){
                $documents[$i]['categories'] = $categories;
            }
            if (!empty($variables)){
                $documents[$i]['variables'] = $variables;
            }

            //submit every N products to IndexDen - this protect from network overloading
            if ( 0 == $counter++ % $limit_docs_per_query ){
                $this->get_structure($documents,$products_structure);
                $this->model()->add_documents($documents);
                $documents = array();
            }
        }
        if ($documents){
            $this->get_structure($documents,$products_structure);
            $this->model()->add_documents($documents);
        }
        //update structure
        foreach($products_structure['fields'] as $field_name){
            $f = FCom_IndexTank_Model_ProductFields::i()->orm()
                    ->where('field_name', $field_name)
                    ->where('type', 'fulltext')
                    ->find_one();
            if ($f){
                continue;
            }
            $f = FCom_IndexTank_Model_ProductFields::i()->orm()->create();
            $f->field_name = $field_name;
            $f->type = 'fulltext';
            $f->save();
        }

        foreach($products_structure['categories'] as $field_name){
            $f = FCom_IndexTank_Model_ProductFields::i()->orm()
                    ->where('field_name', $field_name)
                    ->where('type', 'category')
                    ->find_one();
            if ($f){
                continue;
            }
            $f = FCom_IndexTank_Model_ProductFields::i()->orm()->create();
            $f->field_name = $field_name;
            $f->type = 'category';
            $f->show = 'checkbox';
            $f->filter = 'exclusive';
            $f->save();
        }
    }

    protected function get_structure($documents, &$products_structure)
    {
        if (empty($products_structure['fields'])){
            $products_structure['fields'] = array();
        }
        if (empty($products_structure['categories'])){
            $products_structure['categories'] = array();
        }
        foreach($documents as $doc){
            $products_structure['fields'] = array_merge($products_structure['fields'], array_keys($doc['fields']));
            if (!empty($doc['categories'])) {
                $products_structure['categories'] = array_merge($products_structure['categories'], array_keys($doc['categories']));
            }
        }
        $products_structure['fields'] = array_unique($products_structure['fields']);
        $products_structure['categories'] = array_unique($products_structure['categories']);
        foreach($products_structure['categories'] as $id => $cat){
            if(false !== strpos($cat, self::CT_CATEGORY_PREFIX)){
                unset($products_structure['categories'][$id]);
            }
        }
    }

    public function update_categories($product)
    {
        $categories = $this->_prepareCategories($product);
        $this->model()->update_categories($product->id(), $categories);
    }

    public function delete_categories($product, $category)
    {
        $category = array(self::CT_CATEGORY_PREFIX . $category->id_path => "");
        $this->model()->update_categories($product->id(), $category);
    }

    public function delete_custom_field($product, $cf)
    {
        $category = array(self::CT_CUSTOM_FIELD_PREFIX . $cf->field_name.'_'.$cf->field_code => "");
        $this->model()->update_categories($product->id(), $category);
    }

    public function update_variables($product)
    {
        $variables = $this->_prepareVariables($product);
        $this->model()->update_variables($product->id(), $variables);
    }

    public function delete($products)
    {
        if (!is_array($products)){
            $products = array($products);
        }
        $docids = array();
        foreach($products as $product){
            $docids[] = $product->id();
        }
        $this->model()->delete_documents($docids);
    }

    public function get_custom_field_name($cf_model)
    {
        return self::CT_CUSTOM_FIELD_PREFIX . $cf_model->field_name.'_'.$cf_model->field_code;
    }

    protected function _prepareFields($product)
    {
        //get all text fields
        $fields = array(
                self::FT_DESCRIPTION    => $product->description,
                self::FT_PRODUCT_NAME   => $product->product_name,
                self::FT_MANUF_SKU      => $product->manuf_sku,
                self::FT_NOTES          => $product->notes,
                self::FT_TIMESTAMP      => strtotime($product->update_dt),
                self::FT_MATCH          => "all"
        );
        $product_categories = $product->categories($product->id()); //get all categories for product
        if ($product_categories){
            $categories = array();
            foreach ($product_categories as $cat) {
                $categories[] = $cat->node_name;
            }
            if($categories){
                $fields[self::FT_CATEGORIES] = "/".implode("/", $categories);
            }
        }


        return $fields;
    }

    /**
     *
     * @param FCom_Catalog_Model_Product $product
     * @return array
     */
    protected function _prepareCategories($product)
    {

        $categories = array(
                self::CT_PRICE_RANGE     => $product->getPriceRangeText(),
                self::CT_BRAND          => $product->getBrandName()
        );

        $product_categories = $product->categories($product->id()); //get all categories for product
        if ($product_categories){
            foreach ($product_categories as $cat) {
                $categories[self::CT_CATEGORY_PREFIX . $cat->id_path] = $cat->node_name;
                //uncomment to remove all Categories
               //$categories[self::CT_CATEGORY_PREFIX . $cat->id_path] = '';
            }
        }

        $product_custom_fields = $product->customFields($product); //get all custom fields for product

        if ($product_custom_fields) {
            foreach ($product_custom_fields as $cf) {
                //$categories[self::CT_CUSTOM_FIELD_PREFIX . $cf->field_code .'_'.$cf->field_name] = $cf->label;
                if (!is_null($product->{$cf->field_code})){
                    $categories[$this->get_custom_field_name($cf)] = $product->{$cf->field_code};
                    //uncomment to remove all CF
                    //$categories[self::CT_CUSTOM_FIELD_PREFIX . $cf->field_name.'_'.$cf->field_code] = '';
                }
            }
        }
/*
        $product_sellers = $product->sellers(); //get all sellers for product
            foreach ($product_sellers as $seller) {
                $categories[self::CT_SELLER_PREFIX . $seller->name] = 'Yes';
            }
        // etc.....

*/
        return $categories;
    }

    protected function _prepareVariables($product)
    {
        //get all variables
        $variables = array(
                self::VAR_PRICE         => $product->base_price,
                self::VAR_RATING        => $product->rating()
        );
        return $variables;
    }
}