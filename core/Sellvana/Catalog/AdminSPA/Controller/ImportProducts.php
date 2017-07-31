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

    public function action_upload__POST()
    {
        $helperCtrlr = $this->FCom_Admin_Controller_MediaLibrary;
        $catalog = $this->BModuleRegistry->module('Sellvana_Catalog');
        if(isset($catalog->areas[$catalog->area],
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
        } catch (\Exception $e) {
            $result = ['status'=>'error'];
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
        return $this->BResponse->json($this->Sellvana_Catalog_ProductsImport->config(['status' => 'stopped'], true));
    }

    public function action_status()
    {
        return $this->BResponse->json($this->getCurrentImportConfig());
    }

    /**
     * @return array|bool|mixed
     */
    protected function getCurrentImportConfig()
    {
        return $this->Sellvana_Catalog_ProductsImport->config();
    }
}