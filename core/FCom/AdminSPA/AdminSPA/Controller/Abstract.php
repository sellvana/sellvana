<?php

/**
 * Class FCom_AdminSPA_AdminSPA_Controller_Abstract
 *
 * @property FCom_AdminSPA_AdminSPA FCom_AdminSPA_AdminSPA
 */
abstract class FCom_AdminSPA_AdminSPA_Controller_Abstract extends FCom_Admin_Controller_Abstract
{
    const
        CONFIG = 'config',
        GRID = 'grid',
        ID = 'id',
        DATA = 'data',
        DATA_URL = 'data_url',
        COLUMNS = 'columns',
        ROW_SELECT = 'row-select',
        FILTERS = 'filters',
        PAGER = 'pager',
        EXPORT = 'export',
        ACTIONS = 'actions',
        BULK_ACTIONS = 'bulk_actions',
        PAGE_ACTIONS = 'page_actions',
        PANEL_ACTIONS = 'panel_actions',
        TITLE = 'title',
        FORM = 'form',
        NAME = 'name',
        LABEL = 'label',
        TYPE = 'type',
        OPTIONS = 'options',
        HIDDEN = 'hidden',
        DATACELL_TEMPLATE = 'datacell_template',
        DATACELL_COMPONENT = 'datacell_component',
        FIELDS = 'fields',
        DEFAULT_FIELD = 'default',
        TAB = 'tab',
        TABS = 'tabs',
        MODEL = 'model',
        WIDTH = 'width',
        MULTIPLE = 'multiple',
        BUTTON_CLASS = 'button_class',
        LINK = 'link',
        GROUP = 'group',
        DESKTOP_GROUP = 'desktop_group',
        MOBILE_GROUP = 'mobile_group',
        REQUIRED = 'required',
        VALIDATE = 'validate',
        INPUT_TYPE = 'input_type',
        NOTES = 'notes',
        THUMB_URL = 'thumb_url',
        I18N = 'i18n',
        EDIT = 'edit',
        STATE = 'state'
    ;

    public function authenticate($args = [])
    {
        $result = parent::authenticate();
        if (!$result) {
//            $this->BResponse->header([
//                "{$this->BRequest->serverProtocol()} 401 Not authorized",
//                "Status: 401 Not authorized",
//            ]);
            $this->addMessage('Session expired, authorization required', 'error');
            $this->addResponses(['_login' => true]);
            $this->respond();
            return false;
        }
        return $result;
    }

    public function onBeforeDispatch()
    {
        if ($this->BRequest->csrf()) {
            $this->addMessage('Session token expired, please try again', 'warning');
            $this->addResponses(['_csrf_token' => true]);
            if (!$this->BRequest->post('_last_try')) {
                $this->addResponses(['_retry' => true]);
            }
            $this->addResponses(['_request' => $this->BRequest->post()]);
            $this->respond();
            return false;
            #$this->BResponse->status(403, 'Possible CSRF detected', 'Possible CSRF detected');
        }
        return parent::onBeforeDispatch();
    }

    public function onAfterDispatch()
    {

    }

    public function addResponses($updates)
    {
        $this->FCom_AdminSPA_AdminSPA->addResponses($updates);
        return $this;
    }

    public function addMessage($text, $type = null)
    {
        if ($text instanceof Exception) {
//            $text = $text->getMessage();# . "\n" . $text->getTraceAsString();
            $text = $text->getMessage() . "\n" . $text->getTraceAsString();
            if (!$type) {
                $type = 'error';
            }
            $this->error();
        } elseif (is_string($text)) {
            if (!$type) {
                $type = 'info';
            }
        } else {
            throw new BException('Invalid message text type');
        }
        $this->addResponses(['_messages' => [
            [static::TYPE => $type, 'text' => $text],
        ]]);
        return $this;
    }

    public function ok()
    {
        $this->addResponses(['ok' => true]);
        return $this;
    }

    public function error()
    {
        $this->addResponses(['error' => true]);
        return $this;
    }

    public function respond($result = [])
    {
        $result = $this->FCom_AdminSPA_AdminSPA->mergeResponses($result);
        $this->BResponse->json($result);
    }

    public function getActionsGroups($actions, $form = null)
    {
        $actionGroups = [];
        if (!empty($actions['default'])) {
            $def = $actions['default'];
            unset($actions['default']);
        }
        foreach ($actions as &$act) {
            if (!empty($def)) {
                $act = array_merge($def, $act);
            }
            if (!empty($act['if']) && $form) {
                $ifResult = $this->BUtil->arrayGet($form, $act['if']);
                if (!$ifResult) {
                    continue;
                }
            }
            if (empty($act['group']) && empty($act['desktop_group']) && empty($act['mobile_group'])) {
                $act['group'] = $act['name'];
            }
            foreach (['desktop_group', 'mobile_group'] as $g) {
                $group = !empty($act[$g]) ? $act[$g] : (!empty($act['group']) ? $act['group'] : null);
                if (!empty($group)) {
                    if (empty($actionGroups[$g][$group])) {
                        $actionGroups[$g][$group] = $act;
                    } else {
                        $actionGroups[$g][$group]['children'][] = $act;
                    }
                }
            }
        }
        unset($act);
        $result = [];
        if (!empty($actionGroups['desktop_group'])) {
            $result['desktop'] = array_values($actionGroups['desktop_group']);
        }
        if (!empty($actionGroups['mobile_group'])) {
            $result['mobile'] = array_values($actionGroups['mobile_group']);
        }
        return $result;
    }
}