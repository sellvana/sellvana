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
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Index_Product
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    /**
     * Load defined scoring functions
     */
    protected function _init_functions()
    {
        //scoring functions definition for IndexDen
        //todo: move them into configuration
        $func_list  =  FCom_IndexTank_Model_ProductFunction::i()->get_list();
        foreach ($func_list as $func) {
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
        if (empty($this->_model)){
            //init index name
            if(false != ($index_name = BConfig::i()->get('modules/FCom_IndexTank/index_name'))){
                $this->_index_name = $index_name;
            }
            //init config
            $this->_init_functions();
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
        $this->_scoring_function = $this->_functions[$function]->number;
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

            $product_fields = FCom_IndexTank_Model_ProductField::i()->get_search_list();
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
            $category_rollup = null;
            if($this->_rollup_category){
                $category_rollup = implode(",", $this->_rollup_category);
            }

            $result = $this->model()->search($query_string, $start, $len, $this->_scoring_function,
                    null, null, $this->_filter_category,
                    null, $this->_filter_docvar, null, $category_rollup, true );

        } catch(Exception $e) {
            throw $e;
        }

        $this->_result = $result;
        //print_r( $this->_result);exit;
        if (!$result || $result->matches <= 0){
            return FCom_Catalog_Model_Product::i()->orm('p')->where_in('p.id',array(-1));
        }

        $products = array();
        //$product_model = FCom_Catalog_Model_Product::i();
        foreach ($result->results as $res){
            $products[] = $res->docid;
        }
        if(!$products){
            return FCom_Catalog_Model_Product::i()->orm('p')->where_in('p.id',array(-1));
        }
        $productsORM = FCom_Catalog_Model_Product::i()->orm('p')->where_in("p.id", $products)
                ->order_by_expr("FIELD(p.id, ".implode(",", $products).")");
        return $productsORM;
    }

    public function total_found()
    {
        return !empty($this->_result) ? $this->_result->matches : 0;
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
    public function add($products, $limit_docs_per_query = 500)
    {
        if (!is_array($products))
        {
            $products = array($products);
        }

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

    public function updateTextField($products, $field, $field_value)
    {
        if (!is_array($products))
        {
            $products = array($products);
        }

        $limit_docs_per_query = 500;
        $counter = 0;
        $documents = array();
        foreach($products as $i => $product){
            $fields[$field] = $field_value;
            $documents[$i]['docid'] = $product->id();
            $documents[$i]['fields'] = $fields;

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

    public function get_category_key($category)
    {
        //return 'ct_categories___'.str_replace("/","__",$category->url_path);
        return 'ct_'.$category->id();
    }

    public function get_custom_field_key($cf_model)
    {
        //return 'cf_'.$cf_model->field_type.'___'.$cf_model->field_code;
        return 'cf_'.$cf_model->id();
    }

    /**
     *
     * @param FCom_Catalog_Model_Product $product
     * @param FCom_Catalog_Model_Category $category
     */
    public function delete_categories($product, $category)
    {
        $this->delete_category($product, $this->get_category_key($category));
    }

    /**
     *
     * @param FCom_Catalog_Model_Product $product
     * @param string $category_field in IndexDen
     */
    public function delete_category($product, $category_field)
    {
        $category = array($category_field => "");
        $this->model()->update_categories($product->id(), $category);
    }

    public function update_variables($product)
    {
        $variables = $this->_prepareVariables($product);
        $this->model()->update_variables($product->id(), $variables);
    }

    public function update_functions()
    {
        $functions = FCom_IndexTank_Model_ProductFunction::i()->get_list();
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

    public function prepareFacets($facets, &$filters_invisible)
    {
        $facets_data = array();
        if($facets){
            $cmp = function($a, $b)
            {
                return strnatcmp($a->name, $b->name);
            };

            $facets_fields = FCom_IndexTank_Model_ProductField::i()->get_facets_list();
            $category_data = array();

            //get categories
            foreach($facets as $fname => $fvalues){
                //hard coded ct_categories prefix
                $pos = strpos($fname, 'ct_');
                if ($pos !== false){
                    $cat_id = substr($fname, strlen('ct_'));
                    $category = FCom_Catalog_Model_Category::i()->load($cat_id);
                    $level = count(explode("/", $category->id_path))-1;
                    foreach($fvalues as $fvalue => $fcount) {
                        $obj = new stdClass();
                        $obj->name = $fvalue;
                        $obj->count = $fcount;
                        $obj->key = $this->get_category_key($category);
                        $obj->level = $level;
                        $obj->category = true;
                        $obj->param = "f[category]";
                        $category_data['Categories'][$category->id_path] = $obj;
                        unset($filters_invisible[$fname][$fvalue]);
                    }
                    continue;
                }
                //get other fields
                if( isset($facets_fields[$fname]) ){
                    foreach($fvalues as $fvalue => $fcount) {
                            $obj = new stdClass();
                            $obj->name = $fvalue;
                            $obj->count = $fcount;
                            $obj->key = $fname;
                            $obj->category = false;
                            if ('inclusive' == $facets_fields[$fname]->filter || empty($facets_fields[$fname]->filter)){
                                $obj->param = "f[{$obj->key}][{$obj->name}]";
                            } else {
                                $obj->param = "f[{$obj->key}][]";
                            }
                            $facets_data[$facets_fields[$fname]->field_nice_name][] = $obj;
                            unset($filters_invisible[$fname][$fvalue]);
                    }
                }
            }
            foreach($facets_data as &$values){
                usort($values, $cmp);
            }

            if (!empty($category_data['Categories'])){
                ksort($category_data['Categories']);
            }
            //put categories first
            $facets_data = (array)$category_data + (array)$facets_data;
        }
        return $facets_data;
    }

    protected function _processFields($fields_list, $product, $type='')
    {
        $result = array();
        foreach($fields_list as $field){
            switch($field->source_type){
                case 'product':
                    //get value of product object
                    $value = $product->{$field->source_value};
                    $result[$field->field_name] = $value;
                    break;
                case 'function':
                    //call function
                    $values_list = $this->{"_field_".$field->source_value}($product, $type);
                    //process results
                    if($values_list){
                        if(is_array($values_list)){
                            foreach ($values_list as $search_name => $search_value) {
                                $result[$search_name] = $search_value;
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
        $fields_list = FCom_IndexTank_Model_ProductField::i()->get_search_list();
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
        $fields_list = FCom_IndexTank_Model_ProductField::i()->get_facets_list();
        $categories = $this->_processFields($fields_list, $product);
        return $categories;

    }

    protected function _prepareVariables($product)
    {
        $fields_list = FCom_IndexTank_Model_ProductField::i()->get_varialbes_list();
        $variables_list = $this->_processFields($fields_list, $product);

        $variables = array();
        foreach($fields_list as $field){
            $variables[$field->var_number] = $variables_list[$field->source_value];
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
        if(false != ($index_name = BConfig::i()->get('modules/FCom_IndexTank/index_name'))){
            $this->_index_name = $index_name;
        }

        try {
            //create an index
            $this->_model = FCom_IndexTank_Api::i()->service()->create_index($this->_index_name);
        } catch(Exception $e) {
            $this->_model = FCom_IndexTank_Api::i()->service()->get_index($this->_index_name);
        }
    }

    public function drop_index()
    {
        if(false != ($index_name = BConfig::i()->get('modules/FCom_IndexTank/index_name'))){
            $this->_index_name = $index_name;
        }
        $this->model()->delete_index();
    }

    public function create_index()
    {
        if(false != ($index_name = BConfig::i()->get('modules/FCom_IndexTank/index_name'))){
            $this->_index_name = $index_name;
        }
        FCom_IndexTank_Api::i()->service()->create_index($this->_index_name);
    }


    /*************** Field init functions *******************
     * Start field functions with _field_ prefix
     * Example:
     * For field with source_type 'function' and source_value 'get_label'
     * create following function
     * private function _field_get_label()
     * {
     *      return 'Text label';
     * }
     */

    protected function _field_get_categories($product, $type='')
    {
        $categories = array();
        $product_categories = $product->categories($product->id()); //get all categories for product
        if ($product_categories){
            foreach ($product_categories as $cat) {
                $cat_path = $this->get_category_key($cat);//str_replace("/","__",$cat->url_path);
                $categories[$cat_path] = $cat->node_name;
            }
        }
        if ('search' == $type){
            return "/".implode("/", $categories);
        }
        return $categories;
    }

    protected function _field_get_brands($product, $type='')
    {
        return (rand(0, 100) % 2 == 0) ? "Brand 1": "Brand 2";
    }

    protected function _field_price_range_large($product, $type='')
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

    protected function _field_min_price_range_large($product, $type='')
    {
        if ($product->min_price < 100) {
            return '$0 to $99';
        } else if ($product->min_price < 200) {
            return '$100 to $199';
        }else if ($product->min_price < 300) {
            return '$200 to $299';
        }else if ($product->min_price < 400) {
            return '$300 to $399';
        }else if ($product->min_price < 500) {
            return '$400 to $499';
        }else if ($product->min_price < 600) {
            return '$500 to $599';
        }else if ($product->min_price < 700) {
            return '$600 to $699';
        }else if ($product->min_price < 800) {
            return '$700 to $799';
        }else if ($product->min_price < 900) {
            return '$800 to $899';
        }else if ($product->min_price < 1000) {
            return '$900 to $999';
        }


    }

    protected function _field_price_range_smart($product, $type='')
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