<?php

/**
 * Created by pp
 *
 * @project sellvana_core
 */
class FCom_Frontend_Controller_Media extends FCom_Frontend_Controller_Abstract
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
        $this->_allowedFolders[$folder]                      = 1;
        $this->_allowedFolders[$this->_parseFolder($folder)] = 1;

        return $this;
    }

    public function action_upload__POST()
    {
        $this->processGridPost();
    }

    /**
     * @param string $folder
     * @return mixed
     */
    protected function _parseFolder($folder)
    {
        if (strpos($folder, 'media') === false) {
            $folder = 'media/' . trim($folder, '/');
        }

        return $folder;
    }

    /**
     * @return array|mixed|null|string
     * @throws BException
     */
    public function getFolder()
    {
        $folder = $this->BRequest->get('folder');
        if (empty($folder)) {
            throw new BException("Missing upload folder");
        }
        //if (empty($this->_allowedFolders[$folder])) {
        //    throw new BException('Folder ' . $folder . ' is not allowed');
        //}

        $folder = $this->_parseFolder($folder);

        return $folder;
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
        $folder    = !empty($options['folder'])? $options['folder']: $this->getFolder();
        $subfolder = !empty($options['subfolder'])? $options['subfolder']: null;
        $targetDir = $this->FCom_Core_Main->dir($folder);

        $attModel = !empty($options['model_class'])? $options['model_class']: 'FCom_Core_Model_MediaLibrary';
        $attModel = is_string($attModel)? $this->{$attModel}: $attModel;

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
                $blacklistExt = [
                    'php'      => 1,
                    'php3'     => 1,
                    'php4'     => 1,
                    'php5'     => 1,
                    'htaccess' => 1,
                    'phtml'    => 1,
                    'html'     => 1,
                    'htm'      => 1,
                    'js'       => 1,
                    'css'      => 1,
                    'swf'      => 1,
                    'xml'      => 1,
                ];

                //if (isset($uploadConfig['filetype'])) { // todo figure out how to merge processed config file types
                //    $fileTypes = explode(',', $uploadConfig['filetype']);
                //    if (empty($options['whitelist_ext'])) {
                //        $options['whitelist_ext'] = $fileTypes;
                //    } else {
                //        $options['whitelist_ext'] = $this->BUtil->arrayMerge($options['whitelist_ext'], $fileTypes);
                //    }
                //}

                if (!empty($options['whitelist_ext'])) {
                    foreach ($options['whitelist_ext'] as $ext) {
                        unset($blacklistExt[$ext]);
                    }
                }

                //set_time_limit(0);
                //ob_implicit_flush();
                //ignore_user_abort(true);

                $uploads = $_FILES['upload'];
                $rows    = [];
                foreach ($uploads['name'] as $i => $fileName) {

                    if (!$fileName) {
                        continue;
                    }
                    //$associatedProducts = 0;
                    $fileSize           = 0;
                    $message            = '';
                    $ext                = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $fileName           = preg_replace('/[^\w\d_.-]+/', '_', $fileName);

                    if (!empty($uploads['error'][$i])) {
                        $id      = '';
                        $status  = static::ERROR;
                        $message = $uploads['error'][$i];
                    } elseif (!empty($blacklistExt[$ext])){// || !in_array($ext, $options['whitelist_ext'])) {
                        $id      = '';
                        $status  = static::ERROR;
                        $message = 'Illegal file extension';
                    } elseif (preg_match('#\.(gif|jpe?g|png)$#',
                            $fileName) && !@getimagesize($uploads['tmp_name'][$i])
                    ) {
                        $id      = '';
                        $status  = static::ERROR;
                        $message = 'Invalid image uploaded';
                    } elseif (!@move_uploaded_file($uploads['tmp_name'][$i], $targetDir . '/' . $fileName)) {
                        $id      = '';
                        $status  = static::ERROR;
                        $message = 'Unable to save the file';
                    } else {
                        $att = $attModel->loadWhere([
                            'folder'    => (string) $folder,
                            'file_name' => (string) $fileName
                        ]);

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
                            //if (in_array($type, ['product-images', 'product-attachments'])
                            //    && $this->BModuleRegistry->isLoaded('Sellvana_Catalog')
                            //) {
                            //    $associatedProducts = $this->Sellvana_Catalog_Model_ProductMedia
                            //        ->orm()
                            //        ->select_expr('COUNT(*)', 'associated_products')
                            //        ->where('file_id', $att->get('id'))
                            //        ->find_one();
                            //    $associatedProducts = $associatedProducts->get('associated_products');
                            //}
                            $att->set(['file_size' => $uploads['size'][$i], 'update_at' => $this->BDb->now()])
                                ->save();
                        }
                        $this->BEvents->fire(__METHOD__ . ':' . $folder . ':upload', ['model' => $att]);
                        if (!empty($options['on_upload'])) {
                            $this->BUtil->call($options['on_upload'], $att);
                        }
                        $id       = $att->id;
                        $fileSize = $att->file_size;
                        $status   = '';
                    }

                    if ($status == static::ERROR) {
                        $rows[] = [
                            'error'     => $message,
                            'file_name' => $fileName,
                        ];
                    } else {
                        $row    = [
                            'id'        => $id,
                            'file_name' => $fileName,
                            'file_size' => $fileSize,
                            'act'       => $status,
                            'folder'    => $folder,
                            'url'       => $this->BApp->src($folder . '/' . $fileName),
                            'subfolder' => '',
                            //'associated_products' => $associatedProducts
                        ];
                        if(@getimagesize($targetDir . '/' . $fileName)){
                            $row['thumbnail_url'] = $this->BApp->src("resize.php?s=98x&f=" . urlencode($folder . '/' . $fileName));
                        }
                        $rows[] = $row;
                    }
                }
                $this->BResponse->json(['files' => $rows]);
                break;

            case 'edit':
                $id       = $r->post('id');
                $fileName = $r->post('file_name');
                $att      = $attModel->load($id);
                if (!$att) {
                    $this->BResponse->json(['error' => true]);

                    return;
                }
                $oldFileName = $att->file_name;
                if (@rename($targetDir . '/' . $oldFileName, $targetDir . '/' . $fileName)) {
                    $att->set('file_name', $fileName)->save();
                    $this->BEvents->fire(__METHOD__ . ':' . $folder . ':edit', ['model' => $att]);
                    if (!empty($options['on_edit'])) {
                        $this->BUtil->call($options['on_edit'], $att);
                    }
                    $this->BResponse->json(['success' => true]);
                } else {
                    $this->BResponse->json(['error' => true]);
                }
                break;

            case 'delete':
                $files = (array) $r->post('delete');
                foreach ($files as $fileName) {
                    @unlink($targetDir . '/' . $fileName);
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
                    $fileSPLObjects = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($targetDir),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    $arrImages      = [];
                    $records        = $this->BDb
                        ->many_as_array($this->FCom_Core_Model_MediaLibrary->orm()->select([
                                'folder',
                                'subfolder',
                                'file_name'
                            ])->where('folder', $folder)->find_many());

                    foreach ($fileSPLObjects as $fullFileName => $fileSPLObject) {
                        $fileName  = $fileSPLObject->getFilename();
                        $path      = $fileSPLObject->getPath();
                        $subFolder = null;
                        if (is_file($fullFileName) && getimagesize($fullFileName)) {
                            if ($path != $targetDir) {
                                $path      = str_replace('\\', '/', $path);
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
                        $arr['file_size'] = ($arr['subfolder'])? filesize($targetDir . '/' . $arr['subfolder'] . '/' . $arr['file_name']):
                            filesize($targetDir . '/' . $arr['file_name']);
                        $attModel->create($arr)->save();
                    }
                    $this->BResponse->json(['status' => 'success']);
                } catch(Exception $e) {
                    $this->BResponse->json(['status' => 'error', 'messages' => $e->getMessage()]);
                }
                break;
        }
    }

}
