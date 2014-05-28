<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_FrontendCP_Frontend_View_EditableAttributes extends FCom_Core_View_Abstract
{
    public function render(array $args = [], $retrieveMetaData = true)
    {
        if (!FCom_Admin_Model_User::i()->isLoggedIn()) {
            return '';
        }
        if (!FCom_Admin_Model_User::i()->sessionUser()->getPermission('frontendcp/edit')) {
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
