<?php

/**
 * Created by
 * User: pp
 * Date: 24.Nov14
 *
 * @property FCom_CustomField_Model_Field       $FCom_CustomField_Model_Field
 * @property FCom_Catalog_Model_Product         $FCom_Catalog_Model_Product
 * @property FCom_Catalog_Model_Category        $FCom_Catalog_Model_Category
 * @property FCom_Catalog_Model_InventorySku    $FCom_Catalog_Model_InventorySku
 * @property FCom_CustomField_Model_FieldOption $FCom_CustomField_Model_FieldOption
 */
class FCom_Promo_Admin_Controller_Conditions extends FCom_Admin_Controller_Abstract
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
        $orm = $this->FCom_Catalog_Model_Product->orm('p')->select(['id', 'product_sku', 'product_name'], 'p');
        if ($skuTerm && $skuTerm != '*') {
            $orm->where(['OR' => [['product_sku LIKE ?', "%{$skuTerm}%"], ['product_name LIKE ?', "%{$skuTerm}%"]]]);
        }

        $countOrm = clone $orm;
        $countOrm->select_expr('COUNT(*)', 'count');
        $stmt     = $countOrm->execute();
        $countRes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count    = $countRes[0]['count'];

        $orm->limit((int) $limit)->offset($offset)->order_by_desc('product_name');
        $stmt   = $orm->execute();
        $result = ['total_count' => $count, 'items' => []];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result['items'][] = [
                'id'   => $row['id'],
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

        /** @var BORM $orm */
        $orm = $this->FCom_Catalog_Model_Category->orm('c')->select(['id', 'full_name', 'node_name'], 'c');
        if ($catTerm && $catTerm != '*') {
            $orm->where([['full_name LIKE ?', "%{$catTerm}%"]]);
        }

        $countOrm = clone $orm;
        $countOrm->select_expr('COUNT(*)', 'count');
        $stmt     = $countOrm->execute();
        $countRes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count    = $countRes[0]['count'];

        $orm->limit((int) $limit)->offset($offset)->order_by_desc('node_name');
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

        $orm = $this->FCom_CustomField_Model_Field->orm()->where('field_type', 'product');

        if ($term && $term != '*') {
            $orm->where(['OR' => [['field_code LIKE ?', "%{$term}%"], ['field_name LIKE ?', "%{$term}%"]]]);
        }

        $countOrm = clone $orm;
        $count    = $countOrm->count();
        $results  = ['total_count' => $count, 'items' => []];
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

        $base_product_fields = $this->searchTableFields($this->FCom_Catalog_Model_Product->table(), $term);
        $baseExclude         = ['id', 'images_data', 'data_serialized'];
        if (!empty($base_product_fields)) {
            foreach ($base_product_fields as $field => $fieldData) {
                $result = $this->prepareField($field, $fieldData, $baseExclude, 'static');
                if ($result) {
                    $results['items'][] = $result;
                }
            }

        }

        $stock_product_fields = $this->searchTableFields($this->FCom_Catalog_Model_InventorySku->table(), $term);
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

        $r         = $this->BRequest;
        $page      = $r->get('page')?: 1;
        $fieldCode = explode('.', $r->get('field'), 2);
        $limit     = $r->get('o')?: 30;
        $offset    = ($page - 1) * $limit;

        $field = $this->FCom_CustomField_Model_Field->load($fieldCode[1], 'field_code');

        if ($field) {
            $options = $this->FCom_CustomField_Model_FieldOption->getListAssocbyId($field->id());
        } else {
            $options = [];
        }

        $result = ['more' => false, 'items' => []];

        foreach ($options as $id => $label) {
            $result['items'][] = [
                'id'   => $id,
                'text' => $label
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
