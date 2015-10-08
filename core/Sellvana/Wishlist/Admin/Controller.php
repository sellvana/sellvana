<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Wishlist_Admin_Controller
 *
 * @property Sellvana_Wishlist_Model_Wishlist $Sellvana_Wishlist_Model_Wishlist
 */
class Sellvana_Wishlist_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'wishlist';
    protected $_modelClass = 'Sellvana_Wishlist_Model_Wishlist';
    protected $_gridTitle = 'Wishlist';
    protected $_mainTableAlias = 'w';
    protected $_permission = 'wishlist';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        return $config;
    }

    /**
     * get grid config for wishlist of customer
     * @param $customer Sellvana_Customer_Model_Customer
     * @return array
     */
    public function customerWishlistGridConfig($customer)
    {
        $config = parent::gridConfig();
        $config['id'] = 'customer_grid_wishlist_' . $customer->id;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'title', 'label' => 'Title', 'editable' => true, 'addable' => true],
            ['name' => 'customer_name', 'label' => 'Customer'],
            ['name' => 'remote_ip', 'label' => 'IP'],
            ['name' => 'create_at', 'label' => 'Created'],
            ['name' => 'is_default', 'label' => 'Is Default', 'display' => 'eval',
                'print' => '"<input type=\'radio\' value=\'"+rc.row["id"]+"\' name=\'model[is_default]\' "+(rc.row["is_default"] == 1 ? checked=\'checked\' : \'\')+" />"', 'tdStyle' => ['textAlign' => 'center']
            ],
            ['type' => 'btn_group', 'buttons' => [
                [
                    'name' => 'custom',
                    'icon' => 'icon-edit-sign',
                    'cssClass' => 'btn-custom',
                    'callback' => 'showModalToEditWishlist'
                ]
            ]]
        ];

        $config['filters'] = [
            ['field' => 'title', 'type' => 'text'],
        ];

        $config['callbacks'] = [
            'componentDidMount' => 'wishlistGridRegister'
        ];

        $data = [];
        $wishlists = $this->Sellvana_Wishlist_Model_Wishlist->orm()->where('customer_id', $customer->id)->find_many();
        if (!empty($wishlists)) {
            foreach ($wishlists as $wishlist) {
                $item = $wishlist->as_array();
                $item['customer_name'] = $customer->lastname;
                $data[] = $item;
            }
        } else {
            $wishlists = [];
        }

        $config['data'] = $data;
        $config['data_mode'] = 'local';
        unset($config['orm']);
        return ['config' => $config];
    }

    /**
     * get grid config for wishlist items of customer
     * @param $customer Sellvana_Customer_Model_Customer
     * @return array
     */
    public function customerWishlistItemsGridConfig($customer)
    {
        $config = parent::gridConfig();
        $config['id'] = 'customer_grid_wishlist_items_' . $customer->id;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'wishlist_title', 'label' => 'Wishlist'],
            ['name' => 'product_name', 'label' => 'Product Name'],
            ['name' => 'product_sku', 'label' => 'SKU'],
            ['name' => 'base_price', 'label' => 'Base Price'],
            ['name' => 'sale_price', 'label' => 'Sale Price'],
            ['type' => 'btn_group', 'buttons' => [
                    ['name' => 'edit-custom', 'callback' => 'showModalToEditWishlistItems', 'cssClass' => " btn-xs btn-edit ", 'textValue' => 'Edit Wishlist Items', "icon" => " icon-edit-sign", 'attrs' => ['data-toggle' => 'tooltip', 'title' => 'Update Wishlist', 'data-placement' => 'top']]
                ]
            ]
        ];

        $config['filters'] = [
            ['field' => 'wishlist_title', 'type' => 'text'],
            ['field' => 'product_name', 'type' => 'text'],
            ['field' => 'product_sku', 'type' => 'text'],
            ['field' => 'base_price', 'type' => 'number-range'],
            ['field' => 'sale_price', 'type' => 'number-range']
        ];

        $config['callbacks'] = [
            'componentDidMount' => 'wishlistItemsGridRegister'
        ];

        $data = [];
        /** @var Sellvana_Wishlist_Model_Wishlist[] $wishlistArr */
        $wishlistArr = $this->Sellvana_Wishlist_Model_Wishlist->orm()->where('customer_id', $customer->id)->find_many();
        if ($wishlistArr) {
            foreach ($wishlistArr as $wishlist) {
                $wishlists[] = ['id' => $wishlist->id(), 'text' => $wishlist->title];
                /** @var Sellvana_Wishlist_Model_WishlistItem[] $items */
                $items = $wishlist->items();
                if ($items) {
                    foreach ($items as $item) {
                        $arr = $item->getProduct()->as_array();
                        $arr['wishlist_id'] = $wishlist->id;
                        $arr['wishlist_title'] = $wishlist->title;
                        $data[] = $arr;
                    }
                }
            }
        }

        $config['data'] = $data;
        $config['wishlists'] = $this->BUtil->toJson($wishlists);
        $config['data_mode'] = 'local';
        unset($config['orm']);
        return ['config' => $config];
    }

    public function action_index__POST()
    {
        $post = $this->BRequest->post();
        switch ($post['oper']) {
            case 'edit':
                $wishlist = $this->Sellvana_Wishlist_Model_Wishlist->load($post['id']);
                if ($wishlist->set('title', $post['title'])->save()) {
                    $this->BResponse->json(['success' => true, 'title' => $wishlist->title]);
                }
                break;
            
            default:
                # code...
                break;
        }
    }

    /**
     * Move product to other wishlist
     * 
     * @return Json
     */
    public function action_move()
    {
        if ($this->BRequest->csrf('referrer', 'GET')) {
            $this->message('CSRF detected', 'error');
            $this->BResponse->redirect('wishlist');
            return;
        }

        $id           = $this->BRequest->get('id');
        $pId          = (int)$this->BRequest->get('product');
        $wlId         = (int)$this->BRequest->get('wishlist');
        $wishlistItem = $this->Sellvana_Wishlist_Model_WishlistItem->loadOrCreate(['wishlist_id' => $wlId, 'product_id' => $pId]);

        if ($this->BRequest->xhr() && $wishlistItem) {
            if ($wishlistItem->set('wishlist_id', $id)->save()) {
                $wishlist = $this->Sellvana_Wishlist_Model_Wishlist->load($id);
                $r = [
                    'success' => true,
                    'wishlist_id' => $wishlist->id,
                    'wishlist_title' => $wishlist->title
                ];
            }
            
            $this->BResponse->json($r);
        }
    }
}
