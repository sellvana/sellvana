<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * IndexTank API factory class
 */
class Sellvana_IndexTank_RemoteApi extends BClass
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
        //$this->BApp->m()->autoload('lib');
    }

    /**
     * Initialization of IndexTank API service
     * @return IndexTank_API object
     */
    public function service()
    {
        if (empty($this->indextank)) {
            $apiUrl = $this->BConfig->get('modules/Sellvana_IndexTank/api_url');
            $this->indextank = new Indextank_Api($apiUrl);
        }
        return $this->indextank;
    }
}
