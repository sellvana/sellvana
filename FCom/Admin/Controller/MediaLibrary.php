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
        /*if (empty($this->_allowedFolders[$folder])) {
            BDebug::error('Folder '.$folder.' is not allowed');
        }*/
        return $folder;
    }
    public function test() {
        return array('test' => 'test');
    }
    public function gridConfig($options=array())
    {
        $id = !empty($options['id']) ? $options['id'] : 'media_library';
        $folder = $options['folder'];
        $url = BApp::href('/media/grid');
        $orm = FCom_Core_Model_MediaLibrary::i()->orm()->table_alias('a')
                ->where('folder', $folder)
                ->select(array('a.id', 'a.folder', 'a.file_name', 'a.file_size'))
                ->select_expr('IF (a.subfolder is null, "", CONCAT("/", a.subfolder))', 'subfolder')
                ->order_by_expr('id asc');
            ;
        $baseSrc = rtrim(BConfig::i()->get('web/base_src'), '/') . '/';
        $config = array(
            'config' => array(
                'id' => $id,
                'caption' => 'Media Library',
                'orm' => $orm,
                //'data_mode' => 'json',
                //'url' => $url.'/data?folder='.urlencode($folder),
                'data_url' => $url.'/data?folder='.urlencode($folder),
                'edit_url' => $url.'/edit?folder='.urlencode($folder),
                'columns' => array(
                    array('type'=>'row_select'),
                    array('name'=>'id', 'label'=>'ID', 'width'=>400, 'hidden'=>true),
                    array('name'=>'prev_img', 'label'=>'Preview', 'width'=>110, 'display'=>'eval', 'print'=>'"<a href=\''.$baseSrc.'"+rc.row["folder"]+rc.row["subfolder"]+"/"+rc.row["file_name"]+"\' target=_blank><img src=\''.$baseSrc.'"+rc.row["folder"]+rc.row["subfolder"]+"/"+rc.row["file_name"]+"\' alt=\'"+rc.row["file_name"]+"\' width=50></a>"', 'sortable'=>false),
                    array('name'=>'file_name', 'label'=>'File Name', 'width'=>400),
                    array('name'=>'file_size', 'label'=>'File Size', 'width'=>260, 'search'=>false, 'display'=>'file_size'),
                    array('name'=>'associated_products', 'label'=>'Associated Products', 'width'=>50),
                    //array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'data' => array('edit' => array('href' => $url.'/data?folder='.urlencode($folder)),'delete' => true)),
                ),
                'filters' => array(
                    array('field' => 'file_name', 'type' => 'text')
                ),
                'grid_before_create'=>$id.'_register',
                'actions' => array(
                    'rescan' => array('caption' => 'Rescan', 'class' => 'btn-info btn-rescan-images'),
                    'refresh' => true,
                )
            )
        );
        
        if (!empty($options['config'])) {
            
            $config['config'] = BUtil::arrayMerge($config['config'], $options['config']);

        }

        if($options['mode'] && $options['mode'] === 'link') {
            $download_url = BApp::href('/media/grid/download?folder='.$folder.'&file=');
            $config['config']['columns'] = array(
                    array('type'=>'row_select'),
                    array('name'=>'download_url',  'hidden'=>true, 'default'=>$download_url),
                    array('name'=>'id', 'label'=>'ID', 'width'=>400, 'hidden'=>true),                    
                    array('name'=>'file_name', 'label'=>'File Name', 'width'=>200, 'display'=>'eval', 'print'=>'"<a class=\'file-attachments\' data-file-id=\'"+rc.row["file_id"]+"\' href=\'"+rc.row["download_url"]+rc.row["file_name"]+"\'>"+rc.row["file_name"]+"</a>"'),
                    array('name'=>'file_size', 'label'=>'File Size', 'width'=>260, 'search'=>false, 'display'=>'file_size')
                    //array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'data' => array('edit' => array('href' => $url.'/data?folder='.urlencode($folder)),'delete' => true)),
                );
        }
        //BEvents::i()->fire(__METHOD__, array('config'=>&$config));
        //BEvents::i()->fire(__METHOD__.':'.$folder, array('config'=>&$config));
        return $config;
    }
    public function action_index()
    {
         $this->layout('/media');
    }
    public function action_grid_data()
    {
        switch (BRequest::i()->params('do')) {
        case 'data':
            $folder = $this->getFolder();
            $r = BRequest::i()->get();
            $orm = FCom_Core_Model_MediaLibrary::i()->orm()->table_alias('a')
                ->where('folder', $folder)
                ->select(array('a.id', 'a.folder','a.file_name', 'a.file_size'))
                ->select_expr('(SELECT COUNT(*) FROM '.FCom_Catalog_Model_ProductMedia::table().' pm WHERE pm.file_id = a.id)', 'associated_products')
                ->select_expr('IF (a.subfolder is null, "", CONCAT("/", a.subfolder))', 'subfolder')
            ;
            /*if (isset($r['filters'])) {
                $filters = BUtil::fromJson($r['filters']);
                if (isset($filters['exclude_id']) && $filters['exclude_id'] != '') {
                    $arr = explode(',', $filters['exclude_id']);
                    $orm =  $orm->where_not_in('a.id', $arr);
                }
            }*/
            $data = FCom_Core_View_BackboneGrid::i()->processORM($orm);
            BResponse::i()->json(array(
                    array('c' => $data['state']['c']),
                    BDb::many_as_array($data['rows']),
                ));
            break;
        case 'download':
            $folder = $this->getFolder();
            $r = BRequest::i();
            $fileName = basename($r->get('file'));
            $fullName = FCom_Core_Main::i()->dir($folder).'/'.$fileName;
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
        $targetDir = FCom_Core_Main::i()->dir($folder);

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
                    BEvents::i()->fire(__METHOD__.':'.$folder.':upload', array('model'=>$att));
                    if (!empty($options['on_upload'])) {
                        call_user_func($options['on_upload'], $att);
                    }
                    $id = $att->id;
                    $status = '';
                } else {
                    $id = '';
                    $status = 'ERROR';
                }

                $row = array('id'=>$id, 'file_name'=>$fileName, 'file_size'=>$att->file_size, 'act' => $status, 'folder' => $folder, 'subfolder' => '');
                BResponse::i()->json($row);

                //echo "<script>parent.\$('#$gridId').jqGrid('setRowData', '$fileName', ".BUtil::toJson($row)."); </script>";
                // TODO: properly refresh grid after file upload
                // solution one "addRowData method" - will work if we could prevent add new row after Upload file on client side
                // echo "<script>parent.\$('#$gridId').addRowData('$fileName', ".BUtil::toJson($row)."); </script>";
                // solution two is to find a way to pass rowid to the server side
                //echo "<script>parent.\$('#$gridId').trigger( 'reloadGrid' ); </script>";

            }
            break;

        case 'edit':
            $id = $r->post('id');
            $fileName = $r->post('file_name');
            $att = $attModel->load($id);
            if (!$att) {
                BResponse::i()->json(array('error'=>true));
                return;
            }
            $oldFileName = $att->file_name;
            if (@rename($targetDir.'/'.$oldFileName, $targetDir.'/'.$fileName)) {
                $att->set('file_name', $fileName)->save();
                BEvents::i()->fire(__METHOD__.':'.$folder.':edit', array('model'=>$att));
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
            BEvents::i()->fire(__METHOD__.':'.$folder.':delete', array('files'=>$files));
            if (!empty($options['on_delete'])) {
                call_user_func($options['on_delete'], $args);
            }
            BResponse::i()->json(array('success'=>true));
            break;
        case 'rescan':
            try {
                $fileSPLObjects =  new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($targetDir),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                $arrImages = array();
                $records = BDb::many_as_array(FCom_Core_Model_MediaLibrary::i()->orm()->select(array('folder', 'subfolder', 'file_name'))->where('folder', $folder)->find_many());
                foreach( $fileSPLObjects as $fullFileName => $fileSPLObject ) {
                    $fileName = $fileSPLObject->getFilename();
                    $path = $fileSPLObject->getPath();
                    $subFolder = null;
                    if (is_file($fullFileName) && exif_imagetype($fullFileName)) {
                        if ($path != $targetDir) {
                            $path = str_replace('\\', '/', $path);
                            $subFolder = trim(str_replace($targetDir.'/', '', $path));
                            $subFolder = ltrim($subFolder, '/');
                        }
                        $tmp = array('folder' => $folder, 'subfolder' => $subFolder, 'file_name' => $fileName);
                        if (!in_array($tmp, $records)) {
                            array_push($arrImages, $tmp);
                        }
                    }
                }
                foreach ($arrImages as $arr) {
                    $arr['file_size'] = ($arr['subfolder']) ? filesize($targetDir.'/'.$arr['subfolder'].'/'.$arr['file_name']) :
                                        filesize($targetDir.'/'.$arr['file_name']);
                    $attModel->create($arr)->save();
                }
                BResponse::i()->json(array('status' => 'success'));
            } catch (Exception $e) {
                BResponse::i()->json(array('status' => 'error', 'messages' => $e->getMessage()));
            }
            break;
        }
    }
}
