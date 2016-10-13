<?php

/**
 * Created by pp
 *
 * Class FCom_ApiServer_Controller_V1_Import
 *
 * @project sellvana_core
 *
 * @property FCom_Core_ImportExport $FCom_Core_ImportExport
 */
class FCom_ApiServer_Controller_V1_Import
    extends FCom_ApiServer_Controller_Abstract
{
    /**
     *
     */
    public function action_index()
    {
        /** @var FCom_Core_ImportExport $exporter */
        $exporter = $this->FCom_Core_ImportExport;
        $fromFile = fopen('php://input', 'r');
        $exporter->importFile($fromFile);
        $this->created(['Done']);
    }
}
