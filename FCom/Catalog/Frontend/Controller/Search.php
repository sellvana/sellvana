<?php

class FCom_Catalog_Frontend_Controller_Search extends FCom_Frontend_Controller_Abstract
{
    public function action_category()
    {
        $layout = BLayout::i();
        $category = FCom_Catalog_Model_Category::i()->load(BRequest::i()->params('category'), 'url_path');
        if (!$category) {
            $this->forward(true);
            return $this;
            //$category = FCom_Catalog_Model_Category::orm()->where_null('parent_id')->find_one();
            //$productsORM = FCom_Catalog_Model_Product::i()->orm();
        }

        $productsORM = $this->prepareOrm($category);
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category.products_orm', array('data'=>$productsORM));
        $productsData = $productsORM->paginate(null, array('ps'=>25));
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category.products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_category', $category)
            ->set('products_data', $productsData);

        $head = $this->view('head');
        $crumbs = array('home');
        foreach ($category->ascendants() as $c) {
            if ($c->node_name) {
                $crumbs[] = array('label'=>$c->node_name, 'href'=>$c->url());
                $head->addTitle($c->node_name);
            }
        }
        $crumbs[] = array('label'=>$category->node_name, 'active'=>true);
        $head->addTitle($category->node_name);
        $layout->view('breadcrumbs')->crumbs = $crumbs;

        $rowsViewName = 'catalog/product/'.(BRequest::i()->get('view')=='grid' ? 'grid' : 'list');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->category = $category;
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];

        BLayout::i()->layout(array(
            '/catalog/category'=>array(
                array('view', 'root', 'set'=>array('show_left_col'=>true)),
                array('hook', 'sidebar-left', 'views'=>array('catalog/category/sidebar'))
            ),
         ));

        FCom_Core::lastNav(true);

        $this->layout('/catalog/category');
    }

    public function action_search()
    {
        $layout = BLayout::i();

        $productsORM = $this->prepareOrm();
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search.products_orm', array('data'=>$productsORM));
        $productsData = $productsORM->paginate(null, array('ps'=>25));
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search.products_data', array('data'=>&$productsData));

        $category = FCom_Catalog_Model_Category::orm()->where_null('parent_id')->find_one();
        BApp::i()
            ->set('current_category', $category)
            ->set('products_data', $productsData);


        $rowsViewName = 'catalog/product/'.(BRequest::i()->get('view')=='grid' ? 'grid' : 'list');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];


        BLayout::i()->layout(array(
            '/catalog/search'=>array(
                array('view', 'root', 'set'=>array('show_left_col'=>true)),
                array('hook', 'sidebar-left', 'views'=>array('catalog/category/sidebar'))
            ),
         ));

        FCom_Core::lastNav(true);
        $this->layout('/catalog/search');
    }

    private function prepareOrm($category = null)
    {
        $q = BRequest::i()->get('q');
        $filter = BRequest::i()->get('f');
        $qs = preg_split('#\s+#', $q, 0, PREG_SPLIT_NO_EMPTY);

        if ($category) {
            $productsORM = $category->productsORM();
        } else {
            $productsORM = FCom_Catalog_Model_Product::i()->orm();
        }

        $and = array();
        if ($qs) {
            foreach ($qs as $k) $and[] = array('product_name like ?', '%'.$k.'%');
            $productsORM->where(array('OR'=>array('manuf_sku'=>$q, 'AND'=>$and)));
        }

        if (!empty($filter)){
            foreach($filter as $field => $fieldVal) {
                $productsORM->where($field, $fieldVal);
            }
        }

        if ($q) {
            BApp::i()
                ->set('current_query', $q);
            BLayout::i()->view('breadcrumbs')->crumbs = array('home', array('label'=>'Search: '.$q, 'active'=>true));
            BLayout::i()->view('catalog/search')->query = $q;
        }

        return $productsORM;
    }
}