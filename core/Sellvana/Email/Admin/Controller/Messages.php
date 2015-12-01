<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Email_Admin_Controller_Messages
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Email_Model_Message $Sellvana_Email_Model_Message
 */
class Sellvana_Email_Admin_Controller_Messages extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'messages';
    protected $_modelClass = 'Sellvana_Email_Model_Message';
    protected $_gridTitle = 'Messages';
    protected $_recordName = 'Message';
    protected $_mainTableAlias = 'm';

    public function messagesGridConfig($customer)
    {
        $config = parent::gridConfig();
        unset($config['form_url']);
        $config['id'] = 'customer_addresses_grid_' . $customer->id;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'view_name', 'label' => 'View Name'],
            ['name' => 'recipient', 'label' => 'Recipient'],
            ['name' => 'subject', 'label' => 'Subject'],
            ['name' => 'status', 'label' => 'Status'],
            ['name' => 'num_attemps', 'label' => 'Number Attemps'],
            ['name' => 'create_at', 'label' => 'Created'],
            ['type' => 'btn_group', 'buttons' => [['name' => 'delete']]],
        ];
        $config['filters'] = [
            ['field' => 'view_name', 'type' => 'text'],
            ['field' => 'recipient', 'type' => 'text'],
        ];
        $config['callbacks'] = [
            'componentDidMount' => 'messagesGridRegister'
        ];
        $config['actions'] = ['delete' => true];
        // $config['data_url'] = $config['data_url'] . '?customer_id=' . $customer->id;
        $orm = $this->Sellvana_Email_Model_Message->orm($this->_mainTableAlias)->select($this->_mainTableAlias . '.*');
        $config['orm'] = $orm;

        return ['config' => $config];
    }

    /*public function gridOrmConfig($orm) {
        parent::gridOrmConfig($orm);
        if ($this->BRequest->get('customer_id')) {
            $orm->where('customer_id', $this->BRequest->get('customer_id'));
        }
    }*/
}
