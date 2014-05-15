<?php

/**
 * Created by pp
 *
 * @project sellvana_core
 */
class FCom_ApiServer_Controller_V1_Export
    extends FCom_ApiServer_Controller_Abstract
{
    /**
     * Export data
     *
     * There must be logged in user for this to work.
     */
    public function action_index()
    {
        /** @var FCom_Core_ImportExport $exporter */
        $exporter = FCom_Core_ImportExport::i();
        $toFile = fopen('php://output', 'w');
        //header("Content-Type: application/json");
        //header('Status: 200');
        $this->ok(); // this doesn't seem right, but if first export writes to output, response is parsed as html in browser
        $exporter->export([], $toFile);
    }
}