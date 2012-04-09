<?php

class FCom_IndexTank extends BClass
{
    /**
    * Indextank Service instance
    *
    * @var Indextank_API
    */
    protected $_indextank;

    protected $_api_url = '<API URL HERE>';

    static public function bootstrap()
    {
        BApp::m()->autoload('lib');

        $config = BConfig::i()->get('modules/FCom_IndexTank/api');
        $this->_api_url = $config['api_url'];
    }

    public function service()
    {
        if (empty($this->_indextank)) {
            $this->_indextank = new Indextank_Api($this->_api_url);
        }
        return $this->_indextank;
    }
}