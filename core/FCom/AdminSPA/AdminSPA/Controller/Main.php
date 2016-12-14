<?php

class FCom_AdminSPA_AdminSPA_Controller_Main extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/');
    }

    public function action_vue_app_js()
    {
        $this->BDebug->mode('PRODUCTION');
        $html = (string)$this->view('js/vue-app-js');
        $script = str_replace(['<script>', '</script>'], '', $html);
        $this->BResponse->setContentType('text/javascript')->set($script);
    }

    public function action_components()
    {
        $this->_getComponent();
    }

    public function action_components__POST()
    {
        $this->_getComponent();
    }

    protected function _getComponent()
    {
        $path = $this->BRequest->param(1) ?: $this->BRequest->param('path', true);
        $path = preg_replace('#[^a-zA-Z0-9_/-]#', '', $path);

        if (!$path) {
            $this->BResponse->status(404, 'Template not found', 'Template not found');
            return;
        }
        $view = $this->view('components/' . $path);
        $args = $this->BRequest->request('args');
        if ($args) {
            $view->set($this->BUtil->fromJson($args));
        }
        $this->BDebug->mode('PRODUCTION');
        $result = $view->render();
        $this->BResponse->set($result);
        return;

        if (!$path) {
            $result = ['error' => true, 'message' => $this->_('Invalid path')];
        } else {
            $view = $this->view('components/' . $path);
            $args = $this->BRequest->request('args');
            if ($args) {
                $view->set($this->BUtil->fromJson($args));
            }
            $result = [
                'template' => $view->render(),
            ];
        }
        $script = 'define([], function() { return ' . $this->BUtil->toJson($result) . ' });';
        $this->BResponse->setContentType('text/javascript')->set($script);
    }
}