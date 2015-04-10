<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_SalesTax_Admin_Controller_ProductClasses
 *
 * @property Sellvana_SalesTax_Model_ProductClass $Sellvana_SalesTax_Model_ProductClass
 */
class Sellvana_SalesTax_Admin_Controller_ProductClasses extends FCom_Admin_Controller_Abstract_GridForm {
	protected static $_origClass = __CLASS__;

	protected $_gridHref = 'salestax/product-classes';
	protected $_modelClass = 'Sellvana_SalesTax_Model_ProductClass';
	protected $_gridTitle = 'Product Tax Classes';
	protected $_recordName = 'Product Tax Class';
	protected $_mainTableAlias = 'tp';
	protected $_navPath = 'sales/tax/product-classes';
	protected $_permission = 'sales/tax/product_classes';

	protected $_gridPageViewName = 'admin/griddle';
	protected $_gridViewName = 'core/griddle';
	protected $_defaultGridLayoutName = 'default_griddle';

	public function gridConfig() {
		$config = parent::gridConfig();
		unset($config['form_url']);
		$config['id'] = 'product-class';
		$config['caption'] = 'Product Class';
		$config['columns'] = [
			['type' => 'row_select'],
			['name' => 'id', 'label' => 'ID', 'width' => 50],
			['type' => 'input', 'name' => 'title', 'label' => 'Title', 'width' => 300,
				'editable' => true, 'addable' => true,
				'validation' => ['required' => true, 'unique' => $this->BApp->href('salestax/product_classes/unique')]],
			['type' => 'btn_group', 'buttons' => [['name' => 'edit'], ['name' => 'delete']]],
		];
		$config['actions'] = [
			'new' => array('caption' => 'Add New Product Tax Class', 'modal' => true),
			'edit' => true,
			'delete' => true,
		];
		$config['filters'] = [
			['field' => 'title', 'type' => 'text'],
		];
		$config['new_button'] = '#add_new_product_class';
		return $config;
	}

	public function gridViewBefore($args) {
		parent::gridViewBefore($args);
		$this->view('admin/grid')->set(['actions' => [
			'new' => '<button type="button" id="add_new_product_class" class="btn grid-new btn-primary _modal">'
			. $this->BLocale->_('Add New Product Tax Class') . '</button>']]);
	}

	public function formViewBefore($args) {
		parent::formViewBefore($args);
		$m = $args['model'];
		$title = $m->id ? 'Edit Product Tax Class: ' . $m->title : 'Create New Product Tax Class';
		$this->addTitle($title);
		$args['view']->set(['title' => $title]);
	}

	public function addTitle($title = '') {
		/* @var $v BViewHead */
		$v = $this->view('head');
		if ($v) {
			$v->addTitle($title);
		}
	}

	public function action_unique__POST() {
		$post = $this->BRequest->post();
		$data = each($post);
		$rows = $this->BDb->many_as_array($this->Sellvana_SalesTax_Model_ProductClass->orm()
			                                       ->where($data['key'], $data['value'])->find_many());
		$this->BResponse->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
	}
}
