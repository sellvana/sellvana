<?php

class FCom_IndexTank_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{

    public function action_search()
    {
        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $sc = BRequest::i()->get('sc');
        $f = BRequest::i()->get('f');
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
                foreach ($values as $value){
                    FCom_IndexTank_Index_Product::i()->filter_by($key, $value);
                }
                $filters_selected[$key] = $values;
            }
            //unfilter price and brand to see all total found for each category price and brand
            FCom_IndexTank_Index_Product::i()->unfilter_by(FCom_IndexTank_Index_Product::CT_PRICE_RANGE);
            FCom_IndexTank_Index_Product::i()->unfilter_by(FCom_IndexTank_Index_Product::CT_BRAND);
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

            $productsData = $this->paginate($productsORM, $r, array('ps' => 25));
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
            foreach($facets as $fname => $fvalues){
                $pos = strpos($fname, FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX);
                if ($pos === false){
                    continue;
                }
                $id_path = substr($fname, strlen(FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX));
                foreach($fvalues as $fvalue => $fcount) {
                    $obj = new stdClass();
                    $obj->name = $fvalue;
                    $obj->count = $fcount;
                    $category_data[$id_path] = $obj;
                }
            }
            ksort($category_data);
        }

        $productsData['state']['filter'][FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX] = $category_data;
//        $productsData['state']['filter_selected'][FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX] = $filters_selected[FCom_IndexTank_Index_Product::CT_CATEGORY_PREFIX];

        $productsData['state']['facets'] = $facets;
        $productsData['state']['filter'][FCom_IndexTank_Index_Product::CT_PRICE_RANGE] = array('$0 to $99', '$100 to $299', '$300+');
        $productsData['state']['filter_selected'][FCom_IndexTank_Index_Product::CT_PRICE_RANGE] = $filters_selected[FCom_IndexTank_Index_Product::CT_PRICE_RANGE];

        $productsData['state']['filter'][FCom_IndexTank_Index_Product::CT_BRAND] = array('Brand 1', 'Brand 2', 'Brand 3');
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

    public function paginate($orm, $r, $d=array())
    {
        $d = (array)$d; // make sure it's array

        if (!empty($r['s']) && !empty($d['s']) && is_array($d['s'])) { // limit by these values only
            if (!in_array($r['s'], $d['s'])) $r['s'] = null;
            $d['s'] = null;
        }

        $s = array( // state
            'p'  => !empty($r['p'])  && is_numeric($r['p']) ? $r['p']  : (isset($d['p'])  ? $d['p']  : 1), // page
            'ps' => !empty($r['ps']) && is_numeric($r['ps']) ? $r['ps'] : (isset($d['ps']) ? $d['ps'] : 100), // page size
            's'  => !empty($r['s'])  ? $r['s']  : (isset($d['s'])  ? $d['s']  : ''), // sort by
            'sd' => !empty($r['sd']) ? $r['sd'] : (isset($d['sd']) ? $d['sd'] : 'asc'), // sort dir
            'rs' => !empty($r['rs']) ? $r['rs'] : null,
            'rc' => !empty($r['rc']) ? $r['rc'] : null,
            'sc' => !empty($r['sc']) ? $r['sc'] : null,
        );

        $cntOrm = clone $orm; // clone ORM to count

        $s['c'] = $cntOrm->count(); // total row count
        unset($cntOrm); // free mem

        $s['mp'] = ceil($s['c']/$s['ps']); // max page
        if (($s['p']-1)*$s['ps']>$s['c']) $s['p'] = $s['mp']; // limit to max page
        $s['rs'] = max(0, isset($s['rs']) ? $s['rs'] : ($s['p']-1)*$s['ps']); // start from requested row or page

        $orm->offset($s['rs'])->limit($s['ps']); // limit rows to page
        $rows = $orm->find_many(); // result data
        $s['rc'] = $rows ? sizeof($rows) : 0; // returned row count

        return array('state'=>$s, 'rows'=>$rows);
    }

}
