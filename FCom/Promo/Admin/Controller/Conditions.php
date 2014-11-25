<?php

/**
 * Created by
 * User: pp
 * Date: 24.Nov14
 * @property FCom_CustomField_Model_Field $FCom_CustomField_Model_Field
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Catalog_Model_Category $FCom_Catalog_Model_Category
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
        $page    = $r->get('page') ?: 1;
        $skuTerm = $r->get('q');
        $limit   = $r->get('o') ?: 30;
        $offset  = ($page - 1) * $limit;

        /** @var BORM $orm */
        $orm = $this->FCom_Catalog_Model_Product->orm('p')->select(['id', 'product_sku', 'product_name'], 'p');
        if ($skuTerm) {
            $orm->where(['OR' => [['product_sku LIKE ?', "%{$skuTerm}%"], ['product_name LIKE ?', "%{$skuTerm}%"]]]);
        }

        $countOrm = clone $orm;
        $countOrm->select_expr('COUNT(*)', 'count');
        $stmt     = $countOrm->execute();
        $countRes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count    = $countRes[0]['count'];

        $orm->limit((int)$limit)->offset($offset)->order_by_desc('product_name');
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
        $page    = $r->get('page') ?: 1;
        $catTerm = $r->get('q');
        $limit   = $r->get('o') ?: 30;
        $offset  = ($page - 1) * $limit;

        /** @var BORM $orm */
        $orm = $this->FCom_Catalog_Model_Category->orm('c')->select(['id', 'full_name', 'node_name'], 'c');
        if ($catTerm) {
            $orm->where([['full_name LIKE ?', "%{$catTerm}%"]]);
        }

        $countOrm = clone $orm;
        $countOrm->select_expr('COUNT(*)', 'count');
        $stmt     = $countOrm->execute();
        $countRes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count    = $countRes[0]['count'];

        $orm->limit((int)$limit)->offset($offset)->order_by_desc('node_name');
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
        $page   = $r->get('page') ?: 1;
        $term   = $r->get('q');
        $limit  = $r->get('o') ?: 30;
        $offset = ($page - 1) * $limit;

        $orm = $this->FCom_CustomField_Model_Field->orm()->where('field_type', 'product');

        if ($term) {
            $orm->where(['OR' => [['field_code LIKE ?', "%{$term}%"], ['field_name LIKE ?', "%{$term}%"]]]);
        }

        $countOrm = clone $orm;
        $count    = $countOrm->count();
        $results  = ['total_count' => $count, 'items' => []];
        $orm->limit((int)$limit)->offset($offset)->order_by_desc('frontend_label');

        $orm->iterate(function ($model) use (&$results) {
            $results['items'][] = [
                'id'   => $model->id(),
                'text' => $model->get('frontend_label'),
            ];

            return $results;
        });

        $this->BResponse->json($results);
    }
}
