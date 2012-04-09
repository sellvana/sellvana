<?php

class FCom_IndexTank_Api extends BClass
{
    /**
    * Indextank Service instance
    *
    * @var Indextank_API
    */
    protected $_indextank;

    protected $_api_url = '';

    public function __construct()
    {
        //BApp::m()->autoload('lib');

        require_once __DIR__.'/lib/indextank.php';

        $this->_api_url = BConfig::i()->get('modules/FCom_IndexTank/api_url');
    }

    public function service()
    {
        if (empty($this->_indextank)) {
            $this->_indextank = new Indextank_Api($this->_api_url);
        }
        return $this->_indextank;
    }
}