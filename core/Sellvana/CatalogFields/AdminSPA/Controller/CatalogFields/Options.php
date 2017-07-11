<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductMedia Sellvana_Catalog_Model_ProductMedia
 * @property Sellvana_Catalog_Model_Category Sellvana_Catalog_Model_Category
 * @property FCom_Core_Model_FieldOption FCom_Core_Model_FieldOption
 */
class Sellvana_CatalogFields_AdminSPA_Controller_CatalogFields_Options extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
	public function getGridConfig()
	{
		$fieldId = $this->BRequest->get('id');
		return [
			'id' => 'field_options',
			'data_url' => 'catalogfields/form/options/grid_data?id=' . $fieldId,
			'columns' => [
				['type' => 'row-select', 'width' => 55],
				['type' => 'actions', 'actions' => [
					['type' => 'edit', 'link' => '/catalogfields/options/form?id={id}', 'icon_class' => 'fa fa-pencil'],
					['type' => 'delete', 'delete_url' => 'catalogfields/form/options/grid_delete?id={id}', 'icon_class' => 'fa fa-trash'],
				]],
				['name' => 'id', 'label' => 'ID', 'width' => 55, 'hidden' => true],
				['name' => 'label', 'label' => 'Label', 'width' => 100],
				['name' => 'locale', 'label' => 'Locale', 'width' => 100],
				['name' => 'swatch_info', 'label' => 'Swatch info', 'width' => 100],
			],
			'filters' => [
				['name' => 'id', 'type' => 'number'],
				['name' => 'label'],
				['name' => 'locale'],
				['name' => 'swatch_info'],
			],
			'export' => false,
			'pager' => true,
		];
	}

	public function getGridOrm()
	{
		$fieldId = $this->BRequest->get('id');
		return $this->FCom_Core_Model_FieldOption->orm('fo')
			->select('fo.*')
			->join('FCom_Core_Model_Field', ['fo.field_id', '=', 'f.id'], 'f')
			->where('fo.field_id', $fieldId);
	}

}