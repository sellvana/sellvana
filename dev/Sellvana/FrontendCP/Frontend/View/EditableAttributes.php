<?php

/**
 * Class Sellvana_FrontendCP_Frontend_View_EditableAttributes
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */

class Sellvana_FrontendCP_Frontend_View_EditableAttributes extends FCom_Core_View_Abstract
{
    public function render(array $args = [], $retrieveMetaData = true)
    {
        if (!$this->FCom_Admin_Model_User->isLoggedIn()) {
            return '';
        }
        if (!$this->FCom_Admin_Model_User->sessionUser()->getPermission('frontendcp/edit')) {
            return '';
        }

        $data = [
            'mercury' => $this->get('type'),
            'id' => $this->get('entity') . '--' . $this->get('id') . '--' . $this->get('field'),
            'entity' => $this->get('entity'),
            'model_id' => $this->get('id'),
            'field' => $this->get('field'),
        ];
        $attrs = ['id="' . $data['id'] . '"'];
        foreach ($data as $k => $v) {
            $attrs[] = 'data-' . $k . '="' . htmlspecialchars($v) . '"';
        }

        return join(' ', $attrs);
    }
}
