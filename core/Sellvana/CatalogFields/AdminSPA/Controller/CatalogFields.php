<?php

/**
 * Class Sellvana_CatalogFields_AdminSPA_Controller_CatalogFields
 *
 * @property FCom_Core_Model_Field FCom_Core_Model_Field
 */
class Sellvana_CatalogFields_AdminSPA_Controller_CatalogFields extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
	public function getGridConfig()
	{
		return [
			'id' => 'catalog_fields',
			'data_url' => 'catalogfields/grid_data',
			'columns' => [
				['type' => 'row-select'],
				['name' => 'id', 'label' => 'ID'],
				['name' => 'field_code', 'label' => 'Field Code', 'datacell_template' => '<td><a :href="\'#/catalog/fields/form?id=\'+row.id">{{row.field_code}}</a></td>'],
				['name' => 'field_name', 'label' => 'Field Name', 'datacell_template' => '<td><a :href="\'#/catalog/fields/form?id=\'+row.id">{{row.field_name}}</a></td>'],
			],
			'filters' => true,
			'export' => true,
			'pager' => true,
		];
	}

	public function getGridOrm()
	{
		return $this->FCom_Core_Model_Field->orm('f')->where('field_type', 'product');
	}

	public function getFormData()
	{
		$pId = $this->BRequest->get('id');
		$bool = [0 => 'no', 1 => 'Yes'];

		$field = $this->FCom_Core_Model_Field->load($pId);
		if (!$field) {
			throw new BException('Field not found');
		}

		$result = [];

		$result['form']['field'] = $field->as_array();

		$result['form']['config']['actions'] = true;
		$result['form']['config']['title'] = $field->get('field_name');

		$result['form']['config']['tabs'] = '/catalog/fields/form';
		$result['form']['config']['default_field'] = ['model' => 'field', 'tab' => 'main'];
		$result['form']['config']['fields'] = [
			[ 'required' => true, 'name' => 'field_code', 'label' => 'Field Code'],
			[ 'required' => true, 'name' => 'field_name', 'label' => 'Field Name'],
			[ 'required' => true, 'name' => 'frontend_label', 'label' => 'Frontend label'],
			[ 'required' => true, 'name' => 'frontend_show', 'label' => 'Show on frontend', 'type' => 'checkbox'],
			[ 'required' => true, 'name' => 'sort_order', 'label' => 'Sort order'],
			[ 'required' => true, 'name' => 'table_field_type', 'label' => 'DB Type'],
			[ 'required' => true, 'name' => 'admin_input_type', 'label' => 'Input Type'],
			[ 'required' => true, 'name' => 'multilanguage', 'label' => 'Multi Language'],
			[ 'required' => true, 'name' => 'swatch_type', 'label' => 'Swatch type'],
			[ 'required' => true, 'name' => 'required', 'label' => 'Required'],
		];

//		$result['form']['i18n'] = $this->getModelTranslations('field', $field->id());

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

			if ($data) {
				$model->set($data);
			}

			$origModelData = $modelData = $model->as_array();
			$validated = $model->validate($modelData, [], 'product');
			if ($modelData !== $origModelData) {
				var_dump($modelData);
				$model->set($modelData);
			}


			if ($validated) {
				$model->save();
				$result = $this->getFormData();
				$result['form'] = $this->normalizeFormConfig($result['form']);
				$this->ok()->addMessage('Inventory was saved successfully', 'success');
			} else {
				$result = ['status' => 'error'];
				$this->error()->addMessage('Cannot save data, please fix above errors', 'error');
			}

		} catch (Exception $e) {
			$this->addMessage($e);
		}
		$this->respond($result);
	}
}