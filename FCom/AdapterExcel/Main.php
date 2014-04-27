<?php

class FCom_AdapterExcel_Main extends BClass
{
    public function init()
    {
        require_once '../Classes/PHPExcel/IOFactory.php';
    }

    public function read( $filename )
    {
        $inputFileType = PHPExcel_IOFactory::identify( $inputFileName );
        $objReader = PHPExcel_IOFactory::createReader( $inputFileType );
        $objPHPExcel = $objReader->load( $inputFileName );
    }
}
