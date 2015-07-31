<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract class FCom_Admin_Controller_Abstract_Report extends FCom_Admin_Controller_Abstract_GridForm
{
    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $view = $args['page_view'];
        $view->set('actions', []);
    }

    /**
     * Get filters from either request, or personalization
     *
     * @return array
     */
    protected function _getFilters()
    {
        $request = $this->BRequest->request();
        if (!empty($request['hash'])) {
            $request = (array)$this->BUtil->fromJson(base64_decode($request['hash']));
        } elseif (!empty($request['filters'])) {
            $request['filters'] = $this->BUtil->fromJson($request['filters']);
        }

        /** @var FCom_Core_View_BackboneGrid $view */
        if (!empty($request['filters'])) {
            return $request['filters'];
        } else {
            $pers = $this->FCom_Admin_Model_User->personalize();
            $gridId = $this->origClass();
            $persState = !empty($pers['grid'][$gridId]['state']) ? $pers['grid'][$gridId]['state'] : [];
            if (!empty($persState['filters'])) {
                return $persState['filters'];
            }
        }

        return [];
    }
}