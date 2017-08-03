<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductMedia Sellvana_Catalog_Model_ProductMedia
 * @property Sellvana_Catalog_Model_Category Sellvana_Catalog_Model_Category
 * @property FCom_Core_Model_FieldOption FCom_Core_Model_FieldOption
 */
class Sellvana_CatalogFields_AdminSPA_Controller_CatalogFields_Options
    extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
	public function getGridConfig()
	{
		$fieldId = $this->BRequest->get('id');
		return [
			static::ID => 'field_options',
			static::DATA_URL => 'catalogfields/options/grid_data?id=' . $fieldId,
			static::COLUMNS => [
				[static::TYPE => static::ROW_SELECT, static::WIDTH => 55],
				[static::TYPE => 'actions', static::ACTIONS => [
					[static::TYPE => 'edit', static::LINK => '/catalogfields/options/form?id={id}', 'icon_class' => 'fa fa-pencil'],
					[static::TYPE => 'delete', 'delete_url' => 'catalogfields/options/grid_delete?id={id}', 'icon_class' => 'fa fa-trash'],
				]],
				[static::NAME => 'id', static::LABEL => (('ID')), static::WIDTH => 55, static::HIDDEN => true],
				[static::NAME => 'label', static::LABEL => (('Label')), static::WIDTH => 100],
				[static::NAME => 'locale', static::LABEL => (('Locale')), static::WIDTH => 100],
				[static::NAME => 'swatch_info', static::LABEL => (('Swatch info')), static::WIDTH => 100],
			],
			static::FILTERS => [
				[static::NAME => 'id', static::TYPE => 'number'],
				[static::NAME => 'label'],
				[static::NAME => 'locale'],
				[static::NAME => 'swatch_info'],
			],
			static::EXPORT => false,
			static::PAGER => true,
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