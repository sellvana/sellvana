<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogIndex_Model_Field
 *
 * @property Sellvana_Catalog_Model_CategoryProduct $Sellvana_Catalog_Model_CategoryProduct
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 */
class Sellvana_CatalogIndex_Model_Field extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_index_field';

    protected static $_indexedFields;
    protected static $_sortingArray;

    protected static $_fieldOptions = [
        'field_type'        => [
            'int'      => 'Integer',
            'decimal'  => 'Decimal',
            'varchar'  => 'String',
            'text'     => 'Text',
            'category' => 'Category'
        ],
        'source_type'       => ['field' => 'Field', 'method' => 'Model Method', 'callback' => 'Callback'],
        'filter_type'       => [
            'none'      => 'None',
            'exclusive' => 'Exclusive',
            'inclusive' => 'Inclusive',
            'range'     => 'Range'
        ],
        'filter_multivalue' => [0 => 'No', 1 => 'Yes'],
        'filter_counts'     => [0 => 'No', 1 => 'Yes'],
        'filter_show_empty' => [0 => 'No', 1 => 'Yes'],
        'search_type'       => ['none' => 'None', 'terms' => 'Terms'],
        'sort_type'         => [
            'none' => 'None',
            'asc'  => 'Ascending Only',
            'desc' => 'Descending Only',
            'both' => 'Both Directions'
        ],
    ];
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['field_name', 'field_label','field_type'],
        'related'    => ['fcom_field_id' => 'Sellvana_CatalogFields_Model_Field.id'],
    ];
    public function getFields($context = 'all', $where = null)
    {
        if (!static::$_indexedFields) {
            $orm = $this->orm();
            if ($where) {
                $orm->where($where);
            }
            $fields = $orm->order_by_asc('filter_order')->find_many();
            foreach ($fields as $f) {
                $k = $f->get('field_name');
                static::$_indexedFields['all'][$k] = $f;
                if ($f->get('sort_type') !== 'none') {
                    static::$_indexedFields['sort'][$k] = $f;
                    $ft = $f->get('field_type');
                    $f->set('sort_method', $ft === 'varchar' || $ft === 'text');
                }
                if ($f->get('filter_type') !== 'none') {
                    static::$_indexedFields['filter'][$k] = $f;
                }
                if ($f->get('search_type') !== 'none') {
                    static::$_indexedFields['search'][$k] = $f;
                }
            }
        }
        return static::$_indexedFields[$context];
    }

    public function getSortingArray()
    {
        if (!static::$_sortingArray) {
            static::$_sortingArray = [];
            $sortFields = $this->getFields('sort');
            foreach ($sortFields as $fName => $field) {
                $sortType = $field->get('sort_type');
                $labels = explode('||', $field->get('sort_label'));
                $l1 = !empty($labels[0]) ? trim($labels[0]) : $field->get('field_label');
                $l2 = !empty($labels[1]) ? trim($labels[1]) : null;
                $sortBoth = $sortType == 'both';
                if ($sortType == 'asc' || $sortBoth) {
                    static::$_sortingArray['sort_' . $field->get('field_name') . ' asc'] = $l1 . (($sortBoth && empty($l2)) ? ' (Asc)' : '');
                }
                if ($sortType == 'desc' || $sortBoth) {
                    static::$_sortingArray['sort_' . $field->get('field_name') . ' desc'] = $sortBoth ? (empty($l2) ? $l1 . ' (Desc)' : $l2) : $l1;
                }
            }
        }
        return static::$_sortingArray;
    }

    public function indexCategory($products, $field)
    {
        // TODO: prefetch categories
        $data = [];
        /*
        foreach ($products as $p) {
            foreach ((array)$p->categories() as $c) {
                $data[$p->id][$c->url_path] = $c->url_path.' ==> '.$c->node_name;
                if (($ascendants = $c->category()->ascendants())) {
                    foreach ($ascendants as $c1) { //TODO: configuration?
                        if (!$c1->parent_id) {
                            continue;
                        }
                        $data[$p->id][$c1->url_path] = $c1->url_path.' ==> '.$c1->node_name;
                    }
                }
            }
        }
        */
        $pIds = [];
        foreach ($products as $p) {
            $pIds[] = $p->id();
        }
        $catIds = [];
        $prodCatIds = [];
        if ($pIds) {
            // fetch category - product associations
            $catProds = $this->Sellvana_Catalog_Model_CategoryProduct->orm('cp')
                ->join('Sellvana_Catalog_Model_Category', ['c.id', '=', 'cp.category_id'], 'c')
                ->select(['category_id', 'product_id', 'id_path'])
                ->where_in('product_id', $pIds)
                ->find_many();
            // find ascendant ids of associated categories
            foreach ($catProds as $cp) {
                $idPath = explode('/', $cp->get('id_path'));
                for ($i = sizeof($idPath)-1; $i > 0; $i--) {
                    $prodCatIds[$cp->product_id][] = $idPath[$i];
                    $catIds[$idPath[$i]] = $idPath[$i];
                }
            }
        }

        if ($catIds) {
            // fetch ascendants category names
            $categories = $this->Sellvana_Catalog_Model_Category->orm('c')
                ->select(['id', 'url_path', 'node_name'])
                ->where_in('id', $catIds)
                ->find_many_assoc('id');
            // fill index data
            foreach ($products as $p) {
                if (empty($prodCatIds[$p->id()])) {
                    continue;
                }
                foreach ($prodCatIds[$p->id()] as $cId) {
                    $c = $categories[$cId];
                    $data[$p->id()][$c->get('url_path')] = $c->get('url_path') . ' ==> ' . $c->get('node_name');
                }
            }
        }
        return $data;
    }

    public function indexPrice($products, $field)
    {
        $prices = $this->Sellvana_Catalog_Model_ProductPrice->collectProductsPrices($products);
        $data = [];
        foreach ($products as $p) {
            $data[$p->id()] = $p->getCatalogPrice();
        }
        return $data;
    }

    public function indexPriceRange($products, $field)
    {
        $prices = $this->Sellvana_Catalog_Model_ProductPrice->collectProductsPrices($products);
        $data = [];
        foreach ($products as $p) {
            $m = $p->getCatalogPrice();
            if     ($m ==    0) $v = '0         ==> FREE';
            elseif ($m <   100) $v = '1-99      ==> $1 to $99';
            elseif ($m <   200) $v = '100-199   ==> $100 to $199';
            elseif ($m <   300) $v = '200-299   ==> $200 to $299';
            elseif ($m <   400) $v = '300-399   ==> $300 to $399';
            elseif ($m <   500) $v = '400-499   ==> $400 to $499';
            elseif ($m <   600) $v = '500-599   ==> $500 to $599';
            elseif ($m <   700) $v = '600-699   ==> $600 to $699';
            elseif ($m <   800) $v = '700-799   ==> $700 to $799';
            elseif ($m <   900) $v = '800-899   ==> $800 to $899';
            elseif ($m <  1000) $v = '900-999   ==> $900 to $999';
            elseif ($m <  2000) $v = '1000-1999 ==> $1000 to $1999';
            elseif ($m <  3000) $v = '2000-2999 ==> $2000 to $2999';
            elseif ($m <  4000) $v = '3000-3999 ==> $3000 to $3999';
            elseif ($m <  5000) $v = '4000-4999 ==> $4000 to $4999';
            elseif ($m <  6000) $v = '5000-5999 ==> $5000 to $5999';
            elseif ($m <  7000) $v = '6000-6999 ==> $6000 to $6999';
            elseif ($m <  8000) $v = '7000-7999 ==> $7000 to $7999';
            elseif ($m <  9000) $v = '8000-8999 ==> $8000 to $8999';
            elseif ($m < 10000) $v = '9000-9999 ==> $9000 to $9999';
            else                $v = '10000-    ==> $10000 or more';
            $data[$p->id()] = $v;
        }
        return $data;
    }

    public function getSortMethod()
    {
        $ft = $this->get('field_type');
        return $ft === 'varchar' || $ft === 'text' ? 'join' : 'column';
    }
}
