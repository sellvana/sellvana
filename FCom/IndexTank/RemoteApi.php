<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * IndexTank API factory class
 */
class FCom_IndexTank_RemoteApi extends BClass
{
    /**
    * Indextank Service instance
    *
    * @var Indextank_API
    */
    protected $indextank = null;

    /**
     * Load IndexTank library
     */
    public function __construct()
    {
        include_once __DIR__ . '/lib/indextank.php';
        //BApp::m()->autoload('lib');
    }

    /**
     * Initialization of IndexTank API service
     * @return IndexTank_API object
     */
    public function service()
    {
        if (empty($this->indextank)) {
            $apiUrl = BConfig::i()->get('modules/FCom_IndexTank/api_url');
            $this->indextank = new Indextank_Api($apiUrl);
        }
        return $this->indextank;
    }
}