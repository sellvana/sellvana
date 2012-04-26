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
     * Load defined scoring functions
     */
    protected function _init()
    {
        //scoring functions definition for IndexDen
        //todo: move them into configuration
        $this->_functions  =  array (
                'age'                   => array('number' => 0, 'definition' => '-age'         ),
                'relevance'             => array('number' => 1, 'definition' => 'relevance'    ),
                'base_price_asc'        => array('number' => 2, 'definition' => '-d[0]'  ),
                'base_price_desc'       => array('number' => 3, 'definition' => 'd[0]'   )
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

            $product_fields = FCom_IndexTank_Model_ProductFields::i()->get_search_list();
            $query_string = '';

            foreach($product_fields as $pfield){
                $priority = '';
                if($pfield->priority > 1){
                    $priority = ' ^'.$pfield->priority;
                }
                if(!empty($query_string)){
                    $query_string .= " OR ";
                } else {
                    $query_string = $query . " OR ";
                }

                $query_string .= " {$pfield->field_name}:$query" . $priority." ";
            }

        } else {
            $query_string = "match:all";
        }
//echo $query_string;exit;
        try {
            //search($query, $start = NULL, $len = NULL, $scoring_function = NULL,
            //$snippet_fields = NULL, $fetch_fields = NULL, $category_filters = NULL,
            //$variables = NULL, $docvar_filters = NULL, $function_filters = NULL, $category_rollup = NULL, $match_any_field = NULL )
            $result = $this->model()->search($query_string, $start, $len, $this->_scoring_function,
                    null, null, $this->_filter_category,
                    null, $this->_filter_docvar, null, implode(",", $this->_rollup_category), true );

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
            $products[] = $res->docid;
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
        foreach($products as $i => $product){
            $categories     = $this->_prepareCategories($product);
            $variables      = $this->_prepareVariables($product);
            $fields         = $this->_prepareFields($product);

            $documents[$i]['docid'] = $product->id();
            $documents[$i]['fields'] = $fields;
            if (!empty($categories)){
                $documents[$i]['categories'] = $categories;
            }
            if (!empty($variables)){
                $documents[$i]['variables'] = $variables;
            }

            //submit every N products to IndexDen - this protect from network overloading
            if ( 0 == $counter++ % $limit_docs_per_query ){
                $this->model()->add_documents($documents);
                $documents = array();
            }
        }
        if ($documents){
            $this->model()->add_documents($documents);
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

    public function update_functions()
    {
        $functions = FCom_IndexTank_Model_ProductFunctions::i()->get_list();
        if(!$functions){
            return;
        }
        foreach($functions as $func){
            $this->update_function($func->number, $func->definition);
        }
    }
    public function update_function($number, $definition)
    {
        if('' === $definition){
            return $this->model()->delete_function($number);
        } else {
            return $this->model()->add_function($number, $definition);
        }
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

    protected function _processFields($fields_list, $product, $type='')
    {
        $result = array();
        foreach($fields_list as $field){
            switch($field->source_type){
                case 'product':
                    $value = $product->{$field->source_value};
                    $result[$field->field_name] = $value;
                    break;
                case 'function':
                    $values_list = $this->{$field->source_value}($product, $type);
                    if($values_list){
                        if(is_array($values_list)){
                            foreach ($values_list as $search_name => $search_value) {
                                $result[$field->field_name . $search_name] = $search_value;
                            }
                        }  else {
                            $result[$field->field_name] = $values_list;
                        }

                    }
                    break;
            }
        }
        return $result;
    }

    protected function _prepareFields($product)
    {
        $fields_list = FCom_IndexTank_Model_ProductFields::i()->get_search_list();
        $searches = $this->_processFields($fields_list, $product, 'search');
        //add two special fields
        $searches['timestamp'] = strtotime($product->update_dt);
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
        $fields_list = FCom_IndexTank_Model_ProductFields::i()->get_facets_list();
        $categories = $this->_processFields($fields_list, $product);
        return $categories;

    }

    protected function _prepareVariables($product)
    {
        $fields_list = FCom_IndexTank_Model_ProductFields::i()->get_varialbes_list();
        $variables_list = $this->_processFields($fields_list, $product);

        $variables = array();
        foreach($fields_list as $field){
            $variables[$field->var_number] = $variables_list[$field->source_value];
        }
        return $variables;
    }

    protected function get_ft_categories($product)
    {
        $product_categories = $product->categories($product->id()); //get all categories for product
        if ($product_categories){
            $categories = array();
            foreach ($product_categories as $cat) {
                $categories[] = $cat->node_name;
            }
            if($categories){
                return "/".implode("/", $categories);
            }
        }
        return '';
    }

    protected function get_categories($product, $type='')
    {
        $categories = array();
        $product_categories = $product->categories($product->id()); //get all categories for product
        if ($product_categories){
            foreach ($product_categories as $cat) {
                $cat_path = str_replace("/","__",$cat->url_path);
                $categories[$cat_path] = $cat->node_name;
            }
        }
        if ('search' == $type){
            return "/".implode("/", $categories);
        }
        return $categories;
    }

    protected function get_brands($product, $type='')
    {
        return (rand(0, 100) % 2 == 0) ? "Brand 1": "Brand 2";
    }

    protected function price_range_large($product, $type='')
    {
        if ($product->base_price < 100) {
            return '$0 to $99';
        } else if ($product->base_price < 200) {
            return '$100 to $199';
        }else if ($product->base_price < 300) {
            return '$200 to $299';
        }else if ($product->base_price < 400) {
            return '$300 to $399';
        }else if ($product->base_price < 500) {
            return '$400 to $499';
        }else if ($product->base_price < 600) {
            return '$500 to $599';
        }else if ($product->base_price < 700) {
            return '$600 to $699';
        }else if ($product->base_price < 800) {
            return '$700 to $799';
        }else if ($product->base_price < 900) {
            return '$800 to $899';
        }else if ($product->base_price < 1000) {
            return '$900 to $999';
        }


    }

    protected function price_range_smart($product, $type='')
    {
        $p = $product->base_price;
        $p2_u = ceil($p/10)*10;
        $p2_d = floor($p/10)*10;
        if($p2_u == $p2_d){
            $p2_u += 10;
        }
        return '$'.$p2_d.' to $'.$p2_u;
    }




}