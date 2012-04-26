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
        $r = BRequest::i()->get(); // GET request
        $q = trim($q);
        /*
        if (!$q) {
            BResponse::i()->redirect(BApp::baseUrl());
        }
         *
         */

        if ($sc){
            FCom_IndexTank_Index_Product::i()->scoring_by($sc);
        }

        $product_fields = FCom_IndexTank_Model_ProductFields::i()->get_list();
        $inclusive_fields = FCom_IndexTank_Model_ProductFields::i()->get_inclusive_list();
        $filters_selected = array();
        $filters_invisible = array();
        if ($f){
            foreach($f as $key => $values) {
                if (!is_array($values)){
                    $values = array($values);
                }
                if( isset($inclusive_fields[$key]) ){
                    FCom_IndexTank_Index_Product::i()->rollup_by($key);
                }

                foreach ($values as $value){
                    FCom_IndexTank_Index_Product::i()->filter_by($key, $value);
                    $filters_invisible[$key][$value] = $value;
                }
                $filters_selected[$key] = $values;

            }
        }

        if($v){
            $variables_fields = FCom_IndexTank_Model_ProductFields::i()->get_varialbes_list();
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

        //$categories = FCom_Catalog_Model_Category::i()->orm('c')->where('parent_id', '1')->find_many();
        //print_r($categories);exit;

        $productsORM = FCom_IndexTank_Index_Product::i()->search($q);
        $facets = FCom_IndexTank_Index_Product::i()->getFacets();
        $productsData = array();
        if ( $productsORM ) {
            //BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_search.products_orm', array('data'=>$productsORM));
            //$productsData = $productsORM->paginate(null, array('ps'=>25));
            //BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_search.products_data', array('data'=>&$productsData));

            $productsData = FCom_IndexTank_Index_Product::i()->paginate($productsORM, $r, array('ps' => 25));
        }

        //unset some filters and get facets again
        //$filters_unset = array(FCom_IndexTank_Index_Product::CT_PRICE_RANGE, FCom_IndexTank_Index_Product::CT_BRAND);
        //foreach($filters_unset as $filter){
//            FCom_IndexTank_Index_Product::i()->filter_unset($filter);
//        }
        //fire second request for smart facets
//        FCom_IndexTank_Index_Product::i()->search($q);
//        $facets = FCom_IndexTank_Index_Product::i()->getFacets();

        /*
         * if( in_array($fname, $facets_fields) ){
                    $id_path = substr($fname, strlen($facets_fields[$fname]->field_name));
         */
        if($facets){
            $facets_fields = FCom_IndexTank_Model_ProductFields::i()->get_facets_list();
            $facets_data = array();
            $category_data = array();
            //$cf_data = array();
            //$other_data = array();
            //$brand_data = array();

            //get categories
            foreach($facets as $fname => $fvalues){
                //hard coded ct_categories prefix
                $pos = strpos($fname, 'ct_categories___');
                if ($pos !== false){
                    $path = substr($fname, strlen('ct_categories___'));
                    $level = count(explode("__", $path))-1;
                    foreach($fvalues as $fvalue => $fcount) {
                        $obj = new stdClass();
                        $obj->name = $fvalue;
                        $obj->count = $fcount;
                        $obj->key = $fname;
                        $obj->level = $level;
                        $category_data['Categories'][$path] = $obj;
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
                            $facets_data[$facets_fields[$fname]->field_nice_name][] = $obj;
                            unset($filters_invisible[$fname][$fvalue]);
                    }
                }
            }

            ksort($category_data['Categories']);
            //put categories first
            $facets_data = (array)$category_data + (array)$facets_data;

/*
                print_r($facets);exit;
                if( in_array($fname, $facets_fields) ){
                    if(false !== strpos($fname, "___")){
                        $path = substr($fname, strlen($facets_fields[$fname]));
                        list($custom_name) = explode("___", $path);
                    } else {
                        $custom_name = $fname;
                        $path = '';
                    }

                    foreach($fvalues as $fvalue => $fcount) {
                        $obj = new stdClass();
                        $obj->name = $fvalue;
                        $obj->count = $fcount;
                        $obj->path = $path;
                        $facets_data[$custom_name][] = $obj;
                        unset($filters_invisible[$fname][$fvalue]);
                    }
                }
 *
 */
                /*
                $pos = strpos($fname, FCom_IndexTank_Index_Product::CT_PRICE_RANGE);
                if ($pos !== false){
                    foreach($fvalues as $fvalue => $fcount) {
                        $obj = new stdClass();
                        $obj->name = $fvalue;
                        $obj->count = $fcount;
                        $price_data[] = $obj;
                        unset($filters_invisible[$fname][$fvalue]);
                    }
                }

                $pos = strpos($fname, FCom_IndexTank_Index_Product::CT_BRAND);
                if ($pos !== false){
                    foreach($fvalues as $fvalue => $fcount) {
                        $obj = new stdClass();
                        $obj->name = $fvalue;
                        $obj->count = $fcount;
                        $brand_data[] = $obj;
                        unset($filters_invisible[$fname][$fvalue]);
                    }
                }
                 *
                 */
            //}
            //print_r($facets_data);
            //ksort($brand_data);
           // ksort($other_data);
            //ksort($cf_data);
            //ksort($category_data);
        }
        //print_r($facets_data);exit;

        $productsData['state']['fields'] = $product_fields;
        $productsData['state']['facets'] = $facets;
        $productsData['state']['filter_selected'] = $filters_selected;
        $productsData['state']['filter_invisible'] = $filters_invisible;
        $productsData['state']['available_facets'] = $facets_data;
        $productsData['state']['filter'] = $v;

        //$productsData['state']['filter'][FCom_IndexTank_Index_Product::CT_CUSTOM_FIELD_PREFIX] = $cf_data;
        //$productsData['state']['filter'][FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX] = $category_data;
        //$productsData['state']['filter'][FCom_IndexTank_Index_Product::CT_PRICE_RANGE] = $price_data;
        //$productsData['state']['filter'][FCom_IndexTank_Index_Product::CT_BRAND] = $brand_data;
//        $productsData['state']['filter_selected'][FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX] = $filters_selected[FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX];


        //$productsData['state']['filter_selected'][FCom_IndexTank_Index_Product::CT_PRICE_RANGE] = $filters_selected[FCom_IndexTank_Index_Product::CT_PRICE_RANGE];
        //$productsData['state']['filter_selected'][FCom_IndexTank_Index_Product::CT_BRAND] = $filters_selected[FCom_IndexTank_Index_Product::CT_BRAND];


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
