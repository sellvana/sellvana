<?php

/**
 * @property FCom_Admin_Controller_MediaLibrary $FCom_Admin_Controller_MediaLibrary
 * @property Sellvana_Catalog_ProductsImport $Sellvana_Catalog_ProductsImport
 */
class Sellvana_Catalog_AdminSPA_Controller_ImportProducts extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    const UPLOADS_TYPE = 'product-import';

    const UPLOADS_CONFIG = 'uploads';

    const UPLOADS_FOLDER = 'folder';

    public function action_index()
    {

    }

    public function action_config()
    {
        $hlp  = $this->Sellvana_Catalog_ProductsImport;
        $dir  = $hlp->getImportDir();
        $file = $this->BRequest->get('file');
        $info = $hlp->getFileInfo($dir . '/' . $file);
        $info['filename'] = $file;

        $invalidMsg = $this->_(('Invalid file format, please go back and select another file'));
        if (!$info) {
            $this->error()->message($invalidMsg);
            $this->BResponse->status(400, $invalidMsg);

            return $this->respond(['status' => 'error']);
        }

        $info['field_options'] = $hlp->getFieldOptions();
        $info['field_data'] = $hlp->getFieldData();

        return $this->respond($info);
    }

    public function action_config__POST()
    {
        if ($this->Sellvana_Catalog_ProductsImport->config($this->BRequest->post('config'))) {
            return $this->respond(['success' => true, 'status' => 'success']);
        }
        return $this->respond(['status' => 'error']);
    }

    public function action_upload__POST()
    {
        $helperCtrlr = $this->FCom_Admin_Controller_MediaLibrary;
        $catalog     = $this->BModuleRegistry->module('Sellvana_Catalog');
        if (isset($catalog->areas[$catalog->area],
            $catalog->areas[$catalog->area][self::UPLOADS_CONFIG],
            $catalog->areas[$catalog->area][self::UPLOADS_CONFIG][self::UPLOADS_TYPE],
            $catalog->areas[$catalog->area][self::UPLOADS_CONFIG][self::UPLOADS_TYPE][self::UPLOADS_FOLDER])) {
            $helperCtrlr->allowFolder($catalog->areas[$catalog->area][self::UPLOADS_CONFIG][self::UPLOADS_TYPE][self::UPLOADS_FOLDER]);
        }
        try {
            $result = $helperCtrlr->processGridPost(['return' => true, 'do' => 'upload']);
            if ($result) {
                $result = ['files' => $result];
            }
        } catch(\Exception $e) {
            $result = ['status' => 'error'];
            $this->error()->message($e->getMessage());
            $this->BResponse->status(400, $e->getMessage());
        }
        $this->respond($result);
    }

    public function action_start__POST()
    {
        $this->Sellvana_Catalog_ProductsImport->run();
        return $this->BResponse->json($this->getCurrentImportConfig());
    }

    public function action_stop__POST()
    {
        $this->Sellvana_Catalog_ProductsImport->config(['status' => BImport::STATUS_STOPPED], true);
        return $this->respond($this->Sellvana_Catalog_ProductsImport->config());
    }

    public function action_status()
    {
        $result = $this->getCurrentImportConfig();
        if (is_bool($result)) {
            $result = [];
        }
        return $this->respond($result);
    }

    /**
     * @return array|bool|mixed
     */
    protected function getCurrentImportConfig()
    {
        return $this->Sellvana_Catalog_ProductsImport->config();
    }
}