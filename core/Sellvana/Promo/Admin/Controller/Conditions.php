<?php

/**
 * Created by
 * User: pp
 * Date: 24.Nov14
 *
 * @property Sellvana_CustomField_Model_Field       $Sellvana_CustomField_Model_Field
 * @property Sellvana_Catalog_Model_Product         $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_Category        $Sellvana_Catalog_Model_Category
 * @property Sellvana_Catalog_Model_InventorySku    $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_CustomField_Model_FieldOption $Sellvana_CustomField_Model_FieldOption
 * @property Sellvana_Sales_Main                    $Sellvana_Sales_Main
 * @property Sellvana_Cms_Model_Block               $Sellvana_Cms_Model_Block
 */
class Sellvana_Promo_Admin_Controller_Conditions extends FCom_Admin_Controller_Abstract
{

    /**
     * Fetch list of products to use in conditions
     */
    public function action_products()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->status('403', 'Available only for XHR', 'Available only for XHR');

            return;
        }

        $r       = $this->BRequest;
        $page    = $r->get('page')?: 1;
        $skuTerm = $r->get('q');
        $limit   = $r->get('o')?: 30;
        $offset  = ($page - 1) * $limit;

        /** @var BORM $orm */
        $orm = $this->Sellvana_Catalog_Model_Product->orm('p')->select(['id', 'product_sku', 'product_name'], 'p');
        if ($skuTerm && $skuTerm != '*') {
            $orm->where(['OR' => [['product_sku LIKE ?', "%{$skuTerm}%"], ['product_name LIKE ?', "%{$skuTerm}%"]]]);
        }

        $countOrm = clone $orm;
        $countOrm->select_expr('COUNT(*)', 'count');
        $stmt     = $countOrm->execute();
        $countRes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count    = $countRes[0]['count'];

        $orm->limit((int) $limit)->offset($offset)->order_by_asc('product_name');
        $stmt   = $orm->execute();
        $result = ['total_count' => $count, 'items' => []];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result['items'][] = [
                'id'   => $row['product_sku'],
                'text' => $row['product_name'],
                'sku'  => $row['product_sku'],
            ];
        }

        $this->BResponse->json($result);
    }

    /**
     * Fetch list of categories to use in conditions
     */
    public function action_categories()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->status('403', 'Available only for XHR', 'Available only for XHR');

            return;
        }

        $r       = $this->BRequest;
        $page    = $r->get('page')?: 1;
        $catTerm = $r->get('q');
        $limit   = $r->get('o')?: 30;
        $offset  = ($page - 1) * $limit;
        $ids = $r->get('cats');
        if ($ids) {
            $ids = explode(",", $ids);
        }

        /** @var BORM $orm */
        $orm = $this->Sellvana_Catalog_Model_Category->orm('c')->select(['id', 'full_name', 'node_name'], 'c');
        if (!$ids) {
            if ($catTerm && $catTerm != '*') {
                $orm->where([['full_name LIKE ?', "%{$catTerm}%"]]);
            }

            $countOrm = clone $orm;
            $countOrm->select_expr('COUNT(*)', 'count');
            $stmt     = $countOrm->execute();
            $countRes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count    = $countRes[0]['count'];
            $orm->limit((int) $limit)->offset($offset)->order_by_desc('node_name');
        }  else {
            $orm->where(["id" => $ids]);
            $count = 0;
        }
        $stmt   = $orm->execute();
        $result = ['total_count' => $count, 'items' => []];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
