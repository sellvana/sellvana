<?php

class FCom_Blog_Admin_Controller_Post extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'blog/post';
    protected $_modelClass = 'FCom_Blog_Model_Post';
    protected $_gridTitle = 'Blog Posts';
    protected $_formHref = 'blog/post/form';
    protected $_recordName = 'Blog Post';
    protected $_permission = 'blog';
    protected $_mainTableAlias = 'p';
    protected $_navPath = 'cms/blog';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = array(
            array('type' => 'row_select'),
            array('name' => 'id', 'label' => 'ID'),
            array('name' => 'author', 'label'=>'Author'),
            array('type' => 'input', 'name' => 'status', 'label' => 'Status', 'edit_inline' => false, 'editable' => true, 'mass-editable' => true, 'editor' => 'select','mass-editable-show' => true,
                  'options' => FCom_Blog_Model_Post::i()->fieldOptions('status'), 'index' => $this->_mainTableAlias.'.status'),
            array('type'=>'input', 'name' => 'title', 'label'=>'Title','editable' => true, 'edit_inline' => true, 'validation' => array('required' => true)
//                'href' => BApp::href('blog/post/form/?id=:id')
            ),
            array('name' => 'url_key', 'label'=>'Url Key', 'hidden' => true),
            array('name' => 'meta_title', 'label'=>'Meta Title', 'hidden' => true),
            array('name' => 'meta_description', 'label'=>'Meta Description', 'hidden' => true),
            array('name' => 'meta_keywords', 'label'=>'Meta Keywords', 'hidden' => true),
            array('name' => 'create_ym', 'label'=>'Create ym' , 'hidden' => true),
            array('name' => 'create_at', 'label'=>'Created', 'cell'=>'date'),
            array('name' => 'update_at', 'label'=>'Updated', 'cell'=>'date'),
            array('type' => 'btn_group',
                'buttons' => array(
                    array('name' => 'edit', 'href' => BApp::href($this->_formHref.'?id='), 'col'=>'id'),
                    array('name' => 'delete' , 'edit_inline' => false)
                )
            )
        );
        if (!empty($config['orm'])) {
            if (is_string($config['orm'])) {
                $config['orm'] = $config['orm']::i()->orm($this->_mainTableAlias)->select($this->_mainTableAlias.'.*');
            }
            $this->gridOrmConfig($config['orm']);
        }
        $config['actions'] = array(
            'edit' => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'title', 'type' => 'text'),
            array('field' => 'status', 'type' => 'multiselect'),
        );

        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);
        $r = BRequest::i()->get();
        $orm->join('FCom_Admin_Model_User', array('p.author_user_id', '=', 'u.id'), 'u')
            ->select_expr('CONCAT_WS(" ", u.firstname,u.lastname)', 'author');
        if (!BRequest::i()->xhr()) {
            BSession::i()->pop('categoryBlogPost');
        }
        if (isset($r['category'])) {//@todo: find other solution instead use session?
            BSession::i()->set('categoryBlogPost', $r['category']);
        }
        if (BSession::i()->get('categoryBlogPost')) {
            $orm->join('FCom_Blog_Model_PostCategory', array($this->_mainTableAlias.'.id', '=', 'c.post_id'), 'c')
                ->where('c.category_id', BSession::i()->get('categoryBlogPost'))
            ;
        }
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'title' => $m->id ? 'Edit Blog Post: '.$m->title : 'Create New Blog Post',
        ));
        $tagOptions = FCom_Blog_Model_Tag::i()->orm()->order_by_asc('tag_name')
            ->select('tag_key', 'id')->select('tag_name', 'name')->find_many();
        $tagOptionsJson = BUtil::toJson(BDb::many_as_array($tagOptions));
        $this->view('blog/post-form/main')->set('tag_options_json', $tagOptionsJson);
    }

    /**
     * modal grid on blog/post tab
     */
    public function getAllPostConfig()
    {

        $config = parent::gridConfig();
        //$config['id'] = 'category_all_prods_grid-'.$model->id;
        $config['id'] = 'category_all_post_grid';
        $config['columns'] = array(
            array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>40),
            array('name'=>'id', 'label'=>'ID', 'index'=>'p.id', 'width'=>55),
            array('name' => 'author', 'label'=>'Author'),
            array('name' => 'title', 'label'=>'Title', 'href' => BApp::href('blog/post/form/?id=:id')),
            array('name' => 'status', 'label' => 'Status', 'editable' => true, 'mass-editable' => true, 'editor' => 'select',
                'options' => FCom_Blog_Model_Post::i()->fieldOptions('status')),
        );
        $config['actions'] = array(
            'add' => array('caption'=>'Add selected posts', 'modal' => true)
        );
        $config['filters'] = array(
            array('field' => 'title', 'type' => 'text'),
            array('field' => 'status', 'type' => 'multiselect'),
        );
        $config['orm'] = FCom_Blog_Model_Post::i()->orm('p')
            ->select('p.*')
            ->join('FCom_Admin_Model_User', array('p.author_user_id', '=', 'u.id'), 'u')
            ->select_expr('CONCAT_WS(" ", u.firstname,u.lastname)', 'author');
        $config['events'] = array('add');
        /*$config['_callbacks'] = "{
            'add':'categoryProdsMng.addSelectedProds'
        }";*/


        return array('config' =>$config);
    }

    public function postGridConfig($model)
    {
        $orm = FCom_Blog_Model_Post::i()->orm()->table_alias('p')
            ->select(array('p.id', 'p.author_user_id', 'p.status', 'p.title'));
        $orm->join('FCom_Admin_Model_User', array('p.author_user_id','=','u.id'), 'u')
            ->select_expr('CONCAT_WS(" ", u.firstname,u.lastname)', 'author');
        $orm->join('FCom_Blog_Model_PostCategory', array('p.id','=','cp.post_id'), 'cp')
            ->where('category_id', $model->id);
        $config = array(
            'id'           => 'post_category',
            'data'         =>null,
            'data_mode'     =>'local',
            //'caption'      =>$caption,
            'columns'      =>array(
                array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>40),
                array('name'=>'id', 'label'=>'ID', 'index'=>'p.id', 'width'=>80, 'hidden'=>true),
                array('name' => 'author', 'label'=>'Author', 'index' => 'u.author_user_id'),
                array('name' => 'title', 'label'=>'Title'),
                array('name' => 'status', 'label' => 'Status'),
            ),
            'actions'=>array(
                'add'=>array('caption'=>'Add Posts'),
                'delete'=>array('caption'=>'Remove')
            ),
            'filters'=>array(
                array('field' => 'title', 'type' => 'text'),
                array('field' => 'status', 'type' => 'multiselect'),
            ),
            'events'=>array('init', 'add','mass-delete')
        );


        //BEvents::i()->fire(__METHOD__.':orm', array('type'=>$type, 'orm'=>$orm));
        $data = BDb::many_as_array($orm->find_many());
        //unset unused columns
        /*$columnKeys = array_keys($config['columns']);
        foreach($data as &$prod){
            foreach($prod as $k=>$p) {
                if (!in_array($k, $columnKeys)) {
                    unset($prod[$k]);
                }
            }
        }*/

        $config['data'] = $data;

        //BEvents::i()->fire(__METHOD__.':config', array('type'=>$type, 'config'=>&$config));
        return array('config'=>$config);
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        $r = BRequest::i()->post();
        $model = $args['model'];
        if (isset($r['category-id'])) {
            $cp = FCom_Blog_Model_PostCategory::i();

            $cp->delete_many(array(
                'post_id' => $model->id,
            ));

            if ($r['category-id'] != '') {
                $tmp = explode(',', $r['category-id']);
                foreach ($tmp as $categoryId) {
                    $cp->create(array(
                        'post_id' => $model->id,
                        'category_id' => $categoryId,
                    ))->save();
                }
            }

        }
    }
}
