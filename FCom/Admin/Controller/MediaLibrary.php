<?php

class FCom_Admin_Controller_MediaLibrary extends FCom_Admin_Controller_Abstract
{
    protected $_allowedFolders = array();

    public function allowFolder($folder)
    {
        $this->_allowedFolders[$folder] = 1;
        return $this;
    }

    public function getFolder()
    {
        $folder = BRequest::i()->get('folder');
        if (empty($this->_allowedFolders[$folder])) {
            BDebug::error('Folder '.$folder.' is not allowed');
        }
        return $folder;
    }

    public function gridConfig($options=array())
    {
        $id = !empty($options['id']) ? $options['id'] : 'media_library';
        $folder = $options['folder'];
        $url = BApp::href('/media/grid');
        $config = array(
            'grid' => array(
                'id' => $id,
                'caption' => 'Media Library',
                'datatype' => 'json',
                'url' => $url.'/data?folder='.urlencode($folder),
                'editurl' => $url.'/edit?folder='.urlencode($folder),
                'colModel' => array(
                    array('name'=>'id', 'label'=>'ID', 'width'=>400, 'hidden'=>true),
                    array('name'=>'file_name', 'label'=>'File Name', 'width'=>400, 'editable'=>true),
                    array('name'=>'file_size', 'label'=>'File Size', 'width'=>60, 'search'=>false),
                    array('name'=>'act', 'label'=>'Actions', 'width'=>70, 'search'=>false, 'sortable'=>false, 'resizable'=>false),
                ),
                'multiselect' => true,
                'multiselectWidth' => 30,
            ),
            'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
            array('navButtonAdd', 'id'=>'upload-btn', 'caption' => 'Upload', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Attachments to Library', 'cursor'=>'pointer'),
            array('navButtonAdd', 'caption' => 'Delete', 'buttonicon'=>'ui-icon-trash', 'title' => 'Delete Attachments from Library', 'cursor'=>'pointer'),
        );
        if (!empty($options['config'])) {
            $config = BUtil::arrayMerge($config, $options['config']);
        }
        BPubSub::i()->fire(__METHOD__, array('config'=>&$config));
        BPubSub::i()->fire(__METHOD__.'.'.$folder, array('config'=>&$config));
        return $config;
    }

    public function action_grid_data()
    {
        switch (BRequest::i()->params('do')) {
        case 'data':
            $folder = $this->getFolder();
            $orm = FCom_Core_Model_MediaLibrary::i()->orm()->table_alias('a')
                ->where('folder', $folder)
                ->select(array('a.id', 'a.file_name', 'a.file_size'))
            ;
            $data = FCom_Admin_View_Grid::i()->processORM($orm, 'FCom_Admin_Controller_Media::action_grid_get.'.$folder);
            BResponse::i()->json($data);
            break;

        case 'download':
            $folder = $this->getFolder();
            $r = BRequest::i();
            $fileName = basename($r->get('file'));
            $fullName = FCom_Core::i()->dir($folder).'/'.$fileName;
            BResponse::i()->sendFile($fullName, $fileName, $r->get('inline') ? 'inline' : 'attachment');
            break;
        }
    }

    public function action_grid_data__POST()
    {
        $this->processGridPost();
    }

    /**
    * $options = array(
    *   'folder' => 'media/product/attachment',
    *   'subfolder' => null,
    *   'model_class' => 'FCom_Core_Model_MediaLibrary', (default)
    *   'on_upload' => function() { },
    *   'on_edit' => function() { },
    *   'on_delete' => function() { },
    * );
    *
    * $request['params'] = array(
    *   'do' => 'upload'|'edit'|'delete'
    * );
    *
    * $request['post'] = array(
    *   'grid' => 'products', // upload
    *   'id' => 123, // edit
    *   'file_name' => 'abc.jpg', // edit
    *   'delete' => array('abc.jpg', 'def.png'), // delete
    * );
    *
    * @param array $options
    */
    public function processGridPost($options=array())
    {
        $r = BRequest::i();
        $gridId = $r->get('grid');
        $folder = !empty($options['folder']) ? $options['folder'] : $this->getFolder();
        $subfolder = !empty($options['subfolder']) ? $options['subfolder'] : null;
        $targetDir = FCom_Core::i()->dir($folder);

        $attModel = !empty($options['model_class']) ? $options['model_class'] : 'FCom_Core_Model_MediaLibrary';
        $attModel = is_string($attModel) ? $attModel::i() : $attModel;

        switch($r->params('do')) {
        case 'upload':
            //set_time_limit(0);
            //ob_implicit_flush();
            //ignore_user_abort(true);
            $uploads = $_FILES['upload'];
            foreach ($uploads['name'] as $i=>$fileName) {
                if (!$fileName) {
                    continue;
                }
                if (!$uploads['error'][$i] && @move_uploaded_file($uploads['tmp_name'][$i], $targetDir.'/'.$fileName)) {
                    $att = $attModel->load(array('folder'=>$folder, 'file_name'=>$fileName));
                    if (!$att) {
                        $att = $attModel->create(array(
                            'folder'    => $folder,
                            'subfolder' => $subfolder,
                            'file_name' => $fileName,
                            'file_size' => $uploads['size'][$i],
                        ))->save();
                    } else {
                        $att->set(array('file_size' => $uploads['size'][$i]))->save();
                    }
                    BPubSub::i()->fire(__METHOD__.'.'.$folder.'.upload', array('model'=>$att));
                    if (!empty($options['on_upload'])) {
                        call_user_func($options['on_upload'], $att);
                    }
                    $id = $att->id;
                    $status = '';
                } else {
                    $id = '';
                    $status = 'ERROR';
                }
                echo "<script>parent.\$('#$gridId').jqGrid('setRowData', '$fileName', {id:'$id', act:'$status'})</script>";
            }
            exit;

        case 'edit':
            $id = $r->post('id');
            $fileName = $r->post('file_name');
            $att = $attModel->load($id);
            if (!$att) {
                BResponse::i()->json(array('error'=>true));
            }
            $oldFileName = $att->file_name;
            if (@rename($targetDir.'/'.$oldFileName, $targetDir.'/'.$fileName)) {
                $att->set('file_name', $fileName)->save();
                BPubSub::i()->fire(__METHOD__.'.'.$folder.'.edit', array('model'=>$att));
                if (!empty($options['on_edit'])) {
                    call_user_func($options['on_edit'], $att);
                }
                BResponse::i()->json(array('success'=>true));
            } else {
                BResponse::i()->json(array('error'=>true));
            }
            break;

        case 'delete':
            $files = (array)$r->post('delete');
            foreach ($files as $fileName) {
                @unlink($targetDir.'/'.$fileName);
            }
            $args = array('folder'=>$folder, 'file_name'=>$files);
            $attModel->delete_many($args);
            BPubSub::i()->fire(__METHOD__.'.'.$folder.'.delete', array('files'=>$files));
            if (!empty($options['on_delete'])) {
                call_user_func($options['on_delete'], $args);
            }
            BResponse::i()->json(array('success'=>true));
            break;
        }
    }
}