//            $result['total_count'] += 1;

            $result['items'][] = [
                'id'        => $row['id'],
                'text'      => $row['node_name'],
                'full_name' => $row['full_name'],
            ];
        }

        $this->BResponse->json($result);
    }

    public function action_attributes_list()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->status('403', 'Available only for XHR', 'Available only for XHR');

            return;
        }

        $r      = $this->BRequest;
        $page   = $r->get('page')?: 1;
        $term   = $r->get('q');
        $limit  = $r->get('o')?: 30;
        $offset = ($page - 1) * $limit;

        $orm = $this->Sellvana_CustomField_Model_Field->orm()->where('field_type', 'product');

        if ($term && $term != '*') {
            $orm->where(['OR' => [['field_code LIKE ?', "%{$term}%"], ['field_name LIKE ?', "%{$term}%"]]]);
        }

        $countOrm = clone $orm;
        $count    = $countOrm->count();
        $results  = ['total_count' => $count, 'items' => [
            ['id' => 'cart.qty', 'text' => 'Total Qty (cart)', 'input' => 'number'],
            ['id' => 'cart.amt', 'text' => 'Total Amount (cart)', 'input' => 'number'],
        ]];
        $orm->limit((int) $limit)->offset($offset)->order_by_desc('frontend_label');

        $orm->iterate(function ($model) use (&$results) {
            /** @var $model BModel */
            $result = [
                'id'   => 'field' . '.' . $model->get('field_code'),
                'text' => $model->get('frontend_label') . ' (field)',
            ];
            switch ($model->get('admin_input_type')) {
                case 'text':
                    $result['input'] = 'text';
                    break;
                case 'select':
                case 'multiselect':
                    $result['input'] = 'select';
                    break;
                case 'boolean':
                    $result['input'] = 'yes_no';
                    break;
                case 'number':
                    $result['input'] = 'number';
                    break;
                case 'date':
                    $result['input'] = 'date';
                    break;
                case 'wysiwyg':
                case 'textarea':
                default:
                    $result['input'] = 'text';
            }
            $results['items'][] = $result;

            return $results;
        });

        $base_product_fields = $this->searchTableFields($this->Sellvana_Catalog_Model_Product->table(), $term);
        $baseExclude         = ['id', 'images_data', 'data_serialized'];
        if (!empty($base_product_fields)) {
            foreach ($base_product_fields as $field => $fieldData) {
                $result = $this->prepareField($field, $fieldData, $baseExclude, 'static');
                if ($result) {
                    $results['items'][] = $result;
                }
            }

        }

        $stock_product_fields = $this->searchTableFields($this->Sellvana_Catalog_Model_InventorySku->table(), $term);
        $stockExclude         = ['id', 'inventory_sku', 'bin_id', 'data_serialized', 'manage_inventory'];
        if (!empty($stock_product_fields)) {
            foreach ($stock_product_fields as $field => $fieldData) {
                $result = $this->prepareField($field, $fieldData, $stockExclude, 'stock');
                if ($result) {
                    $results['items'][] = $result;
                }
            }
        }
        $this->BResponse->json($results);
    }

    public function action_attributes_field()
    {
        if (!$this->BRequest->xhr()) {
            $this->BResponse->status('403', 'Available only for XHR', 'Available only for XHR');

            return;
        }

        $fieldCode = explode('.', $this->BRequest->get('field'), 2);
        $fieldType = $fieldCode[0];
        $fieldCode = $fieldCode[1];

        $field = $this->Sellvana_CustomField_Model_Field->load($fieldCode, 'field_code');
        $options = [];

        if ($fieldType == 'field') {
            if ($field) {
                $options = $this->Sellvana_CustomField_Model_FieldOption->getListAssocbyId($field->id());
            } else {
                $options = [];
            }
        }

        $result = ['items' => []];

        foreach ($options as $id => $label) {
            $result['items'][] = [
                'id'   => $id,
                'text' => $label
            ];
        }

        $result['total_count'] = count($result['items']);
        $this->BResponse->json($result);
    }

    public function action_shipping()
    {
        $field = $this->BRequest->get('field');
        $result['items'] = [];
        switch ($field) {
            case 'methods':
                $methodCodes = $this->BRequest->get('methods');
                if ($methodCodes) {
                    $methodCodes = explode(",", $methodCodes);
                }
                $methods = $this->Sellvana_Sales_Main->getShippingMethods();
                foreach ($methods as $code => $method) {
                    /** @var Sellvana_Sales_Method_Shipping_Abstract $method */
                    $name              = $method->getName();
                    if (!$methodCodes) { // if looking for method codes, return just them
                        $result['items'][] = ['id' => $code, 'text' => $name];
                    } else if (in_array($code, $methodCodes)) {
                        $result['items'][] = ['id' => $code, 'text' => $name];
                    }
                }
                break;
            case 'country':
                $countryCodes = $this->BRequest->get('countries');
                if ($countryCodes) {
                    $countryCodes = explode(",", $countryCodes);
                }
                $countries = $this->FCom_Core_Main->getAllowedCountries();
                foreach ($countries as $code => $country) {
                    if (!$countryCodes) { // if looking for method codes, return just them
                        $result['items'][] = ['id' => $code, 'text' => $country];
                    } else if (in_array($code, $countryCodes)) {
                        $result['items'][] = ['id' => $code, 'text' => $country];
                    }
                }
                break;
            case 'region':
            case 'state':
                $regionCodes = $this->BRequest->get('regions');
            if ($regionCodes) {
                $regionCodes = explode(",", $regionCodes);
                }
                $regions = $this->FCom_Core_Main->getAllowedRegions();
                foreach ($regions as $country => $region) {
                    //$countryRegions = ['text' => trim($country, '@'), 'children' => []];
                    foreach ($region as $code => $r) {
                        if (!$regionCodes) {
                            $result['items'][] = ['id' => $code, 'text' => $r];
                        } elseif(in_array($code, $regionCodes)){
                            $result['items'][] = ['id' => $code, 'text' => $r];
                        }
                        //$countryRegions['children'][] = ['id' => $code, 'text' => $r];
                    }
                    //$result['items'][] = $countryRegions;
                }
                break;
            default :
                // todo
                break;
        }
        $result['total_count'] = 1;

        $this->BResponse->json($result);
    }

    public function action_cmsblocks()
    {
        $r       = $this->BRequest;
        $page    = $r->get('page')?: 1;
        $cmsblocksTerm = $r->get('q');
        $limit   = $r->get('o')?: 30;
        $offset  = ($page - 1) * $limit;
        $ids     = $r->get('cmsblocks');
        if ($ids) {
            $ids = explode(",", $ids);
        }

        /** @var BORM $orm */
        $orm = $this->Sellvana_Cms_Model_Block->orm('c')->select(['id', 'handle'], 'c');
        if (!$ids) {
            if ($cmsblocksTerm && $cmsblocksTerm != '*') {
                $orm->where([['handle LIKE ?', "%{$cmsblocksTerm}%"]]);
            }

            $countOrm = clone $orm;
            $countOrm->select_expr('COUNT(*)', 'count');
            $stmt     = $countOrm->execute();
            $countRes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count    = $countRes[0]['count'];
            $orm->limit((int) $limit)->offset($offset)->order_by_asc('handle');
        } else {
            $orm->where(["id" => $ids]);
            $count = 0;
        }
        $stmt   = $orm->execute();
        $result = ['total_count' => $count, 'items' => []];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result['items'][] = [
                'id'        => $row['id'],
                'text'      => $row['handle'],
            ];
        }

        $this->BResponse->json($result);
    }

    /**
     * @param string $field
     * @param BModel $fieldData
     * @param array  $exclude
     * @param null|string   $prefix
     * @return array
     */
    public function prepareField($field, $fieldData, $exclude = [], $prefix = null)
    {
        if (in_array($field, $exclude)) {
            return [];
        }
        $id     = $prefix? $prefix . '.' . $field: $field;
        $label  = ucwords(str_replace('_', ' ', $field));
        if ($prefix) {
            $label .= ' (' . $prefix . ')';
        }
        $result = [
            'id'   => $id,
            'text' => $label
        ];

        $type = explode('(', $fieldData->get('Type'), 2);
        switch (strtolower($type[0])) {
            case 'int':
            case 'decimal':
            case 'integer':
            case 'smallint':
            case 'mediumint':
            case 'bigint':
            case 'numeric':
            case 'double':
            case 'float':
                $result['input'] = 'number';
                break;
            case 'tinyint':
            case 'bit':
                $result['input'] = 'yes_no';
                break;
            case 'datetime':
            case 'date':
            case 'timestamp':
            case 'year':
                $result['input'] = 'date';
                break;
            case 'time':
                $result['input'] = 'time'; // ?
                break;
            case 'enum':
            case 'set':
                $result['input'] = 'select';
                break;
            case 'varchar':
            case 'text':
            case 'char':
            case 'binary':
            case 'varbinary':
            case 'blob':
            default:
                $result['input'] = 'text';
                break;
        }

        return $result;
    }

    /**
     * @param string $tableName
     * @param string $term
     * @return BModel[]
     */
    protected function searchTableFields($tableName, $term)
    {
        $sql = "SHOW FIELDS FROM `{$tableName}`";
        if ($term != '*') {
            $term = "%{$term}%";
            $sql .= "WHERE Field LIKE ?";
        }
        $res = BORM::i()->raw_query($sql, [$term])->find_many_assoc('Field');

        return $res;
    }
}
