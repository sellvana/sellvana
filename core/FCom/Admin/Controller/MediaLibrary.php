<?php

/**
 * Class FCom_Admin_Controller_MediaLibrary
 *
 * @property Sellvana_Catalog_Model_ProductMedia $Sellvana_Catalog_Model_ProductMedia
 * @property FCom_Core_View_BackboneGrid $FCom_Core_View_BackboneGrid
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Core_Main $FCom_Core_Main
 * @property FCom_Core_Model_MediaLibrary $FCom_Core_Model_MediaLibrary
 */
class FCom_Admin_Controller_MediaLibrary extends FCom_Admin_Controller_Abstract
{
    protected $_allowedFolders = [];
    protected $_uploadConfigs;

    const ERROR = 'ERROR';

    /**
     * @param string $folder
     * @return $this
     */
    public function allowFolder($folder)
    {
        $this->_allowedFolders[$folder] = 1;
        $this->_allowedFolders[$this->_parseFolder($folder)] = 1;
        return $this;
    }

    /**
     * @return array|mixed|null|string
     * @throws BException
     */
    public function getFolder()
    {
        $folder = $this->BRequest->get('folder');
        if (empty($folder)) {
            $type = $this->BRequest->get('type');
            if ($type) {
                $uploadConfig = $this->uploadConfig($type);
                if (empty($uploadConfig)) {
                    throw new BException("Unknown upload type.");
                }
                $canUpload = isset($uploadConfig['can_upload']) ? $uploadConfig['can_upload'] : true;
                if ($canUpload && isset($uploadConfig['folder'])) {
                    $folder = $uploadConfig['folder'];
                }
            }
        }
        if(!$folder){
            return null;
        }
        if (empty($this->_allowedFolders[$folder])) {
            throw new BException('Folder ' . $folder . ' is not allowed');
        }

        $folder = $this->_parseFolder($folder);
        return $folder;
    }

    public function test() {
        return ['test' => 'test'];
    }

