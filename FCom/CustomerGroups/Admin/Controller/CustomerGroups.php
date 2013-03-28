<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Admin_Controller_CustomerGroups
    extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;

    protected $_gridHref = 'customer-groups';
    protected $_modelClass = 'FCom_CustomerGroups_Model_Group';
    protected $_gridTitle = 'Customer Groups';
    protected $_recordName = 'Customer Group';
    protected $_mainTableAlias = 'cg';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['grid']['columns'] = array_replace_recursive($config['grid']['columns'],
            array(
                 'id'    => array('index' => 'cg.id'),
                 'title' => array('label' => 'Title', 'index' => 'cg.title'),
                 'code'  => array('label' => 'Code', 'index' => 'cg.code'),
            )
        );
        $config['custom']['dblClickHref'] = BApp::href('customer-groups/form/?id=');
        return $config;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $title = $m->id ? 'Edit Customer Group: '.$m->title : 'Create New Customer Group';
        $this->addTitle($title);
        $args['view']->set(array(
                                'title' => $title,
                           ));
    }
    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        if ($args['do']!=='DELETE') {
            $customerGroup = $args['model'];
            $addrPost = BRequest::i()->post('address');
            if (($newData = BUtil::fromJson($addrPost['data_json']))) {
                $oldModels = FCom_CustomerGroups_Model_Group::i()->orm('a')->where('customer_id', $customerGroup->id)->find_many_assoc();
                foreach ($newData as $id=>$data) {
                    if (empty($data['id'])) {
                        continue;
                    }
                    if (!empty($oldModels[$data['id']])) {
                        $addr = $oldModels[$data['id']];
                        $addr->set($data)->save();
                    } elseif ($data['id']<0) {
                        unset($data['id']);
                        $addr = FCom_Customer_Model_Address::i()->newBilling($data, $cust);
                    }
                }
            }
            if (($del = BUtil::fromJson($addrPost['del_json']))) {
                FCom_Customer_Model_Address::i()->delete_many(array('id'=>$del, 'customer_id'=>$customerGroup->id));
            }
        }
    }

    public function action_index()
    {
        $this->addTitle($this->_gridTitle);
        parent::action_index();
    }

    public function addTitle($title = '')
    {
        /* @var $v BViewHead */
        $v = $this->view('head');
        if ($v) {
            $v->addTitle($title);
        }
    }
}