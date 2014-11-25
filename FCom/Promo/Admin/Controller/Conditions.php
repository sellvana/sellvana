<?php

/**
 * Created by
 * User: pp
 * Date: 24.Nov14
 * @property FCom_CustomField_Model_Field $FCom_CustomField_Model_Field
 */
class FCom_Promo_Admin_Controller_Conditions extends FCom_Admin_Controller_Abstract
{

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
