<?php

class FCom_IndexTank_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{

    public function action_search()
    {
        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $sc = BRequest::i()->get('sc');
        $f = BRequest::i()->get('f');
        $v = BRequest::i()->get('v');
        $page = BRequest::i()->get('p');
        $result_per_page = BRequest::i()->get('ps');
        $r = BRequest::i()->get(); // GET request
        $q = trim($q);
        /*
        if (!$q) {
            BResponse::i()->redirect(BApp::baseUrl());
        }
         *
         */

        if(false == BConfig::i()->get('modules/FCom_IndexTank/index_name')){
            die('Please set up correct API URL at Admin Setting page');
        }

        if ($sc){
            FCom_IndexTank_Index_Product::i()->scoring_by($sc);
        }

        $product_fields = FCom_IndexTank_Model_ProductField::i()->get_list();
        $inclusive_fields = FCom_IndexTank_Model_ProductField::i()->get_inclusive_list();

        $filters_selected = array();

        if ($f){
            foreach($f as $key => $values) {
                $is_category = false;
                if($key == 'category'){
                    $is_category = true;
                    $kv = explode(":", $values);
                    if(empty($kv)){
                        continue;
                    }
                    $key = $kv[0];
                    $values = array($kv[1]);
                }
                if (!is_array($values)){
                    $values = array($values);
                }
                if( isset($inclusive_fields[$key]) ){
                    FCom_IndexTank_Index_Product::i()->rollup_by($key);
                }

                foreach ($values as $value){
                    FCom_IndexTank_Index_Product::i()->filter_by($key, $value);
                }
                $filters_selected[$key] = $values;
            }
        }

        if($v){
            $variables_fields = FCom_IndexTank_Model_ProductField::i()->get_varialbes_list();
            foreach($v as $key => $values) {
                if (!is_array($values)){
                    $values = array($values);
                }
                if( in_array($key, $variables_fields) ){
                    if ($values['from'] < $values['to']){
                        FCom_IndexTank_Index_Product::i()->filter_range($variables_fields[$key]->var_number, $values['from'], $values['to']);
                    }
                }
            }
        }

        if (empty($result_per_page)){
            $result_per_page = 25;
        }
        if(empty($page)){
            $page = 1;
        }
        $start = ($page - 1) * $result_per_page;

        $productsORM = FCom_IndexTank_Index_Product::i()->search($q, $start, $result_per_page);
        $facets = FCom_IndexTank_Index_Product::i()->getFacets();
        //print_r($facets);exit;
        $productsData = array();
        if ( $productsORM ) {
            //BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_search.products_orm', array('data'=>$productsORM));
            //$productsData = $productsORM->paginate(null, array('ps'=>25));
            //BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_search.products_data', array('data'=>&$productsData));

            //$productsData = FCom_IndexTank_Index_Product::i()->paginate($productsORM, $r, array('ps' => 25));
        }
        $productsData = FCom_IndexTank_Index_Product::i()->paginate($productsORM, $r, array('ps' => 25, 'c' => FCom_IndexTank_Index_Product::i()->total_found()));

        $facets_data = FCom_IndexTank_Index_Product::i()->collectFacets($facets);
        $categories_data = FCom_IndexTank_Index_Product::i()->collectCategories($facets);

        $productsData['state']['fields'] = $product_fields;
        $productsData['state']['facets'] = $facets;
        $productsData['state']['filter_selected'] = $filters_selected;
        $productsData['state']['available_facets'] = $facets_data;
        $productsData['state']['available_categories'] = $categories_data;
        $productsData['state']['filter'] = $v;
        $productsData['state']['save_filter'] = BConfig::i()->get('modules/FCom_IndexTank/save_filter');


        BApp::i()
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core::lastNav(true);
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Search: '.$q, 'active'=>true));
        $layout->view('indextank/search')->query = $q;
        $layout->view('indextank/product/list')->products_data = $productsData;

        $this->layout('/indextank/search');
        BResponse::i()->render();
    }



}
