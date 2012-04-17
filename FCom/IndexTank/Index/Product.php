<?php

class FCom_IndexTank_Index_Product extends BClass
{
    protected $_index_name = 'products';
    protected $_model;

    //main DOCID - unique for each records
    const DOC_ID = 'docid';

    //text fields IndexDen
    const FT_PRODUCT_NAME = 'product_name';
    const FT_DESCRIPTION = 'text';
    const FT_NOTES = 'notes';
    const FT_MANUF_SKU = 'manuf_sku';
    const FT_TIMESTAMP = 'timestamp';
    const FT_CATEGORIES = 'categories';
    /**
     * Special text field which will contain word 'all'
     * It will be used when we will need to fetch all documents from the index
     * without performing a search query
     */
    const FT_MATCH = 'match';


    //categories for IndexDen
    /**
     * Level for categories
     * Example:
     * array ( self::CT_CATEGORY_PREFIX . $CAT_ID => 'Electronics' )
     */
    const CT_CATEGORY_PREFIX = 'ct_category_';

    /**
     * Prefix for custom field
     * Example:
     * array ( self::CT_CUSTOM_FIELD_PREFIX . 'display_inch' => '29' )
     */
    const CT_CUSTOM_FIELD_PREFIX = 'ct_custom_field_';

    /**
     * Textual price range representation like '$100 to $299'
     */
    const CT_PRICE_RANGE = 'ct_price_range';

    /**
     * Brand name
     */
    const CT_BRAND = 'ct_brand';

    /**
     * Prefix for seller name
     */
    const CT_SELLER_PREFIX = 'ct_seller_';


    //variables for IndexDen
    const VAR_PRICE         = 0;
    const VAR_RATING        = 1;

    //scoring functions definition for IndexDen
    protected $_functions  =  array ();


    //currently selected function
    protected $_scoring_function = 0;
    protected $_filter_category = null;
    protected $_filter_docvar = null;

    protected $_result = null;

    protected $_simple_query = false;


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

    public function scoring_by($function)
    {
        $this->model();
        if (empty($this->_functions[$function])){
            throw new Exception('Scoring function does not exist: ' . $function);
        }
        $this->_scoring_function = $this->_functions[$function]['number'];
    }

    public function filter_by($category, $value)
    {
        if ( !in_array($category, array(self::CT_PRICE_RANGE, self::CT_BRAND)) ){
           // throw new Exception('Filter does not exist: ' . $category);
        }
        $this->_filter_category[$category][] = $value;
    }

    public function filter_range($var, $from, $to)
    {
        $this->_filter_docvar[$var][] = array($from, $to);
    }


    public function unfilter_by($category)
    {
        $this->_unfilter_category[] = $category;

    }

    public function reset_filters()
    {
        $this->_filter_category = array();
    }

    public function status()
    {
        $result = array (
            'name'          => $this->_index_name,
            'code'          => $this->model()->get_code(),
            'status'        => $this->model()->get_status(),
            'size'          => $this->model()->get_size(),
            'date'          => $this->model()->get_creation_time()
        );
        return $result;
    }


    /**
     *
     * @param string $query
     * @return array $products of FCom_Catalog_Model_Product objects
     * @throws Exception if query failed
     */
    public function search($query, $start=null, $len=null)
    {
        if (!empty($query)){
            $queryValue = $query;
            if (strpos($query, " in ")) {
                $categories_query = substr($query, strpos($query, " in ")+strlen(" in "));
                $queryValue = substr($query, 0, strpos($query, " in "));

                if(!empty($categories_query) && !empty($queryValue)){
                    $queryString = self::FT_PRODUCT_NAME . ":($queryValue)^10 OR ".
                            self::FT_DESCRIPTION.":($queryValue) AND ".
                            self::FT_CATEGORIES.":($categories_query) ";
                } else {
                    $queryString = self::FT_PRODUCT_NAME . ":($query)^10 OR ".
                            self::FT_DESCRIPTION.":($query) OR ".
                            self::FT_CATEGORIES.":($query)";
                }
            } else {
                $queryString = self::FT_PRODUCT_NAME . ":($query)^10 OR ".
                            self::FT_DESCRIPTION.":($query) OR ".
                            self::FT_CATEGORIES.":($query)";
            }
        } else {
            $queryString = "match:all";
        }

        try {
            //search($query, $start = NULL, $len = NULL, $scoring_function = NULL,
            //$snippet_fields = NULL, $fetch_fields = NULL, $category_filters = NULL,
            //$variables = NULL, $docvar_filters = NULL, $function_filters = NULL, $category_unfilters = NULL )
            $result = $this->model()->search($queryString, $start, $len, $this->_scoring_function,
                    null, null, $this->_filter_category,
                    null, $this->_filter_docvar, null, implode(",", $this->_unfilter_category) );

            //try simple query
            if ($result->matches <= 0){
                $queryString = self::FT_PRODUCT_NAME . ":($query)^10 OR ". self::FT_DESCRIPTION.":($query)";
                $result = $this->model()->search($queryString, $start, $len, $this->_scoring_function,
                    null, null, $this->_filter_category,
                    null, $this->_filter_docvar, null, implode(",", $this->_unfilter_category) );
                $this->_simple_query = true;
            }

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
        if (!empty($this->_result->facets_advanced)){
            foreach($this->_result->facets_advanced as $k => $v){
                $res[$k] = get_object_vars($v);
            }
        }

        return $res;
    }

    /**
     *
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
                    $categories[self::CT_CUSTOM_FIELD_PREFIX . $cf->field_name.'_'.$cf->field_code] = $product->{$cf->field_code};
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