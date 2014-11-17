<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Admin_Controller_OrderStatus
 *
 * @property FCom_Sales_Model_Order_StateCustom $FCom_Sales_Model_Order_StateCustom
 */

class FCom_Sales_Admin_Controller_OrderStatus extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'orderstatus';
    protected $_modelClass = 'FCom_Sales_Model_Order_CustomStatus';
    protected $_gridTitle = 'Orders Status';
    protected $_recordName = 'Order status';
    protected $_mainTableAlias = 'os';
    protected $_permission = 'sales/order_status';
    protected $_navPath = 'sales/orderstatus';
    protected $_formViewPrefix = 'order/orderstatus-form/';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'index' => 'o.id', 'label' => 'ID', 'width' => 70],
            ['name' => 'code', 'index' => 'code', 'label' => 'Code', 'addable' => true, 'editable' => true,
                'validation' => ['required' => true, 'unique' => $this->BApp->href('orderstatus/unique')]],
            ['name' => 'name', 'index' => 'name', 'label' => 'Label', 'addable' => true, 'editable' => true,
                'validation' => ['required' => true, /*'unique' => $this->BApp->href('orderstatus/unique')*/]],
            ['type' => 'btn_group', 'buttons' => [['name' => 'edit'], ['name' => 'delete']]]
        ];
        $config['actions'] = [
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'code', 'type' => 'text'],
        ];
        $config['new_button'] = '#add_new_order_status';
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(['actions' => [
            'new' => '<button type="button" id="add_new_order_status" class="btn grid-new btn-primary _modal">'
                . $this->BLocale->_('Add New Order Status') . '</button>']]);
    }

    /**
     * ajax check code is unique
     */
    public function action_unique__POST()
    {
        try {
            $post = $this->BRequest->post();
            $data = each($post);
            if (!isset($data['key']) || !isset($data['value'])) {
                throw new BException('Invalid post data');
            }
            $key = $this->BDb->sanitizeFieldName($data['key']);
            $value = $data['value'];
            $exists = $this->FCom_Sales_Model_Order_StateCustom->load($value, $key);
            $result = ['unique' => !$exists, 'id' => !$exists ? -1 : $exists->id()];
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage()];
        }
        $this->BResponse->json($result);
    }
}
