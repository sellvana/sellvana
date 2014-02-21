<?php

class FCom_Blog_Admin_Controller_Category extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'blog/category';
    protected $_modelClass = 'FCom_Blog_Model_Category';
    protected $_gridTitle = 'Blog Categories';
    protected $_recordName = 'Blog Category';
    protected $_permission = 'blog';
    protected $_mainTableAlias = 'c';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label' => 'ID'),
            array('name' => 'name', 'label'=>'Name'),
            array('name' => 'description', 'label'=>'Description'),
            array('name' => 'url_key', 'label'=>'URL Key'),
            array('name' => 'post', 'label'=>'Posts', 'href' => BApp::href('blog/post/?category=')),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false,
                'data' => array('edit' => array('href' => BApp::href('blog/category/form/?id='), 'col'=>'id'),'delete' => true)),
        );
        if (!empty($config['orm'])) {
            if (is_string($config['orm'])) {
                $config['orm'] = $config['orm']::i()->orm($this->_mainTableAlias)->select($this->_mainTableAlias.'.*');
            }
            $this->gridOrmConfig($config['orm']);
        }
        $config['actions'] = array(
            //'edit' => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'name', 'type' => 'text'),
        );
        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->left_outer_join('FCom_Blog_Model_PostCategory', array($this->_mainTableAlias.'.id', '=', 'u.category_id'), 'u')
            ->group_by($this->_mainTableAlias.'.id')
            ->select_expr('COUNT(u.category_id)', 'post')
        ;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
                'title' => $m->id ? 'Edit Blog Category: '.$m->title : 'Create New Blog Category',
            ));
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        $cp = FCom_Blog_Model_PostCategory::i();
        $model = $args['model'];
        $data = BRequest::i()->post();
        if (!empty($data['grid']['post_category']['del'])) {
            $cp->delete_many(array(
                    'category_id' => $model->id,
                    'post_id'=>explode(',', $data['grid']['post_category']['del']),
                ));
        }
        if (!empty($data['grid']['post_category']['add'])) {
            $oldPost = $cp->orm()->where('category_id', $model->id)->where('post_id', $model->id)
                ->find_many_assoc('post_id');
            foreach (explode(',', $data['grid']['post_category']['add']) as $postId) {
                if ($postId && empty($oldPost[$postId])) {
                    $m = $cp->create(array(
                            'category_id'=>$model->id,
                            'post_id'=>$postId,
                        ))->save();
                }
            }
        }
    }

    public function processFormTabs($view, $model = null, $mode = 'edit', $allowed = null)
    {
        if ($model && $model->id) {
            $view->addTab('post', array('label' => $this->_('Blog Posts'), 'pos' => 20));
        }
        return parent::processFormTabs($view, $model, $mode, $allowed);
    }

    public function action_category_tree()
    {
        $r = BRequest::i()->get();
        $categoryPosts = FCom_Blog_Model_PostCategory::i()->orm('p')
                    ->select('p.category_id')
                    ->join('FCom_Blog_Model_Post', array('p.post_id', '=', 'u.id'), 'u')
                    ->where('p.post_id', $r['post-id'])->find_many();
        $categories = FCom_Blog_Model_Category::i()->orm('c')->select('c.*')->find_many();
        $result = array();
        $arr_category_id = array();
        foreach ($categoryPosts as $arr) {
            $tmp = $arr->as_array();
            array_push($arr_category_id, $tmp['category_id']);
        }
        foreach ($categories as $arr) {
            $tmp = $arr->as_array();
            $attr = (in_array($tmp['id'], $arr_category_id)) ? array('id' => $tmp['id'], "class" => "jstree-checked") : array('id' => $tmp['id']);
            $tem = array(
                'data' => $tmp['name'],
                'attr' => $attr,
                'state' => null,
                'rel' => 'root',
                'position' => $tmp['id'],
                'children' => null
            );
            array_push($result, $tem);
        }
        BResponse::i()->json($result);
    }
}
