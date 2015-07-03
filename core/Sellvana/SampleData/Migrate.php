<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_SampleData_Migrate
 *
 * @property Sellvana_CatalogIndex_Model_Field $Sellvana_CatalogIndex_Model_Field
 * @property Sellvana_CatalogFields_Model_Field $Sellvana_CatalogFields_Model_Field
 */

class Sellvana_SampleData_Migrate extends BClass
{
    public function install__0_1_1()
    {
        $customField = $this->Sellvana_CatalogFields_Model_Field;
        $fields   = [
            'finish'    => [
                'field_code'       => 'finish',
                'field_name'       => 'Finish',
                'table_field_type' => 'varchar(255)',
                'admin_input_type' => 'select',
                'frontend_label'   => 'Finish',
                'frontend_show'    => 0,
                'sort_order'       => 1,
            ],
            'ship_type' => [
                'field_code'       => 'ship_type',
                'field_name'       => 'Ship type',
                'table_field_type' => 'varchar(255)',
                'admin_input_type' => 'select',
                'frontend_label'   => 'Ship type',
                'frontend_show'    => 0,
                'sort_order'       => 1,
            ],
            'lead_time' => [
                'field_code'       => 'lead_time',
                'field_name'       => 'Lead time',
                'table_field_type' => 'varchar(255)',
                'admin_input_type' => 'text',
                'frontend_label'   => 'Lead time',
                'frontend_show'    => 0,
                'sort_order'       => 1,
            ]
        ];
        $exist    = $customField->orm()->where_in('field_code', array_keys($fields))->find_many_assoc('field_code');

        //add custom fields
        foreach ($fields as $f => $data) {
            // create custom fields if they don't exist
            if (empty($exist[$f])) {
                $f = $customField->create($data)->save();
            } else {
                $f = $exist[$f];
            }

            $fieldName = $f->get('field_code');

            /* @var Sellvana_CatalogIndex_Model_Field $catalogIndexField */
            $catalogIndexField = $this->Sellvana_CatalogIndex_Model_Field->orm()->where('field_name', $fieldName)->find_one();

            if (!$catalogIndexField) {
                $data = [
                    "field_name"    => $f->get('field_code'),
                    "field_label"   => $f->get('field_name'),
                    "field_type"    => 'varchar',
                    "weight"        => 0,
                    "fcom_field_id" => $f->id(),
                    "search_type"   => 'none',
                    "sort_type"     => 'none',
                ];
                $catalogIndexField = $this->Sellvana_CatalogIndex_Model_Field->create($data);
                $catalogIndexField->save();
            }
        }
        $fields = [
            'finish',
            'ship_type',
            'lead_time'
        ];

        $indexFields = $customField->orm()->where_in('field_name', $fields)->find_many();
        $nextCount = BORM::get_db()->query('SELECT max(`filter_order`) as "max" FROM ' . $this->Sellvana_CatalogIndex_Model_Field->table())->fetchAll();
        $nextCount = $nextCount[0]['max'] + 1;

        if ($indexFields) {
            foreach ($indexFields as $f) {
                /** @var Sellvana_CatalogIndex_Model_Field $f */
                if ($f->get('filter_type') == 'none') {
                    $f->set('filter_type', 'inclusive');
                }
                if (!$f->get('filter_counts')) {
                    $f->set('filter_counts', 1);
                }
                if (!$f->get('filter_order')) {
                    $f->set('filter_order', $nextCount++);
                }
                $f->save();
            }

        }
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $fields = [
            'finish',
            'ship_type',
            'lead_time'
        ];

        $indexFields = $this->Sellvana_CatalogIndex_Model_Field->orm()->where_in('field_name', $fields)->find_many();
        $nextCount = BORM::get_db()->query('SELECT max(`filter_order`) as "max" FROM ' . $this->Sellvana_CatalogIndex_Model_Field->table())->fetchAll();
        $nextCount = $nextCount[0]['max'] + 1;

        if ($indexFields) {
            foreach ($indexFields as $f) {
                /** @var Sellvana_CatalogIndex_Model_Field $f */
                if ($f->get('filter_type') == 'none') {
                    $f->set('filter_type', 'inclusive');
                }
                if (!$f->get('filter_counts')) {
                    $f->set('filter_counts', 1);
                }
                if (!$f->get('filter_order')) {
                    $f->set('filter_order', $nextCount++);
                }
                $f->save();
            }

        }
    }
}
