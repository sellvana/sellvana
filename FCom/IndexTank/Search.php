<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_IndexTank_Search extends BClass
{
    /**
     *
     * @param type $q - query
     * @param type $sc - sort by
     * @param type $f - filters
     * @param type $v - filter by range
     * @param int $page - current page
     * @param int $resultPerPage - results per page
     * @return Array
     */
    public function search($q, $sc, $f, $v, $page, $resultPerPage)
    {
        $r = $this->BRequest->get(); // GET request
        $q = trim($q);

        if ($sc) {
            $this->FCom_IndexTank_Index_Product->scoringBy($sc);
        }

        $productFields = $this->FCom_IndexTank_Model_ProductField->getList();
        $inclusiveFields = $this->FCom_IndexTank_Model_ProductField->getInclusiveList();

        $filtersSelected = [];

        $categorySelected = '';

        if ($f) {
            foreach ($f as $key => $values) {
                $is_category = false;
                if ($key == 'category') {
                    $is_category = true;
                    $kv = explode(":", $values);
                    if (empty($kv)) {
                        continue;
                    }
                    $key = $kv[0];
                    $values = [$kv[1]];
                    $categorySelected = $key;
                }

                if (!is_array($values)) {
                    $values = [$values];
                }
                if (isset($inclusiveFields[$key])) {
                    $this->FCom_IndexTank_Index_Product->rollupBy($key);
                }

                foreach ($values as $value) {
                    $this->FCom_IndexTank_Index_Product->filterBy($key, $value);
                }
                $filtersSelected[$key] = $values;
            }
        }

        if ($v) {
            $variablesFields = $this->FCom_IndexTank_Model_ProductField->getVariablesList();
            foreach ($v as $key => $values) {
                if (!is_array($values)) {
                    $values = [$values];
                }
                if (in_array($key, $variablesFields)) {
                    if ($values['from'] < $values['to']) {
                        $this->FCom_IndexTank_Index_Product->filterRange($variablesFields[$key]->var_number, $values['from'], $values['to']);
                    }
                }
            }
        }

        if (empty($resultPerPage)) {
            $resultPerPage = 25;
        }
        if (empty($page)) {
            $page = 1;
        }
        $start = ($page - 1) * $resultPerPage;

        $productsORM = $this->FCom_IndexTank_Index_Product->search($q, $start, $resultPerPage);
        $facets = $this->FCom_IndexTank_Index_Product->getFacets();


        $productsData = $this->FCom_IndexTank_Index_Product->paginate($productsORM, $r,
                ['ps' => 25, 'c' => $this->FCom_IndexTank_Index_Product->totalFound()]);

        //get all facets exclude categories
        $facetsData = $this->FCom_IndexTank_Index_Product->collectFacets($facets);
        $categoriesData = $this->FCom_IndexTank_Index_Product->collectCategories($facets, $categorySelected);


        $productsData['state']['fields'] = $productFields;
        $productsData['state']['facets'] = $facets;
        $productsData['state']['filter_selected'] = $filtersSelected;
        $productsData['state']['available_facets'] = $facetsData;
        $productsData['state']['available_categories'] = $categoriesData;
        $productsData['state']['category_selected'] = $categorySelected;
        $productsData['state']['filter'] = $v;
        $productsData['state']['save_filter'] = $this->BConfig->get('modules/FCom_IndexTank/save_filter');

        BEvents::i()->fire(__METHOD__, ['data' => &$productsData]);

        return $productsData;
    }

    public function publicApiUrl()
    {
        $url = '';
        if ($this->BConfig->get('modules/FCom_IndexTank/api_url')) {
            $url = $this->BConfig->get('modules/FCom_IndexTank/api_url');
            $parsed = parse_url($url);
            unset($parsed['pass']);
            $url = $this->BUtil->unparseUrl($parsed);
        }
        return $url;
    }

    public function indexName()
    {
        if ($this->BConfig->get('modules/FCom_IndexTank/index_name')) {
            return $this->BConfig->get('modules/FCom_IndexTank/index_name');
        }
        return '';
    }

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Search
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }
}
