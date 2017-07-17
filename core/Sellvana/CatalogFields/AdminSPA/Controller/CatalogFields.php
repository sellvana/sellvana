<?php

/**
 * Class Sellvana_CatalogFields_AdminSPA_Controller_CatalogFields
 *
 * @property FCom_Core_Model_Field       FCom_Core_Model_Field
 * @property FCom_Core_Model_FieldOption FCom_Core_Model_FieldOption
 */
class Sellvana_CatalogFields_AdminSPA_Controller_CatalogFields
    extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{

    static protected $_modelClass = 'FCom_Core_Model_Field';
    static protected $_modelName  = 'field';
    static protected $_recordName = 'Field';

    public function getGridConfig()
    {
        $fld = $this->{static::$_modelClass};

        $yesNoOpts = ['0' => 'No', '1' => 'Yes'];

        return [
            'id'       => 'catalog_fields',
            'data_url' => 'catalogfields/grid_data',
            'columns'  => [
                ['type' => 'row-select'],
                [
                    'type'    => 'actions',
                    'actions' => [
                        ['type' => 'edit', 'link' => 'fields/form?id={id}'],
                        ['type' => 'delete', 'delete_url' => true],
                    ]
                ],
                ['name' => 'id', 'label' => 'ID'],
                [
                    'name'              => 'field_code',
                    'label'             => 'Field Code',
                    'datacell_template' => '<td><a :href="\'#/catalog/fields/form?id=\'+row.id">{{row.field_code}}</a></td>'
                ],
                [
                    'name'              => 'field_name',
                    'label'             => 'Field Name',
                    'datacell_template' => '<td><a :href="\'#/catalog/fields/form?id=\'+row.id">{{row.field_name}}</a></td>'
                ],
                ['name' => 'field_type', 'label' => 'Field Type'],
                ['name' => 'frontend_label', 'label' => 'Frontend Label'],
                [
                    'name'    => 'frontend_show',
                    'label'   => 'Show on frontend',
                    'options' => $fld->fieldOptions('frontend_show')
                ],
                ['name' => 'sort_order', 'label' => 'Sort Order'],
                [
                    'name'    => 'table_field_type',
                    'label'   => 'DB Type',
                    'options' => $fld->fieldOptions('table_field_type')
                ],
                [
                    'name'    => 'admin_input_type',
                    'label'   => 'Input Type',
                    'options' => $fld->fieldOptions('admin_input_type')
                ],
                ['name' => 'num_options', 'label' => 'Options', 'default' => 0],
                ['name' => 'system', 'label' => 'System field', 'options' => $yesNoOpts],
                ['name' => 'multilanguage', 'label' => 'Multi language', 'options' => $yesNoOpts],
                ['name' => 'swatch_type', 'label' => 'Swatch type', 'options' => $fld->fieldOptions('swatch_type')],
                ['name' => 'required', 'label' => 'Required', 'options' => $yesNoOpts],
            ],
            'filters'  => [
                ['field' => 'id', 'type' => 'number'],
                ['field' => 'field_code', 'type' => 'text'],
                ['field' => 'field_name', 'type' => 'text'],
                ['field' => 'frontend_label', 'type' => 'text'],
                ['field' => 'frontend_show', 'type' => 'multiselect'],
                ['field' => 'table_field_type', 'type' => 'multiselect'],
                ['field' => 'admin_input_type', 'type' => 'multiselect'],
                ['field' => 'num_options', 'type' => 'text'],
                ['field' => 'system', 'type' => 'multiselect'],
                ['field' => 'multilanguage', 'type' => 'multiselect'],
                ['field' => 'swatch_type', 'type' => 'multiselect'],
                ['field' => 'required', 'type' => 'multiselect'],
            ],
            'export'   => true,
            'pager'    => true,
        ];
    }

    public function getGridOrm()
    {
        $subSql = '(select count(*) from ' . $this->FCom_Core_Model_FieldOption->table() . ' where field_id=f.id)';;

        return $this->FCom_Core_Model_Field
            ->orm('f')
            ->where('field_type', 'product')
            ->select('f.*')
            ->select($subSql, 'num_options');
    }

    public function getFormData()
    {
        $fId  = $this->BRequest->get('id');
        $bool = [['id' => 0, 'text' => 'no'], ['id' => 1, 'text' => 'Yes']];

        $field = $this->FCom_Core_Model_Field->load($fId);
        if (!$field) {
            throw new BException('Field not found');
        }

        $result = [];

        $result['form'][static::$_modelName] = $field->as_array();

        $fieldOptions = $this->FCom_Core_Model_FieldOption
            ->orm()
            ->select(['id', 'label', 'swatch_info'])
            ->where('field_id', $fId);

        $result['form']['options'] = array_map(function (BModel $obj) {
            return $obj->as_array();
        }, $fieldOptions->find_many());

		$result['form']['config']['page_actions'] = $this->getDefaultFormPageActions();
		$result['form']['config']['title'] = $field->get('field_name');

        $result['form']['config']['tabs']          = '/catalog/fields/form';
        $result['form']['config']['default_field'] = ['model' => 'field', 'tab' => 'info'];
        $result['form']['config']['fields']        = [ // still need to figure out what are the possible options for fields
                                                       [
                                                           'required' => true,
                                                           'name'     => 'field_code',
                                                           'label'    => 'Field Code'
                                                       ],
                                                       [
                                                           'required' => true,
                                                           'name'     => 'field_name',
                                                           'label'    => 'Field Name'
                                                       ],
                                                       [
                                                           'required' => true,
                                                           'name'     => 'frontend_label',
                                                           'label'    => 'Frontend label'
                                                       ],
                                                       [
                                                           'required' => true,
                                                           'name'     => 'frontend_show',
                                                           'label'    => 'Show on frontend',
                                                           'type'     => 'checkbox'
                                                       ],
                                                       [
                                                           'required' => true,
                                                           'name'     => 'sort_order',
                                                           'label'    => 'Sort order'
                                                       ],
                                                       [
                                                           'required' => true,
                                                           'name'     => 'table_field_type',
                                                           'label'    => 'DB Type',
                                                           'options'  => [
                                                               ['id' => 'varchar', 'text' => 'Short Text'],
                                                               ['id' => 'text', 'text' => 'Long Text'],
                                                               ['id' => 'options', 'text' => 'Options'],
                                                               ['id' => 'int', 'text' => 'Integer'],
                                                               ['id' => 'tinyint', 'text' => 'Tiny Integer'],
                                                               ['id' => 'decimal', 'text' => 'Decimal'],
                                                               ['id' => 'date', 'text' => 'Date'],
                                                               ['id' => 'datetime', 'text' => 'Date/Time'],
                                                               ['id' => 'serialized', 'text' => 'Serialized'],
                                                           ]
                                                       ],
                                                       [
                                                           'required' => true,
                                                           'name'     => 'admin_input_type',
                                                           'label'    => 'Input Type',
                                                           'options'  => [
                                                               ['id' => 'text', 'text' => 'Text Line'],
                                                               ['id' => 'textarea', 'text' => 'Text Area'],
                                                               ['id' => 'select', 'text' => 'Drop down'],
                                                               ['id' => 'multiselect', 'text' => 'Multiple Select'],
                                                               ['id' => 'boolean', 'text' => 'Yes/No'],
                                                               ['id' => 'wysiwyg', 'text' => 'WYSIWYG editor'],
                                                           ]
                                                       ],
                                                       [
                                                           'required' => true,
                                                           'name'     => 'multilanguage',
                                                           'label'    => 'Multi Language',
                                                           'type'     => 'checkbox'
                                                       ],
                                                       [
                                                           'required' => true,
                                                           'name'     => 'swatch_type',
                                                           'label'    => 'Swatch type',
                                                           'options'  => [
                                                               ['id' => 'N', 'text' => 'None'],
                                                               ['id' => 'C', 'text' => 'Color'],
                                                               ['id' => 'I', 'text' => 'Image'],
                                                           ]
                                                       ],
                                                       [
                                                           'required' => true,
                                                           'name'     => 'required',
                                                           'label'    => 'Required',
                                                           'type'     => 'checkbox'
                                                       ],
        ];

//		$result['form']['i18n'] = $this->getModelTranslations('field', $field->id());
//
        return $result;
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {
            $r     = $this->BRequest;
            $data  = $r->post();
            $id    = $r->param('id', true);
            $model = $this->FCom_Core_Model_Field->load($id);
            if (!$model) {
                throw new BException("This field does not exist");
            }
            if (isset($data['field'])) {
                $model->set($data['field']);
            }

            $modelData = $model->as_array();
            $validated = $model->validate($modelData, [], 'field');
            //if ($modelData !== $origModelData) {
            //var_dump($modelData);
            //$model->set($modelData);
            //}

            if ($validated) {
                $model->save();
                if (isset($data['options'])) {
                    $this->saveFieldOptions($model->id(), $data['options']);
                }
                $result         = $this->getFormData();
                $result['form'] = $this->normalizeFormConfig($result['form']);
                $this->ok()->addMessage(static::$_recordName . ' was saved successfully', 'success');
            } else {
                $result = ['status' => 'error'];
                $this->error()->addMessage('Cannot save data, please fix above errors', 'error');
            }

        } catch(Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    /**
     * Options should be in format:
     *  [
     *      [label: "Label", swatch_info: "INFO"],
 *          ...
     *  ]
     * @param       $id - Field Id
     * @param array $options - array of field options
     */
    protected function saveFieldOptions($id, array $options)
    {
        if (empty($options)) {
            return;
        }

        foreach ($options as $option) {
            if (isset($option['id'])) {
                $model = $this->FCom_Core_Model_FieldOption->load($id);
            } else {
                $model = $this->FCom_Core_Model_FieldOption->create();
            }

            $model->set($option);
            $model->set('field_id', $id);
            $modelData = $model->as_array();
            $validated = $model->validate($modelData, [], 'field');

            if ($validated) {
                $model->save();
            } else {
                $this->error()->addMessage('Cannot save data, please fix above errors', 'error');
            }
        }
    }
}