    public function gridConfig($options = [])
    {
        $id = !empty($options['id']) ? $options['id'] : 'media_library';
        $folder = $this->_parseFolder($options['folder']);
        $url = $this->BApp->href('/media/grid');
        $orm = $this->FCom_Core_Model_MediaLibrary->orm('a')
            ->where('folder', $folder)
            ->select(['a.id', 'a.folder', 'a.file_name', 'a.file_size', 'a.data_serialized'])
            ->select_expr('IF (a.subfolder is null, "", CONCAT("/", a.subfolder))', 'subfolder')
            //  ->order_by_expr('id asc')
        ;
        if ($this->BModuleRegistry->isLoaded('Sellvana_Catalog')) {
            $orm->select_expr('(SELECT COUNT(*) FROM ' . $this->Sellvana_Catalog_Model_ProductMedia->table()
                . ' pm WHERE pm.file_id = a.id)', 'associated_products');
        }
        $baseSrc = rtrim($this->BConfig->get('web/base_src'), '/') . '/';

        if ($id == 'all_videos') {
            $defaultThumbnail = $this->BApp->src('@FCom_Admin/Admin/theme1/assets/images/video-default.jpg');
            $elementPrint = '
                if (rc.row["file_size"] !== undefined && rc.row["file_size"] !== null) {
                    var html = $("<video width=\'200\' height=\'140\' controls=\'controls\' id=\'video-"+ rc.row["id"] +"\' class=\'product-video-media\' preload=\'none\'><source src=\''. $baseSrc .'" + rc.row["folder"] + "/" + rc.row["file_name"] + "\' type=\'video/" + rc.row["file_name"].slice(rc.row["file_name"].lastIndexOf(".") + 1) + "\'></video>");
                    "<a href=\'javascript:void(0)\' class=\'btn-video-player\' title=\'Click to preview\' data-html=\'"+ html[0].outerHTML +"\'><img src=\''. $defaultThumbnail .'\' width=\'200\' alt=\'"+ rc.row["file_name"] +"\'></a>"
                } else {
                    var data = typeof rc.row[\'data_serialized\'] === \'string\' ? JSON.parse(rc.row[\'data_serialized\']) : rc.row[\'data_serialized\'];
                    if (data !== undefined) {
                        var provider = data !== undefined ? data.provider_name.toLowerCase() : "";
                        switch(provider) {
                            case \'youtube\':
                                var src = $(data.html).prop(\'src\');
                                var html = $("<video width=\'200\' height=\'140\' controls=\'controls\' id=\'video-"+ row.id +"\' class=\'product-video-media\' preload=\'none\'><source src=\'" + src + "\' title=\'" + data.title + "\' type=\'video/youtube\'></video>");
                                "<a href=\'javascript:void(0)\' class=\'btn-video-player\' title=\'Click to preview\' data-html=\'"+ html[0].outerHTML +"\'><img src=\'"+ data.thumbnail_url +"\' width=\'200\' alt=\'"+ data.title +"\'></a>"
                                break;
                            case \'vimeo\':
                                var html = data.html.replace(/(width="\d{3}"\s+height="\d{3}")/, \'width="200" height="140"\');
                                "<a href=\'javascript:void(0)\' title=\'Click to preview\' class=\'btn-video-player\' data-html=\'"+html+"\'><img src=\'"+ data.thumbnail_url +"\' width=\'200\' alt=\'"+data.title+"\'></a>"
                                break;
                            default:
                                data.html
                                break;
                        }
                    }
                }
            ';
        } else {
            $elementPrint = '"<a href=\'' . $baseSrc . '"+rc.row["folder"]+rc.row["subfolder"]+"/"+rc.row["file_name"]+"\' target=_blank>'
                            . '<img src=\'' . $baseSrc . '"+rc.row["folder"]+rc.row["subfolder"]+"/"+rc.row["file_name"]+"\' alt=\'"+rc.row["file_name"]+"\' width=50></a>"';
        }

        $config = [
            'config' => [
                'id'          => $id,
                'caption'     => (('Media Library')),
                'orm'         => $orm,
                //'data_mode' => 'json',
                //'url'       => $url.'/data?folder='.urlencode($folder),
                'data_url'    => $url . '/data?folder=' . urlencode($folder),
                'edit_url'    => $url . '/edit?folder=' . urlencode($folder),
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => (('ID')), 'width' => 50, 'hidden' => true],
                    ['name' => 'prev_img', 'label' => (('Preview')), 'width' => 110, 'display' => 'eval', 'print' => $elementPrint, 'sortable' => false],
                    ['name' => 'file_name', 'label' => (('File Name')), 'width' => 400],
                    ['name' => 'file_size', 'label' => (('File Size')), 'width' => 260, 'search' => false,
                        'display' => 'file_size'],
                    ['name' => 'associated_products', 'label' => (('Associated Products')), 'width' => 50],
                    ['type' => 'btn_group',
                        'buttons' => [
                            ['name' => 'delete']
                        ]
                    ],
                ],
                'filters' => [
                    ['field' => 'file_name', 'type' => 'text']
                ],
                //callbacks for backbonegrid
                'grid_before_create' => $id . '_register',
                'afterMassDelete' => $id .'_afterMassDelete',
                //callbacks for react griddle
                'callbacks' => [
                    'componentDidMount' => 'registerGrid' . $id,
                ],
                'actions' => [
                    'rescan'  => ['caption' => (('Rescan')), 'class' => 'btn-info btn-rescan-media'],
                    'refresh' => true,
                ]
            ]
        ];

        if (!empty($options['config'])) {
            $config['config'] = $this->BUtil->arrayMerge($config['config'], $options['config']);
        }

        if ($options['mode'] && $options['mode'] === 'link') {
            $download_url = $this->BApp->href('/media/grid/download?folder=' . $folder . '&file=');
            $config['config']['columns'] = [
                ['type' => 'row_select'],
                ['name' => 'download_url',  'hidden' => true, 'default' => $download_url],
                ['name' => 'id', 'label' => (('ID')), 'width' => 50, 'hidden' => true],
                ['name' => 'file_name', 'label' => (('File Name')), 'width' => 200, 'display' => 'eval',
                    'print' => '"<a class=\'file-attachments\' data-file-id=\'"+rc.row["file_id"]+"\' '
                        . 'href=\'"+rc.row["download_url"]+rc.row["file_name"]+"\'>"+rc.row["file_name"]+"</a>"'],
                ['name' => 'file_size', 'label' => (('File Size')), 'width' => 260, 'search' => false,
                    'display' => 'file_size'],
                ['name' => 'associated_products', 'label' => (('Associated Products')), 'width' => 50],
                //array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'data' => array('edit' => array('href' => $url.'/data?folder='.urlencode($folder)),'delete' => true)),
            ];
        }

        if ($options['mode'] && $options['mode'] === 'images') {
            $downloadUrl = $this->BApp->href('/media/grid/download?folder=' . $folder . '&file=');
            switch ($options['folder']) { // TODO: think about how we can make it more versatile (e.g. via config)
                case 'media/category/images':
                    $thumbUrl = $this->FCom_Core_Main->resizeUrl($this->BConfig->get('web/media_dir') . '/category/images', ['s' => 100]);
                    break;
                case 'media/product/videos':
                    $thumbUrl = $this->FCom_Core_Main->resizeUrl($this->BConfig->get('web/media_dir') . '/product/videos', ['s' => 100]);
                    break;
                case 'media/product/attachment':
                    $thumbUrl = $this->FCom_Core_Main->resizeUrl($this->BConfig->get('web/media_dir') . '/product/attachment', ['s' => 100]);
                    break;
                case 'media/blog/images':
                    $thumbUrl = $this->FCom_Core_Main->resizeUrl($this->BConfig->get('web/media_dir') . '/blog/images', ['s' => 100]);
                    break;
                default:
                    $thumbUrl = $this->FCom_Core_Main->resizeUrl($this->BConfig->get('web/media_dir') . '/product/images', ['s' => 100]);
                    break;
            }
            $config['config']['columns'] = [
                ['type' => 'row_select'],
                ['name' => 'download_url',  'hidden' => true, 'default' => $downloadUrl],
                ['name' => 'thumb_url',  'hidden' => true, 'default' => $thumbUrl],
                ['name' => 'id', 'label' => (('ID')), 'width' => 50, 'hidden' => true],
                ['name' => 'file_name', 'label' => (('File Name')), 'width' => 200, 'display' => 'eval',
                    'print' => '"<a class=\'file-attachments\' data-file-id=\'"+rc.row["file_id"]+"\' '
                        . 'href=\'"+rc.row["download_url"]+rc.row["file_name"]+"\'>"+rc.row["file_name"]+"</a>"'],
                ['name' => 'prev_img', 'label' => (('Preview')), 'width' => 110, 'display' => 'eval',
                    'print' => '"<a href=\'"+rc.row["download_url"]+rc.row["subfolder"]+"/"+rc.row["file_name"]+"\'>'
                        . '<img src=\'"+rc.row["thumb_url"]+rc.row["subfolder"]+"/"+rc.row["file_name"]+"\' '
                        . 'alt=\'"+rc.row["file_name"]+"\' ></a>"',
                    'sortable' => false],
                ['name' => 'file_size', 'label' => (('File Size')), 'width' => 260, 'search' => false,
                    'display' => 'file_size']
            ];
        }
        //$this->BEvents->fire(__METHOD__, array('config'=>&$config));
        //$this->BEvents->fire(__METHOD__.':'.$folder, array('config'=>&$config));
        return $config;
    }

    public function gridConfigLibrary($options = [])
    {
        $id = !empty($options['id']) ? $options['id'] : 'media_library';
        $folder = 'media';
        $url = $this->BApp->href('/media/grid');
        $orm = $this->FCom_Core_Model_MediaLibrary->orm('a')
            ->select(['a.id', 'a.folder', 'a.file_name', 'a.file_size', 'a.data_serialized'])
            ->select_expr('IF (a.subfolder is null, "", CONCAT("/", a.subfolder))', 'subfolder');

        if ($this->BModuleRegistry->isLoaded('Sellvana_Catalog')) {
            $orm->select_expr('(SELECT COUNT(*) FROM ' . $this->Sellvana_Catalog_Model_ProductMedia->table()
                . ' pm WHERE pm.file_id = a.id)', 'associated_products');
        }
        $baseSrc = rtrim($this->BConfig->get('web/base_src'), '/') . '/';

        $elementPrint = '
            if (rc.row["file_size"]) {
                "<a href=\''.$url.'/download?folder="+rc.row["folder"]+ "&file="+rc.row["file_name"]+"\' target=_blank><img src=\'/"+rc.row["thumb_path"]+"\' alt=\'"+rc.row["file_name"]+"\' width=100></a>"
            } else if (rc.row[\'data_serialized\']) {
                var data = JSON.parse(rc.row[\'data_serialized\']);
                if (data) {
                    "<img src=\'"+ data.thumbnail_url +"\' width=100 alt=\'"+ data.title +"\'>"
                }
            }
        ';

        $config = [
            'config' => [
                'id'            => $id,
                'caption'       => (('Media Library')),
                'orm'           => $orm,
                'data_url'      => $url . '/data',
                'edit_url'      => $url . '/edit',
                'pending_state' => true,
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => (('ID')), 'width' => 50, 'hidden' => true],
                    ['name' => 'prev_img', 'label' => (('Preview')), 'width' => 110, 'display' => 'eval',
                        'print' => $elementPrint,
                        'sortable' => false],
                    ['name' => 'file_name', 'label' => (('File Name')), 'width' => 400],
                    ['name' => 'folder', 'label' => (('Folder')), 'width' => 200],
                    ['name' => 'file_size', 'label' => (('File Size')), 'width' => 260, 'search' => false,
                        'display' => 'file_size'],
                    ['name' => 'associated_products', 'label' => (('Associated Products')), 'width' => 50],
                    ['type' => 'btn_group',
                        'buttons' => [
                            ['name' => 'delete']
                        ]
                    ],
                ],
                'filters' => [
                    ['field' => 'file_name', 'type' => 'text']
                ],
                //callbacks for backbonegrid
                //'grid_before_create' => $id . '_register',
                //'afterMassDelete' => $id .'_afterMassDelete',
                //callbacks for react griddle
                'callbacks' => [
                    'componentDidMount' => 'registerGrid' . $id,
                ],
                'actions' => [
                    'add-image' => [
                        'caption'  => (('Add Files')),
                        'type'     => 'button',
                        'id'       => 'add-attachment-from-grid',
                        'class'    => 'btn-primary',
                        'callback' => 'gridShowMedia' . $id
                    ],
                    'rescan' => ['caption' => (('Rescan')), 'class' => 'btn-info btn-rescan-media'],
                    //'refresh' => true,
                ],
                'page_rows_data_callback' => [$this, 'afterInitialLibraryData']
            ],
        ];

        if (!empty($options['config'])) {
            $config['config'] = $this->BUtil->arrayMerge($config['config'], $options['config']);
        }

        return $config;
    }

    public function layoutGridLibraryConfig($options = [])
    {
        $config = $this->gridConfigLibrary($options);
        unset($config['config']['actions']['add-image']);
        return $config;
    }

    /**
     * @param $rows
     * @return mixed
     */
    public function afterInitialLibraryData($rows)
    {
        $baseUrl = $this->BConfig->get('web/base_dir');
        $hlp     = $this->FCom_Core_Main;
        $images  = ['jpeg', 'jpg', 'tiff', 'gif', 'png', 'bmp'];
        foreach ($rows as & $row) {
            $ext = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $images)) {
                $thumbUrl = 'media/image-not-found.png';
            } else {
                $folder = $row["folder"];
                //if (strpos($folder, 'media/') === 0) {
                //    $folder = str_replace('media/', '', $row["folder"]);
                //}
                $thumbUrl = $folder . $row["subfolder"] . "/" . $row["file_name"];
            }
            $row['thumb_path'] = trim($hlp->resizeUrl($baseUrl . '/' . $thumbUrl, ['s' => 68]), '/');
        }

        return $rows;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function gridDataAfter($data)
    {
        $baseUrl = $this->BConfig->get('web/base_dir');//?: 'media';
        $hlp      = $this->FCom_Core_Main;
        $images = ['jpeg', 'jpg', 'tiff', 'gif', 'png', 'bmp'];

        foreach ($data['rows'] as $row) {
            /** @var Sellvana_Catalog_Model_Product $row */
            $customRowData = $row->getData();
            if ($customRowData) {
                $row->set($customRowData);
                $row->set('data', null);
            }

            $ext = strtolower(pathinfo($row->get('file_name'), PATHINFO_EXTENSION));
            if (!in_array($ext, $images)) {
                $thumbUrl = 'media/image-not-found.png';
            } else {
                $folder = $row->get("folder");
                //if (strpos($folder, 'media/') === 0) {
                //    $folder = str_replace('media/', '', $row->get('folder'));
                //}
                $thumbUrl = $folder . $row->get('subfolder') . "/" . $row->get('file_name');
            }
            $row->set('thumb_path', trim($hlp->resizeUrl($baseUrl . '/' . $thumbUrl, ['s' => 68]), '/'));
        }
        unset($row);

        return $data;
    }

    public function action_index()
    {
        $gridConfig = $this->gridConfigLibrary();
        unset($gridConfig['config']['pending_state']);
        $config = [
            'id'         => 'media_library',
            'title'      => $this->_(("Media Library")),
            'gridConfig' => $gridConfig,
        ];
        $this->layout('/media');
        $view = $this->layout()->view('media')->set('config', $config);
    }

    public function action_grid_data()
    {
        //if (!$this->BRequest->xhr()) {
        //    $this->BResponse->status('403', 'Available only for XHR', 'Available only for XHR');
        //    return;
        //}
        switch ($this->BRequest->param('do')) {
            case 'data':
                $folder = $this->getFolder();
    //            $r = $this->BRequest->get();
                $orm = $this->FCom_Core_Model_MediaLibrary->orm('a')
                    ->select(['a.id', 'a.folder', 'a.file_name', 'a.file_size', 'a.data_serialized'])
                    ->select_expr('IF (a.subfolder is null, "", CONCAT("/", a.subfolder))', 'subfolder')
                ;
                if($folder){
                    $orm->where('folder', $folder);
                }

                if ($this->BModuleRegistry->isLoaded('Sellvana_Catalog')) {
                    $orm->select_expr('(SELECT COUNT(*) FROM ' . $this->Sellvana_Catalog_Model_ProductMedia->table()
                        . ' pm WHERE pm.file_id = a.id)', 'associated_products');
                }
                /*if (isset($r['filters'])) {
                    $filters = $this->BUtil->fromJson($r['filters']);
                    if (isset($filters['exclude_id']) && $filters['exclude_id'] != '') {
                        $arr = explode(',', $filters['exclude_id']);
                        $orm =  $orm->where_not_in('a.id', $arr);
                    }
                }*/
                $data = $this->FCom_Core_View_BackboneGrid->processORM($orm);
                $data = $this->gridDataAfter($data);
                $this->BResponse->json([
                        ['c' => $data['state']['c']],
                        $this->BDb->many_as_array($data['rows']),
                    ]);
                break;

            case 'download':
                $folder   = $this->getFolder();
                $r        = $this->BRequest;
                $fileName = basename($r->get('file'));
                $fullName = $this->FCom_Core_Main->dir($folder) . '/' . $fileName;
                
                if (!$this->BUtil->isPathWithinRoot($fullName, ['@media_dir', '@random_dir'])) {
                    $this->BResponse->status(403, (('Invalid file source')), 'Invalid file source');
                }

                $this->BResponse->sendFile($fullName, $fileName, $r->get('inline') ? 'inline' : 'attachment');
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
     * @throws BException
     */
    public function processGridPost($options = [])
    {
        $r         = $this->BRequest;
        $gridId    = $r->get('grid');
        $folder    = !empty($options['folder']) ? $options['folder'] : $this->getFolder();
        $subfolder = !empty($options['subfolder']) ? $options['subfolder'] : null;
        $targetDir = $this->FCom_Core_Main->dir($folder);
        
        $attModel  = !empty($options['model_class']) ? $options['model_class'] : 'FCom_Core_Model_MediaLibrary';
        $attModel  = is_string($attModel) ? $this->{$attModel} : $attModel;

        /*
         * class GridForm: "oper" use in grid, "do" use in form
         * todo: consider change param name from "do" to "oper".
         */
        $do = $r->post('oper');
        if (empty($do)) {
            $do = $r->param('do');
        }

        switch ($do) {
            case 'upload':
                $type = $r->get('type');
                if (!$type) {
                    throw new BException("Missing upload type");
                }
                $uploadConfigs = $this->uploadConfig($type);
                if (empty($uploadConfigs)) {
                    throw new BException("Unknown upload type.");
                }
                $canUpload = isset($uploadConfigs['can_upload'])? $uploadConfigs['can_upload']: false;// allow upload in case no permission is configured? Or deny?
                $blacklistExt = [
                    'php' => 1, 'php3' => 1, 'php4' => 1, 'php5' => 1, 'htaccess' => 1,
                    'phtml' => 1, 'html' => 1, 'htm' => 1, 'js' => 1, 'css' => 1, 'swf' => 1, 'xml' => 1,
                ];

                if (isset($uploadConfigs['filetype'])) { // todo figure out how to merge processed config file types
                    $fileTypes = explode(',', $uploadConfigs['filetype']);
                    if (empty($options['whitelist_ext'])) {
                        $options['whitelist_ext'] = $fileTypes;
                    } else {
                        $options['whitelist_ext'] = $this->BUtil->arrayMerge($options['whitelist_ext'], $fileTypes);
                    }
                }

                if (!empty($options['whitelist_ext'])) {
                    foreach ($options['whitelist_ext'] as $ext) {
                        unset($blacklistExt[$ext]);
                    }
                }

                //set_time_limit(0);
                //ob_implicit_flush();
                //ignore_user_abort(true);
                if ($canUpload) {

                    $uploads = $_FILES['upload'];
                    $rows    = [];
                    foreach ($uploads['name'] as $i => $fileName) {

                        if (!$fileName) {
                            continue;
                        }
                        $associatedProducts = 0;
                        $fileSize           = 0;
                        $message            = '';
                        $ext                = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $fileName           = preg_replace('/[^\w\d_.-]+/', '_', $fileName);

                        if (!empty($uploads['error'][$i])) {
                            $id      = '';
                            $status  = static::ERROR;
                            $message = $uploads['error'][$i];
                        } elseif (!empty($blacklistExt[$ext]) || !in_array($ext, $options['whitelist_ext'])) {
                            $id      = '';
                            $status  = static::ERROR;
                            $message = (('Illegal file extension'));
                        } elseif (preg_match('#\.(gif|jpe?g|png)$#',
                                $fileName) && !@getimagesize($uploads['tmp_name'][$i])
                        ) {
                            $id      = '';
                            $status  = static::ERROR;
                            $message = (('Invalid image uploaded'));
                        } elseif (!$this->BUtil->moveUploadedFileSafely($uploads['tmp_name'][$i], $targetDir . '/' . $fileName)) {
                            $id      = '';
                            $status  = static::ERROR;
                            $message = (('Unable to save the file'));
                        } else {
                            $att = $attModel->loadWhere(['folder' => (string)$folder, 'file_name' => (string)$fileName]);

                            if (!$att) {
                                $att = $attModel->create([
                                    'folder'    => $folder,
                                    'subfolder' => $subfolder,
                                    'file_name' => $fileName,
                                    'file_size' => $uploads['size'][$i],
                                    'create_at' => $this->BDb->now(),
                                    'update_at' => $this->BDb->now()
                                ])->save();
                            } else {
                                if (in_array($type, ['product-images', 'product-attachments', 'product-videos'])
                                    && $this->BModuleRegistry->isLoaded('Sellvana_Catalog')
                                ) {
                                    $associatedProducts = $this->Sellvana_Catalog_Model_ProductMedia
                                        ->orm()
                                        ->select_expr('COUNT(*)', 'associated_products')
                                        ->where('file_id', $att->get('id'))
                                        ->find_one();
                                    $associatedProducts = $associatedProducts->get('associated_products');
                                }
                                $att->set(['file_size' => $uploads['size'][$i], 'update_at' => $this->BDb->now()])->save();
                            }
                            $this->BEvents->fire(__METHOD__ . ':' . $folder . ':upload', ['model' => $att]);
                            if (!empty($options['on_upload'])) {
                                $this->BUtil->call($options['on_upload'], $att);
                            }
                            $id       = $att->id;
                            $fileSize = $att->file_size;
                            $status   = '';
                        }

                        if($status == static::ERROR){
                            $rows[] = [
                                'error'     => $message,
                                'file_name' => $fileName,
                            ];
                        } else {
                            $rows[] = [
                                'id'                  => $id,
                                'file_name'           => $fileName,
                                'file_size'           => $fileSize,
                                'act'                 => $status,
                                'folder'              => $folder,
                                'subfolder'           => '',
                                'associated_products' => $associatedProducts
                            ];
                        }

                        //echo "<script>parent.\$('#$gridId').jqGrid('setRowData', '$fileName', ".$this->BUtil->toJson($row)."); </script>";
                        // TODO: properly refresh grid after file upload
                        // solution one "addRowData method" - will work if we could prevent add new row after Upload file on client side
                        // echo "<script>parent.\$('#$gridId').addRowData('$fileName', ".$this->BUtil->toJson($row)."); </script>";
                        // solution two is to find a way to pass rowid to the server side
                        //echo "<script>parent.\$('#$gridId').trigger( 'reloadGrid' ); </script>";

                    }
                    $this->BResponse->json(['files' => $rows]);
                }
                break;
            case 'edit':
                $id = $r->post('id');
                $fileName = $r->post('file_name');
                $att = $attModel->load($id);
                if (!$att) {
                    $this->BResponse->json(['error' => true, 'message' => $this->BApp->t('Can not load related model.')]);
                    return;
                }
                $oldFileName = $att->file_name;
                $oldFile = sprintf('%s/%s', $targetDir, $oldFileName);
                $newFile = sprintf('%s/%s', $targetDir, $fileName);
                if (file_exists($oldFile) && @rename($oldFile, $newFile)) {
                    $att->set('file_name', $fileName)->save();
                    $this->BEvents->fire(__METHOD__ . ':' . $folder . ':edit', ['model' => $att]);
                    if (!empty($options['on_edit'])) {
                        $this->BUtil->call($options['on_edit'], $att);
                    }
                    $this->BResponse->json(['success' => true]);
                } else if (!file_exists($oldFile)) {
                    $att->set('file_name', $fileName)->save();
                    $this->BResponse->json(['success' => true]);
                } else {
                    $this->BResponse->json(['error' => true, 'message' => $this->BApp->t('Can not edit file due to system error.')]);
                }
                break;
            case 'delete':
                $files = (array)$r->post('delete');
                foreach ($files as $fileName) {
                    $this->BUtil->deleteFileSafely($targetDir . '/' . $fileName);
                }
                $args = ['folder' => $folder, 'file_name' => $files];
                $attModel->delete_many($args);
                $this->BEvents->fire(__METHOD__ . ':' . $folder . ':delete', ['files' => $files]);
                if (!empty($options['on_delete'])) {
                    $this->BUtil->call($options['on_delete'], $args);
                }
                $this->BResponse->json(['success' => true]);
                break;
            case 'mass-delete':
                $listIds = $r->post('id');
                $listIds = explode(',', $listIds);
                foreach ($listIds as $id) {
                    $file = $attModel->load($id);
                    if ($file) {
                       $file->delete();
                    }
                }
                $this->BResponse->json(['success' => true]);
                break;
            case 'rescan':
                try {
                    if (!file_exists($targetDir)) {
                        $this->BResponse->json(['status' => 'success']);
                        break;
                    }
                    $fileSPLObjects =  new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($targetDir),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    $arrImages = [];
                    $records = $this->BDb->many_as_array($this->FCom_Core_Model_MediaLibrary->orm()
                        ->select(['folder', 'subfolder', 'file_name'])->where('folder', $folder)->find_many());
                    foreach ($fileSPLObjects as $fullFileName => $fileSPLObject) {
                        $fileName = $fileSPLObject->getFilename();
                        $path = $fileSPLObject->getPath();
                        $subFolder = null;
                        if (is_file($fullFileName) && getimagesize($fullFileName)) {
                            if ($path != $targetDir) {
                                $path = str_replace('\\', '/', $path);
                                $subFolder = trim(str_replace($targetDir . '/', '', $path));
                                $subFolder = ltrim($subFolder, '/');
                            }
                            $tmp = ['folder' => $folder, 'subfolder' => $subFolder, 'file_name' => $fileName];
                            if (!in_array($tmp, $records)) {
                                array_push($arrImages, $tmp);
                            }
                        }
                    }
                    foreach ($arrImages as $arr) {
                        $arr['file_size'] = ($arr['subfolder']) ? filesize($targetDir . '/' . $arr['subfolder'] . '/' . $arr['file_name']) :
                                            filesize($targetDir . '/' . $arr['file_name']);
                        $attModel->create($arr)->save();
                    }
                    $this->BResponse->json(['status' => 'success']);
                } catch (Exception $e) {
                    $this->BResponse->json(['status' => 'error', 'messages' => $e->getMessage()]);
                }
                break;
            case 'rescan_library':
                try {
                    if (!file_exists($targetDir)) {
                        $this->BResponse->json(['status' => 'success']);
                        break;
                    }
                    $uploadConfigs = $this->uploadConfig();

                    foreach ($uploadConfigs as $uc) {
                        $folder = $this->_parseFolder($uc['folder']);
                        $targetDirLocal = $targetDir . $folder;

                        if (!file_exists($targetDirLocal)) {
                            continue;
                        }
                        $fileSPLObjects = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($targetDirLocal),
                            RecursiveIteratorIterator::SELF_FIRST
                        );
                        $arrImages      = [];

                        $records = $this->BDb->many_as_array($this->FCom_Core_Model_MediaLibrary->orm()
                            ->select(['folder', 'subfolder', 'file_name'])
                            ->where('folder', $folder)->find_many());

                        /** @var SplFileInfo $fileSPLObject */
                        foreach ($fileSPLObjects as $fullFileName => $fileSPLObject) {
                            if ($fileSPLObject->isFile() && $fileSPLObject->isReadable() && @getimagesize($fullFileName)) {
                                $fileName  = $fileSPLObject->getFilename();
                                $path      = $fileSPLObject->getPath();
                                $subFolder = null;
                                if ($path != $targetDirLocal) {
                                    $path      = str_replace('\\', '/', $path);
                                    $subFolder = trim(str_replace($targetDirLocal . '/', '', $path));
                                    $subFolder = ltrim($subFolder, '/');
                                }
                                $tmp = ['folder' => $folder, 'subfolder' => $subFolder, 'file_name' => $fileName];
                                if (!in_array($tmp, $records)) {
                                    array_push($arrImages, $tmp);
                                }
                            }
                        }
                        foreach ($arrImages as $arr) {
                            $filePath = $targetDirLocal . '/';
                            if($arr['subfolder']){
                                $filePath .= $arr['subfolder'] . '/';
                            }
                            $filePath .= $arr['file_name'];

                            $arr['file_size'] = filesize($filePath);
                            $attModel->create($arr)->save();
                        }
                    }
                    $this->BResponse->json(['status' => 'success']);
                } catch(Exception $e) {
                    $this->BResponse->json(['status' => 'error', 'messages' => $e->getMessage()]);
                }
                break;
        }
    }


    public function collectUploadConfig()
    {
        $modules = $this->BModuleRegistry->getAllModules();

        foreach ($modules as $module) {
            /** @var BModule $module */
            if (!$module || !$module instanceof BModule) {
                return;
            }
            $area = $module->area;
            if (!empty($module->areas[$area]['uploads'])) {
                $this->_uploadConfigs = $this->BUtil->arrayMerge($this->_uploadConfigs,
                    (array)$module->areas[$area]['uploads']);
            }
        }
    }

    /**
     * @param null $configId
     * @return array
     */
    public function uploadConfig($configId = null)
    {
        $uploadConfig = [];
        if(empty($this->_uploadConfigs)){
            $this->collectUploadConfig();
        }
        if (isset($this->_uploadConfigs[$configId])) {
            $uploadConfig = $this->_processUploadConfig($this->_uploadConfigs[$configId], $configId);
        } else if(null === $configId){
            foreach ($this->_uploadConfigs as $id => $config) {
                $uploadConfig[$id] = $this->_processUploadConfig($config, $id);
            }
        }
        return $uploadConfig;
    }

    /**
     * @param array $uploadConfig
     * @param string $configId
     * @return mixed
     */
    protected function _processUploadConfig($uploadConfig, $configId)
    {
        $uploadConfig['type'] = $configId;
        $uploadConfig['label'] = ucwords(str_replace(['-', '_'], ' ', $configId));
        if (isset($uploadConfig['filetype'])) {
            $uploadConfig['filetype_regex'] = '/(\\.|\\/)(' . str_replace([','], '|',
                    $uploadConfig['filetype']) . ')$/i';
        }

        if (isset($uploadConfig['permission'])) {
            $canUpload                  = $this->FCom_Admin_Model_User->sessionUser()
                                                                      ->getPermission($uploadConfig['permission']);
            $uploadConfig['can_upload'] = $canUpload;
        }
        return $uploadConfig;
    }

    /**
     * @param string $folder
     * @return mixed
     */
    protected function _parseFolder($folder)
    {
        if (strpos($folder, '{random}') !== false) {
            $random = 'storage/' . $this->BConfig->get('core/storage_random_dir');
            $folder = str_replace('{random}', $random, $folder);
        }
        return $folder;
    }
}
