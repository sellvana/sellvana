<?php

/**
 * Created by pp
 *
 * @project sellvana_core
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
        $exporter = FCom_Core_ImportExport::i();
        $fromFile = fopen('php://input', 'r');
        $exporter->importFile($fromFile);
        $this->created(['Done']);
    }
}