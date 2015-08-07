<?php defined('BUCKYBALL_ROOT_DIR') || die();

abstract class FCom_Admin_Controller_Abstract_Report extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_periodTypes = [
        'day' => 'Day',
        'week' => 'Week',
        'month' => 'Month',
        'quarter' => 'Quarter',
        'year' => 'Year'
    ];

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $view = $args['page_view'];
        $view->set('actions', []);
    }

    /**
     * @param array $filter
     * @param string $val
     * @param BORM $orm
     */
    public function periodTypeCallback($filter, $val, $orm)
    {
        $field = 'o.create_at';
        switch ($val) {
            case 'year':
                $expr = "YEAR({$field})";
                break;
            case 'quarter':
                $expr = "CONCAT(YEAR({$field}), '-', QUARTER({$field}))";
                break;
            case 'month':
                $expr = "DATE_FORMAT({$field}, '%Y-%m')";
                break;
            case 'week':
                $expr = "DATE_FORMAT({$field}, '%Y-%u')";
                break;
            case 'day':
            default:
                $expr = "DATE_FORMAT({$field}, '%Y-%m-%d')";
                break;
        }

        $orm->group_by_expr($expr);
        $orm->select_expr($expr, 'period');
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