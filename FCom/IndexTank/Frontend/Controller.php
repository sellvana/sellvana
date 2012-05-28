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
            FCom_IndexTank_Index_Product::i()->scoringBy($sc);
        }

        $productFields = FCom_IndexTank_Model_ProductField::i()->getList();
        $inclusiveFields = FCom_IndexTank_Model_ProductField::i()->getInclusiveList();

        $filtersSelected = array();

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
                if( isset($inclusiveFields[$key]) ){
                    FCom_IndexTank_Index_Product::i()->rollupBy($key);
                }

                foreach ($values as $value){
                    FCom_IndexTank_Index_Product::i()->filterBy($key, $value);
                }
                $filtersSelected[$key] = $values;
            }
        }

        if($v){
            $variablesFields = FCom_IndexTank_Model_ProductField::i()->getVarialbesList();
            foreach($v as $key => $values) {
                if (!is_array($values)){
                    $values = array($values);
                }
                if( in_array($key, $variablesFields) ){
                    if ($values['from'] < $values['to']){
                        FCom_IndexTank_Index_Product::i()->filterRange($variablesFields[$key]->var_number, $values['from'], $values['to']);
                    }
                }
            }
        }

        if (empty($resultPerPage)){
            $resultPerPage = 25;
        }
        if(empty($page)){
            $page = 1;
        }
        $start = ($page - 1) * $resultPerPage;

        $productsORM = FCom_IndexTank_Index_Product::i()->search($q, $start, $resultPerPage);
        $facets = FCom_IndexTank_Index_Product::i()->getFacets();
        //print_r($facets);exit;
        $productsData = array();
        if ( $productsORM ) {
            //BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_search.products_orm', array('data'=>$productsORM));
            //$productsData = $productsORM->paginate(null, array('ps'=>25));
            //BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_search.products_data', array('data'=>&$productsData));

            //$productsData = FCom_IndexTank_Index_Product::i()->paginate($productsORM, $r, array('ps' => 25));
        }
        $productsData = FCom_IndexTank_Index_Product::i()->paginate($productsORM, $r,
                array('ps' => 25, 'c' => FCom_IndexTank_Index_Product::i()->totalFound()));

        $facetsData = FCom_IndexTank_Index_Product::i()->collectFacets($facets);
        $categoriesData = FCom_IndexTank_Index_Product::i()->collectCategories($facets);

        $productsData['state']['fields'] = $productFields;
        $productsData['state']['facets'] = $facets;
        $productsData['state']['filter_selected'] = $filtersSelected;
        $productsData['state']['available_facets'] = $facetsData;
        $productsData['state']['available_categories'] = $categoriesData;
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
