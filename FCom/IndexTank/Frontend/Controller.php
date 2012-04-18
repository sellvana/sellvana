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

        $filters_selected = array();
        if ($f){
            foreach($f as $key => $values) {
                if (!is_array($values)){
                    $values = array($values);
                }
                $pos = strpos($key, FCom_IndexTank_Index_Product::CT_CUSTOM_FIELD_PREFIX);
                if ($pos !== false){
                    foreach ($values as $value){
                        FCom_IndexTank_Index_Product::i()->rollup_by($key, $value);
                    }
                }
                foreach ($values as $value){
                    FCom_IndexTank_Index_Product::i()->filter_by($key, $value);
                }
                $filters_selected[$key] = $values;
            }
            //unfilter price and brand to see all total found for each category price and brand
            FCom_IndexTank_Index_Product::i()->rollup_by(FCom_IndexTank_Index_Product::CT_PRICE_RANGE);
            FCom_IndexTank_Index_Product::i()->rollup_by(FCom_IndexTank_Index_Product::CT_BRAND);
        }

        if($v){
            foreach($v as $key => $values) {
                if (!is_array($values)){
                    $values = array($values);
                }
                $pos = strpos($key, 'price');
                if($pos !== false){
                    if ($values['from'] < $values['to']){
                        FCom_IndexTank_Index_Product::i()->filter_range(FCom_IndexTank_Index_Product::VAR_PRICE, $values['from'], $values['to']);
                    }
                }
            }
        }

        //$categories = FCom_Catalog_Model_Category::i()->orm('c')->where('parent_id', '1')->find_many();
        //print_r($categories);exit;

        $productsORM = FCom_IndexTank_Index_Product::i()->search($q);
        $facets = FCom_IndexTank_Index_Product::i()->getFacets();
        $isQuerySimple = FCom_IndexTank_Index_Product::i()->isSimpleQuery();

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

        if($facets){
            $category_data = array();
            $cf_data = array();
            $price_data = array();
            $brand_data = array();
            foreach($facets as $fname => $fvalues){
                $pos = strpos($fname, FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX);
                if ($pos !== false){
                    $id_path = substr($fname, strlen(FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX));
                    foreach($fvalues as $fvalue => $fcount) {
                        $obj = new stdClass();
                        $obj->name = $fvalue;
                        $obj->count = $fcount;
                        $category_data[$id_path] = $obj;
                    }
                }
                $pos = strpos($fname, FCom_IndexTank_Index_Product::CT_CUSTOM_FIELD_PREFIX);
                if ($pos !== false){
                    $path = substr($fname, strlen(FCom_IndexTank_Index_Product::CT_CUSTOM_FIELD_PREFIX));
                    list($custom_name) = explode("_", $path);
                    foreach($fvalues as $fvalue => $fcount) {
                        $obj = new stdClass();
                        $obj->name = $fvalue;
                        $obj->count = $fcount;
                        $obj->path = $path;
                        $cf_data[$custom_name][] = $obj;
                    }
                }
                $pos = strpos($fname, FCom_IndexTank_Index_Product::CT_PRICE_RANGE);
                if ($pos !== false){
                    foreach($fvalues as $fvalue => $fcount) {
                        $obj = new stdClass();
                        $obj->name = $fvalue;
                        $obj->count = $fcount;
                        $price_data[] = $obj;
                    }
                }

                $pos = strpos($fname, FCom_IndexTank_Index_Product::CT_BRAND);
                if ($pos !== false){
                    foreach($fvalues as $fvalue => $fcount) {
                        $obj = new stdClass();
                        $obj->name = $fvalue;
                        $obj->count = $fcount;
                        $brand_data[] = $obj;
                    }
                }
            }
            ksort($brand_data);
            ksort($price_data);
            ksort($cf_data);
            ksort($category_data);
        }

        $productsData['state']['info']['query_mode'] = $isQuerySimple ? 'simple' : 'standard';
        $productsData['state']['filter'] = $v;
        $productsData['state']['filter'][FCom_IndexTank_Index_Product::CT_CUSTOM_FIELD_PREFIX] = $cf_data;
        $productsData['state']['filter'][FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX] = $category_data;
        $productsData['state']['filter'][FCom_IndexTank_Index_Product::CT_PRICE_RANGE] = $price_data;
        $productsData['state']['filter'][FCom_IndexTank_Index_Product::CT_BRAND] = $brand_data;
//        $productsData['state']['filter_selected'][FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX] = $filters_selected[FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX];

        $productsData['state']['facets'] = $facets;
        $productsData['state']['filter_selected'][FCom_IndexTank_Index_Product::CT_PRICE_RANGE] = $filters_selected[FCom_IndexTank_Index_Product::CT_PRICE_RANGE];
        $productsData['state']['filter_selected'][FCom_IndexTank_Index_Product::CT_BRAND] = $filters_selected[FCom_IndexTank_Index_Product::CT_BRAND];
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
