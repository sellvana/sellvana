<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_IndexTank_Frontend_Controller
 *
 * @property FCom_Catalog_Model_Category $FCom_Catalog_Model_Category
 * @property FCom_IndexTank_Index_Product $FCom_IndexTank_Index_Product
 * @property FCom_IndexTank_Search $FCom_IndexTank_Search
 * @property FCom_IndexTank_Model_ProductFunction $FCom_IndexTank_Model_ProductFunction
 */
class FCom_IndexTank_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_category()
    {
#echo "<pre>"; debug_print_backtrace(); print_r($this->BRouting->currentRoute()); exit;
        $category = $this->FCom_Catalog_Model_Category->load($this->BRequest->param('category'), 'url_path');
        if (!$category) {
            $this->forward(false);
            return $this;
        }

        $layout = $this->BLayout;
        $q = $this->BRequest->get('q');
        $sc = $this->BRequest->get('sc');
        $f = $this->BRequest->get('f');
        $v = $this->BRequest->get('v');
        $page = $this->BRequest->get('p');
        $resultPerPage = $this->BRequest->get('ps');

        if (empty($f['category'])) {
            $categoryKey = $this->FCom_IndexTank_Index_Product->getCategoryKey($category);
            $f['category'] = $categoryKey . ":" . $category->node_name;
        }

        $productsData = $this->FCom_IndexTank_Search->search($q, $sc, $f, $v, $page, $resultPerPage);
        $this->BEvents->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_data', ['data' => &$productsData]);

        $this->BApp->i()
            ->set('current_category', $category)
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        $this->FCom_Core_Main->lastNav(true);

        $this->layout('/catalog/category');
        $head = $this->view('head');
        $crumbs = ['home'];
        foreach ($category->ascendants() as $c) {
            if ($c->node_name) {
                $crumbs[] = ['label' => $c->node_name, 'href' => $c->url()];
                $head->addTitle($c->node_name);
            }
        }
        $crumbs[] = ['label' => $category->node_name, 'active' => true];
        $head->addTitle($category->node_name);
        $layout->view('breadcrumbs')->crumbs = $crumbs;

        $layout->view('catalog/search')->query = $q;
        $layout->view('catalog/search')->public_api_url = $this->FCom_IndexTank_Search->publicApiUrl();
        $layout->view('catalog/search')->index_name = $this->FCom_IndexTank_Search->indexName();

        $rowsViewName = 'catalog/product/' . ($this->BRequest->get('view') == 'list' ? 'list' : 'grid');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->category = $category;
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];

        $layout->view('catalog/product/pager')->sort_options = $this->FCom_IndexTank_Model_ProductFunction->getSortingArray();
        $layout->view('indextank/product/filters')->state = $productsData['state'];


    }

    public function action_search()
    {
        $req = $this->BRequest;
        $q = $req->get('q');
        if (!$q) {
            $this->BResponse->redirect('');
            return;
        }
        $sc = $req->get('sc');
        $f = $req->get('f');
        $v = $req->get('v');
        $page = $req->get('p');
        $resultPerPage = $req->get('ps');

        if (false == $this->BConfig->get('modules/FCom_IndexTank/index_name')) {
            die('Please set up correct API URL at Admin Setting page');
        }

        $productsData = $this->FCom_IndexTank_Search->search($q, $sc, $f, $v, $page, $resultPerPage);
        $this->BEvents->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_data', ['data' => &$productsData]);

        $this->BApp->i()
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        $this->FCom_Core_Main->lastNav(true);
        $layout = $this->BLayout;
        $this->layout('/catalog/search');
        $layout->view('breadcrumbs')->crumbs = ['home', ['label' => 'Search: ' . $q, 'active' => true]];
        $layout->view('catalog/search')->query = $q;
        $layout->view('catalog/search')->public_api_url = $this->FCom_IndexTank_Search->publicApiUrl();
        $layout->view('catalog/search')->index_name = $this->FCom_IndexTank_Search->indexName();

        $rowsViewName = 'catalog/product/' . ($this->BRequest->get('view') == 'list' ? 'list' : 'grid');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];

        $layout->view('catalog/product/pager')->sort_options = $this->FCom_IndexTank_Model_ProductFunction->getSortingArray();
        $layout->view('indextank/product/filters')->state = $productsData['state'];

    }



}
