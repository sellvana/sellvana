<?php

/**
 * Class Sellvana_CatalogFields_AdminSPA_Controller_CatalogFields
 *
 * @property FCom_Core_Model_Field FCom_Core_Model_Field
 * @property FCom_Core_Model_FieldOption FCom_Core_Model_FieldOption
 */
class Sellvana_CatalogFields_AdminSPA_Controller_CatalogFields
    extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{

    static protected $_modelClass = 'FCom_Core_Model_Field';
    static protected $_modelName = 'field';
    static protected $_recordName = (('Field'));

    public function getGridConfig()
    {
        $fld = $this->{static::$_modelClass};

        $yesNoOpts = ['0' => (('No')), '1' => (('Yes'))];

        return [
            static::ID => 'catalog_fields',
            static::DATA_URL => 'catalogfields/grid_data',
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT],
                [static::TYPE => 'actions', static::ACTIONS => [
                    [static::TYPE => 'edit', static::LINK => 'fields/form?id={id}'],
                    [static::TYPE => 'delete', 'delete_url' => true],
                ]],
                [static::NAME => 'id', static::LABEL => (('ID'))],
                [static::NAME => 'field_code', static::LABEL => (('Field Code')),
                 static::DATACELL_TEMPLATE => '<td><a :href="\'#/catalog/fields/form?id=\'+row.id">{{row.field_code}}</a></td>'],
                [static::NAME => 'field_name', static::LABEL => (('Field Name')),
                 static::DATACELL_TEMPLATE => '<td><a :href="\'#/catalog/fields/form?id=\'+row.id">{{row.field_name}}</a></td>'],
                [static::NAME => 'field_type', static::LABEL => (('Field Type'))],
                [static::NAME => 'frontend_label', static::LABEL => (('Frontend Label'))],
                [static::NAME => 'frontend_show', static::LABEL => (('Show on frontend')),
                 static::OPTIONS => $fld->fieldOptions('frontend_show')],
                [static::NAME => 'sort_order', static::LABEL => (('Sort Order'))],
                [static::NAME => 'table_field_type', static::LABEL => (('DB Type')), static::OPTIONS => $fld->fieldOptions('table_field_type')],
                [static::NAME => 'admin_input_type', static::LABEL => (('Input Type')),
                 static::OPTIONS => $fld->fieldOptions('admin_input_type')],
                [static::NAME => 'num_options', static::LABEL => (('Options')), static::DEFAULT_FIELD => 0],
                [static::NAME => 'system', static::LABEL => (('System field')), static::OPTIONS => $yesNoOpts],
                [static::NAME => 'multilanguage', static::LABEL => (('Multi language')), static::OPTIONS => $yesNoOpts],
                [static::NAME => 'swatch_type', static::LABEL => (('Swatch type')), static::OPTIONS => $fld->fieldOptions('swatch_type')],
                [static::NAME => 'required', static::LABEL => (('Required')), static::OPTIONS => $yesNoOpts],
            ],
            static::FILTERS => [
                ['field' => 'id', static::TYPE => 'number'],
                ['field' => 'field_code', static::TYPE => 'text'],
                ['field' => 'field_name', static::TYPE => 'text'],
                ['field' => 'frontend_label', static::TYPE => 'text'],
                ['field' => 'frontend_show', static::TYPE => 'multiselect'],
                ['field' => 'table_field_type', static::TYPE => 'multiselect'],
                ['field' => 'admin_input_type', static::TYPE => 'multiselect'],
                ['field' => 'num_options', static::TYPE => 'text'],
                ['field' => 'system', static::TYPE => 'multiselect'],
                ['field' => 'multilanguage', static::TYPE => 'multiselect'],
                ['field' => 'swatch_type', static::TYPE => 'multiselect'],
                ['field' => 'required', static::TYPE => 'multiselect'],
            ],
            static::EXPORT => true,
            static::PAGER => true,
        ];
    }

    public function getGridOrm()
    {
        $subSql = '(select count(*) from ' . $this->FCom_Core_Model_FieldOption->table() . ' where field_id=f.id)';

        return $this->FCom_Core_Model_Field
            ->orm('f')
            ->where('field_type', 'product')
            ->select('f.*')
            ->select($subSql, 'num_options');
    }

    public function getFormData()
    {
        $fId = $this->BRequest->get('id');
        $bool = [[static::ID => 0, 'text' => 'no'], [static::ID => 1, 'text' => (('Yes'))]];

        $field = $this->FCom_Core_Model_Field->load($fId);
        if (!$field) {
            throw new BException('Field not found');
        }

        $result = [];

        $result[static::FORM][static::$_modelName] = $field->as_array();

        $fieldOptions = $this->FCom_Core_Model_FieldOption
            ->orm()
            ->select(['id', 'label', 'swatch_info'])
            ->where('field_id', $fId);

        $result['form']['options'] = array_map(function (BModel $obj) {
            return $obj->as_array();
        }, $fieldOptions->find_many());

		$result[static::FORM][static::CONFIG][static::PAGE_ACTIONS] = $this->getDefaultFormPageActions();
		$result[static::FORM][static::CONFIG][static::TITLE] = $field->get('field_name');

		$result[static::FORM][static::CONFIG][static::TABS] = '/catalog/fields/form';
		$result[static::FORM][static::CONFIG][static::FIELDS] = [ // still need to figure out what are the possible options for fields
            static::DEFAULT_FIELD => [static::MODEL => 'field', static::TAB => 'info'],
			[ static::REQUIRED => true, static::NAME => 'field_code', static::LABEL => (('Field Code'))],
			[ static::REQUIRED => true, static::NAME => 'field_name', static::LABEL => (('Field Name'))],
			[ static::REQUIRED => true, static::NAME => 'frontend_label', static::LABEL => (('Frontend label'))],
			[ static::REQUIRED => true, static::NAME => 'frontend_show', static::LABEL => (('Show on frontend')), static::TYPE => 'checkbox'],
			[ static::REQUIRED => true, static::NAME => 'sort_order', static::LABEL => (('Sort order'))],
			[ static::REQUIRED => true, static::NAME => 'table_field_type', static::LABEL => (('DB Type')), static::OPTIONS => [
                [static::ID => 'varchar', 'text' => (('Short Text'))],
                [static::ID => 'text', 'text' => (('Long Text'))],
                [static::ID => 'options', 'text' => (('Options'))],
                [static::ID => 'int', 'text' => (('Integer'))],
                [static::ID => 'tinyint', 'text' => (('Tiny Integer'))],
                [static::ID => 'decimal', 'text' => (('Decimal'))],
                [static::ID => 'date', 'text' => (('Date'))],
                [static::ID => 'datetime', 'text' => (('Date/Time'))],
                [static::ID => 'serialized', 'text' => (('Serialized'))],
            ]],
			[ static::REQUIRED => true, static::NAME => 'admin_input_type', static::LABEL => (('Input Type')), static::OPTIONS => [
                [static::ID => 'text', 'text' => (('Text Line'))],
                [static::ID => 'textarea', 'text' => (('Text Area'))],
                [static::ID => 'select', 'text' => (('Drop down'))],
                [static::ID => 'multiselect', 'text' => (('Multiple Select'))],
                [static::ID => 'boolean', 'text' => (('Yes/No'))],
                [static::ID => 'wysiwyg', 'text' => (('WYSIWYG editor'))],
            ]],
			[ static::REQUIRED => true, static::NAME => 'multilanguage', static::LABEL => (('Multi Language')), static::TYPE => 'checkbox'],
			[ static::REQUIRED => true, static::NAME => 'swatch_type', static::LABEL => (('Swatch type')), static::OPTIONS => [
			    ['id'=> 'N', 'text' => (('None'))],
			    ['id'=> 'C', 'text' => (('Color'))],
			    ['id'=> 'I', 'text' => (('Image'))],
            ]],
			[ static::REQUIRED => true, static::NAME => 'required', static::LABEL => (('Required')), static::TYPE => 'checkbox'],
		];

//		$result[static::FORM][static::I18N] = $this->getModelTranslations('field', $field->id());
//
        return $result;
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {
            $r = $this->BRequest;
            $data = $r->post();
            $id = $r->param('id', true);
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
                $model->save();if (isset($data['options'])) {
                    $this->saveFieldOptions($model->id(), $data['options']);
                }
                $result = $this->getFormData();
                $result[static::FORM] = $this->normalizeFormConfig($result[static::FORM]);
                $this->ok()->addMessage(static::$_recordName . ' was saved successfully', 'success');
            } else {
                $result = ['status' => 'error'];
                $this->error()->addMessage('Cannot save data, please fix above errors', 'error');
            }

        } catch (Exception $e) {
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
     *
     * @param       $id      - Field Id
     * @param array $options - array of field options
     * @throws \BException
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
