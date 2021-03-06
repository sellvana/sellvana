<?php

/**
 * Class Sellvana_Blog_Admin_Controller_Category
 *
 * @property Sellvana_Blog_Model_PostCategory $Sellvana_Blog_Model_PostCategory
 * @property Sellvana_Blog_Model_Category $Sellvana_Blog_Model_Category
 */
class Sellvana_Blog_Admin_Controller_Category extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'blog/category';
    protected $_modelClass = 'Sellvana_Blog_Model_Category';
    protected $_gridTitle = (('Blog Categories'));
    protected $_recordName = (('Blog Category'));
    protected $_permission = 'blog';
    protected $_mainTableAlias = 'c';
    protected $_navPath = 'cms/category';
    protected $_formTitleField = 'name';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
                ['name' => 'delete'],
            ]],
            ['name' => 'id', 'label' => (('ID'))],
            ['name' => 'name', 'label' => (('Name'))],
            ['name' => 'description', 'label' => (('Description'))],
            ['name' => 'url_key', 'label' => (('URL Key'))],
            ['name' => 'post', 'label' => (('Posts')), 'href' => $this->BApp->href('blog/post/?category=')],
        ];
        if (!empty($config['orm'])) {
            if (is_string($config['orm'])) {
                $config['orm'] = $this->{$config['orm']}->orm($this->_mainTableAlias)->select($this->_mainTableAlias . '.*');
            }
            $this->gridOrmConfig($config['orm']);
        }
        $config['actions'] = [
            //'edit' => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'name', 'type' => 'text'],
        ];
        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->left_outer_join('Sellvana_Blog_Model_PostCategory', [$this->_mainTableAlias . '.id', '=', 'u.category_id'], 'u')
            ->group_by($this->_mainTableAlias . '.id')
            ->select_expr('COUNT(u.category_id)', 'post')
        ;
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        $cp = $this->Sellvana_Blog_Model_PostCategory;
        $model = $args['model'];
        $data = $this->BRequest->post();

        if (isset($data['grid']['post_category']['add'])) {
            $cp->delete_many(['category_id' => $model->id()]);

            $postAddIds = $data['grid']['post_category']['add'];

            if (!empty($postAddIds)) {
                $postAddIds = explode(',', $postAddIds);

                foreach ($postAddIds as $postId) {
                    $cp->create([
                        'post_id' => $postId,
                        'category_id' => $model->id(),
                    ])->save();
                }
            }
        }

        /*if (!empty($data['grid']['post_category']['del'])) {
            $cp->delete_many([
                'category_id' => $model->id(),
                'post_id' => explode(',', $data['grid']['post_category']['del']),
            ]);
        }
        if (!empty($data['grid']['post_category']['add'])) {
            $oldPost = $cp->orm()->where('category_id', $model->id)->where('post_id', $model->id)
                ->find_many_assoc('post_id');
            foreach (explode(',', $data['grid']['post_category']['add']) as $postId) {
                if ($postId && empty($oldPost[$postId])) {
                    $m = $cp->create([
                        'category_id' => $model->id(),
                        'post_id' => $postId,
                    ])->save();
                }
            }
        }*/
    }

    public function processFormTabs($view, $model = null, $mode = 'edit', $allowed = null)
    {
        if ($model && $model->id) {
            $view->addTab('post', ['label' => $this->_(('Blog Posts')), 'pos' => 20]);
        }
        return parent::processFormTabs($view, $model, $mode, $allowed);
    }

    public function action_category_tree()
    {
        $r = $this->BRequest->get();
        $categoryPosts = $this->Sellvana_Blog_Model_PostCategory->orm('p')
                    ->select('p.category_id')
                    ->join('Sellvana_Blog_Model_Post', ['p.post_id', '=', 'u.id'], 'u')
                    ->where('p.post_id', $r['post-id'])->find_many();
        $categories = $this->Sellvana_Blog_Model_Category->orm('c')->select('c.*')->find_many();
        $result = [];
        $arr_category_id = [];
        foreach ($categoryPosts as $arr) {
            $tmp = $arr->as_array();
            array_push($arr_category_id, $tmp['category_id']);
        }
        foreach ($categories as $arr) {
            $tmp = $arr->as_array();
            $attr = (in_array($tmp['id'], $arr_category_id)) ? ['id' => $tmp['id'], "class" => "jstree-checked"] : ['id' => $tmp['id']];
            $tem = [
                'data' => $tmp['name'],
                'attr' => $attr,
                'state' => null,
                'rel' => 'root',
                'position' => $tmp['id'],
                'children' => null
            ];
            array_push($result, $tem);
        }
        $this->BResponse->json($result);
    }
}
