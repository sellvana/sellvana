<?php

/**
 * IndexTank API factory class
 */
class FCom_IndexTank_Api extends BClass
{
    /**
    * Indextank Service instance
    *
    * @var Indextank_API
    */
    protected $_indextank = null;

    /**
     * Load IndexTank library
     */
    public function __construct()
    {
        include_once 'lib/indextank.php';
        //BApp::m()->autoload('lib');
    }

    /**
     * Initialization of IndexTank API service
     * @return IndexTank_API object
     */
    public function service()
    {
        if (empty($this->_indextank)) {
            $api_url = BConfig::i()->get('modules/FCom_IndexTank/api_url');
            $this->_indextank = new Indextank_Api($api_url);
        }
        return $this->_indextank;
    }
}