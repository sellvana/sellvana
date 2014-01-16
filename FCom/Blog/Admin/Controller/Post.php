<?php

class FCom_Blog_Admin_Controller_Post extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'blog/post';
    protected $_modelClass = 'FCom_Blog_Model_Post';
    protected $_gridTitle = 'Blog Posts';
    protected $_recordName = 'Blog Post';
    protected $_permission = 'blog';
    protected $_mainTableAlias = 'p';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label' => 'ID'),
            array('name' => 'author', 'label'=>'Author'),
            array('name' => 'status', 'label' => 'Status', 'editable' => true, 'mass-editable' => true, 'editor' => 'select',
                  'options' => FCom_Blog_Model_Post::i()->fieldOptions('status')),
            array('name' => 'title', 'label'=>'Title', 'href' => BApp::href('blog/post/form/?id=:id')),
            array('name' => 'url_key', 'label'=>'Url Key', 'hidden' => true),
            array('name' => 'meta_title', 'label'=>'Meta Title', 'hidden' => true),
            array('name' => 'meta_description', 'label'=>'Meta Description', 'hidden' => true),
            array('name' => 'meta_keywords', 'label'=>'Meta Keywords', 'hidden' => true),
            array('name' => 'create_ym', 'label'=>'Create ym'),
            array('name' => 'create_at', 'label'=>'Created', 'cell'=>'date'),
            array('name' => 'update_at', 'label'=>'Updated', 'cell'=>'date'),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false,
                  'data' => array('edit' => array('href' => BApp::href('blog/post/form/?id='), 'col'=>'id'),'delete' => true)),
        );
        $config['orm'] = FCom_Blog_Model_Post::i()->orm('p')
            ->select('p.*')
            ->join('FCom_Admin_Model_User', array('p.author_user_id', '=', 'u.id'), 'u')
            ->select_expr('CONCAT_WS(" ", u.firstname,u.lastname)', 'author');
        $config['actions'] = array(
            'edit' => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'title', 'type' => 'text'),
            array('field' => 'status', 'type' => 'select'),
        );
        return $config;
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
            'add'=>array('caption'=>'Add selected posts')
        );
        $config['filters'] = array(
            array('field' => 'title', 'type' => 'text'),
            array('field' => 'status', 'type' => 'select'),
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
        $orm->join('FCom_Blog_Model_CategoryPost', array('p.id','=','cp.post_id'), 'cp')
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
                array('field' => 'status', 'type' => 'select'),
            ),
            'events'=>array('init', 'add','mass-delete')
        );


        //BEvents::i()->fire(__METHOD__.'.orm', array('type'=>$type, 'orm'=>$orm));
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

        //BEvents::i()->fire(__METHOD__.'.config', array('type'=>$type, 'config'=>&$config));
        return array('config'=>$config);
    }


}
