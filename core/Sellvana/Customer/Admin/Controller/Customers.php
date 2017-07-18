<?php

/**
 * Class Sellvana_Customer_Admin_Controller_Customers
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Customer_Model_Address $Sellvana_Customer_Model_Address
 * @property Sellvana_CustomerGroups_Model_Group $Sellvana_CustomerGroups_Model_Group
 * @property Sellvana_Wishlist_Model_Wishlist $Sellvana_Wishlist_Model_Wishlist
 * @property FCom_Core_Main $FCom_Core_Main
 * @property FCom_Admin_Main $FCom_Admin_Main
 */
class Sellvana_Customer_Admin_Controller_Customers extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'customers';
    protected $_modelClass = 'Sellvana_Customer_Model_Customer';
    protected $_gridTitle = (('Customers'));
    protected $_recordName = (('Customer'));
    protected $_mainTableAlias = 'c';
    protected $_permission = 'customers/manage';
    protected $_navPath = 'customer/customers';
    protected $_formViewPrefix = 'customer/customers-form/';
    protected $_formTitleField = 'Sellvana_Customer_Admin_Controller_Customers.formTitleField';
    protected $_formLayoutName = '/customers/form';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
                ['name' => 'login', 'icon' => 'icon-user', 'href' => $this->BApp->href('customers/start_session?id='),
                    'title' => $this->_(('Log in as customer')), 'target' => (('AdminCustomer'))],
                ['name' => 'delete'],
            ]],
            ['name' => 'id', 'label' => (('ID')), 'index' => 'c.id'],
            ['name' => 'firstname', 'label' => (('First Name')), 'index' => 'c.firstname'],
            ['name' => 'lastname', 'label' => (('Last Name')), 'index' => 'c.lastname'],
            ['name' => 'email', 'label' => (('Email')), 'index' => 'c.email'],
            ['type' => 'input', 'name' => 'customer_group', 'label' => (('Customer Group')), 'index' => 'c.customer_group',
                  'editor' => 'select', 'options' => $this->Sellvana_CustomerGroups_Model_Group->groupsOptions(),
                  'editable' => true, 'multirow_edit' => true, 'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'status', 'label' => (('Status')), 'index' => 'c.status', 'editor' => 'select',
                  'options' => $this->Sellvana_Customer_Model_Customer->fieldOptions('status'),
                  'editable' => true, 'multirow_edit' => true, 'validation' => ['required' => true]],
            ['name' => 'street1', 'label' => (('Address')), 'index' => 'a.street1'],
            ['name' => 'city', 'label' => (('City')), 'index' => 'a.city', 'hidden' => true],
            ['name' => 'region', 'label' => (('Region')), 'index' => 'a.region', 'hidden' => true],
            ['name' => 'postcode', 'label' => (('Postal Code')), 'index' => 'a.postcode', 'hidden' => true],
            ['type' => 'input', 'name' => 'country', 'label' => (('Country')), 'index' => 'a.country', 'editor' => 'select', 'hidden' => true,
                    'options' => $this->BLocale->getAvailableCountries()],
            ['name' => 'create_at', 'label' => (('Created')), 'index' => 'c.create_at', 'cell' => 'datetime'],
            /*array('name' => 'update_at', 'label'=>'Updated', 'index'=>'c.update_at'),*/
            ['name' => 'last_login', 'label' => (('Last Login')), 'index' => 'c.last_login', 'hidden' => true, 'cell' => 'datetime'],
        ];
        $config['actions'] = [
            'export' => true,
            'edit'   => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'firstname', 'type' => 'text'],
            ['field' => 'lastname', 'type' => 'text'],
            ['field' => 'email', 'type' => 'text'],
            ['field' => 'customer_group', 'type' => 'multiselect'],
            ['field' => 'street1', 'type' => 'text'],
            ['field' => 'city', 'type' => 'text'],
            ['field' => 'region', 'type' => 'text'],
            ['field' => 'postcode', 'type' => 'text'],
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'last_login', 'type' => 'date-range'],
            ['field' => 'country', 'type' => 'multiselect'],
            ['field' => 'status', 'type' => 'multiselect'],
        ];
        //$config['custom']['dblClickHref'] = $this->BApp->href('customers/form/?id=');
        //todo: check this in FCom_Admin_Controller_Abstract_GridForm
        if (!empty($config['orm'])) {
            if (is_string($config['orm'])) {
                $config['orm'] = $config['orm']::i()->orm($this->_mainTableAlias)->select($this->_mainTableAlias . '.*');
            }
            $this->gridOrmConfig($config['orm']);
        }
        $config['grid_before_create'] = 'customerGridRegister';
        $config['callbacks'] = ['componentDidMount' => 'customerGridRegister'];
        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->left_outer_join('Sellvana_Customer_Model_Address', ['a.id', '=', 'c.default_billing_id'], 'a')
            ->left_outer_join('Sellvana_CustomerGroups_Model_Group', ['cg.id', '=', 'c.customer_group'], 'cg')
            ->select(['a.street1', 'a.city', 'a.region', 'a.postcode', 'a.country'])
            ->select(['cg.title'])
        ;
    }

    public function formTitleField($m)
    {
        return $m->get('firstname') . ' ' . $m->get('lastname');
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        /** @var $m Sellvana_Customer_Model_Customer */
        $m = $args['model'];
        $actions = $args['view']->get('actions');
        if ($m->id()) {
            $actions['create-order'] = [
                'a',
                [
                    'class' => ['btn', 'btn-default'],
                    'title' => $this->_(('Redirect to frontend and create order')),
                    'href' => $this->BApp->href('customers/start_session?id=' . $m->id()),
                ],
                [
                    ['span', null, $this->_(('Log in as Customer'))],
                ]
            ];
        }
        if ($m->id()) {
            $saleStatistics = $m->saleStatistics();
            $info = $this->_(('Lifetime Sales')) . ' ' . $this->BLocale->currency($saleStatistics['lifetime'], 'base')
                . ' | ' . $this->_(('Avg. Sales')) . ' ' . $this->BLocale->currency($saleStatistics['avg'], 'base');
        } else {
            $info = '';
        }
        if ($this->BConfig->get('modules/Sellvana_Customer/use_gravatar')) {
            $img = $this->BUtil->gravatar($m->email);
        } else {
            $media = $this->BConfig->get('web/media_dir') ?: 'media';
            $img = $this->FCom_Core_Main->resizeUrl($media . '/silhouette.jpg', ['s' => 98]);
        }
        $args['view']->set([
            'sidebar_img' => $img,
            'other_info' => $info,
            'actions' => $actions,
        ]);
    }

    /**
    * Not used currently
    *
    * @param array $args
    */
    public function formPostAfter($args)
    {
        parent::formPostAfter($args);

        $customer = $args['model'];
        $hlp = $this->Sellvana_Customer_Model_Address;

        if ($args['do'] !== 'DELETE') {
            $addrPost = $this->BRequest->post('address');
            if (($newData = $this->BUtil->fromJson($addrPost['data_json']))) {
                $oldModels = $hlp->orm('a')->where('customer_id', $customer->id)->find_many_assoc();
                foreach ($newData as $id => $data) {
                    if (empty($data['id'])) {
                        continue;
                    }
                    if (!empty($oldModels[$data['id']])) {
                        $addr = $oldModels[$data['id']];
                        $addr->set($data)->save();
                    } elseif ($data['id'] < 0) {
                        unset($data['id']);
                        $hlp->newBilling($data, $customer);
                    }
                }
            }
            if (($del = $this->BUtil->fromJson($addrPost['del_json']))) {
                $hlp->delete_many(['id' => $del, 'customer_id' => $customer->id]);
            }

            //set default billing / shipping from addressed grid
            $data = $args['data'];
            if (!empty($data['default_billing_id'])) {
                $address = $hlp->load($data['default_billing_id']);
                /** @type Sellvana_Customer_Model_Address $address */
                if ($address->is_default_billing == 0 && $address->customer_id == $customer->id) {
                    $hlp->update_many(['is_default_billing' => 0], ['customer_id' => $customer->id]);
                    $address->set('is_default_billing', 1)->save();
                }
            }
            if (!empty($data['default_shipping_id'])) {
                $address = $hlp->load($data['default_shipping_id']);
                /** @type Sellvana_Customer_Model_Address $address */
                if ($address->is_default_shipping == 0 && $address->customer_id == $customer->id) {
                    $hlp->update_many(['is_default_shipping' => 0], ['customer_id' => $customer->id]);
                    $address->set('is_default_shipping', 1)->save();
                }
            }

            if (!empty($data['is_default'])) {
                $wishlists = $this->Sellvana_Wishlist_Model_Wishlist->orm()->where('customer_id', $customer->id)->find_many();
                foreach ($wishlists as $wishlist) {
                    $wishlist->set('is_default', $wishlist->id() == $data['is_default'])->save();
                }
            }
        }
    }

    /**
     * get config for grid: all customer
     * @param $group Sellvana_CustomerGroups_Model_Group
     * @return array
     */
    public function getAllCustomersConfig($group)
    {
        $config            = parent::gridConfig();
        $config['id']      = 'group_all_customers_grid_' . $group->id;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => (('ID')), 'index' => 'c.id', 'width' => 80, 'hidden' => true],
            ['name' => 'firstname', 'label' => (('Firstname')), 'index' => 'c.username', 'width' => 200],
            ['name' => 'lastname', 'label' => (('Lastname')), 'index' => 'c.username', 'width' => 200],
            ['name' => 'email', 'label' => (('Email')), 'index' => 'c.email', 'width' => 200],
        ];
        $config['actions'] = [
            'add' => ['caption' => (('Add selected customers'))]
        ];
        $config['filters'] = [
            ['field' => 'firstname', 'type' => 'text'],
            ['field' => 'lastname', 'type' => 'text'],
            ['field' => 'email', 'type' => 'text'],
            '_quick' => ['expr' => 'firstname like ? or lastname like ? or email like ? or c.id=?', 'args' => ['?%', '%?%', '?']]
        ];

        return ['config' => $config];
    }

    public function action_create_order()
    {
        $id = $this->BRequest->param('id', true);
        $r = $this->BRequest;
        $baseSrc = $this->BConfig->get('web/base_src');
        $redirectUrl = $r->scheme() . '://' . $r->httpHost() . $baseSrc;
        try {
            $model = $this->Sellvana_Customer_Model_Customer->load($id);
            if (!$model) {
                $this->message('Cannot load this customer model', 'error');
                $redirectUrl = $this->BApp->href($this->_formHref) . '?id=' . $id;
            } else {
                $model->login();
            }
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
            $redirectUrl = $this->BApp->href($this->_formHref) . '?id=' . $id;
        }

        $this->BResponse->redirect($redirectUrl);
    }

    public function onHeaderSearch($args)
    {
        $r = $this->BRequest->get();
        if (isset($r['q']) && $r['q'] != '') {
            $value = '%' . $r['q'] . '%';
            $result = $this->Sellvana_Customer_Model_Customer->orm()
                ->where(['OR' => [
                    ['id like ?', (int)$value],
                    ['firstname like ?', (string)$value],
                    ['lastname like ?', (string)$value],
                    ['email like ?', (string)$value],
                ]])->find_one();
            $args['result']['customer'] = null;
            if ($result) {
                $args['result']['customer'] = [
                    'priority' => 10,
                    'url' => $this->BApp->href($this->_formHref) . '?id=' . $result->id()
                ];
            }
        }
    }

    public function action_start_session()
    {
        $sess = $this->BSession;
        $resp = $this->BResponse;

        $cId = $this->BRequest->get('id');
        $customer = $this->Sellvana_Customer_Model_Customer->load($cId);

        if (!$customer) {
            $sess->addMessage('Invalid customer ID: ' . $cId, 'error');
            $resp->redirect('customers');
            return;
        }

        $customer->login(false);
        $sess->set('admin_customer_id', $cId);
        $resp->cookie('cart', false);
        /*
        $sessId = $sess->sessionId();
        $sess->open($customer->get('last_session_id'), false, false);
        //...
        $sess->close();
        $sess->open($sessId);
        */
        $resp->redirect($this->FCom_Admin_Main->frontendHref());
    }
}
