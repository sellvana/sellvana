<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Core_View_Root extends FCom_Core_View_Abstract
{
    protected $_htmlAttr = ['lang' => 'en'];

    public function __construct(array $params, BRequest $req)
    {
        parent::__construct($params);
        $this->addBodyClass(strtolower(trim(preg_replace('#[^a-z0-9]+#i', '-', $req->rawPath()), '-')));
    }

    public function addBodyClass($class)
    {
//BDebug::dump($class);
        $this->body_class = !$this->body_class ? (array)$class
            : array_merge((array)$this->body_class, (array)$class);
        return $this;
    }

    public function getBodyClass()
    {
        return $this->body_class ? join(' ', (array)$this->body_class) : '';
    }

    public function getHtmlAttributes()
    {
        $xmlns = [];
        foreach ($this->_htmlAttr as $a => $v) {
            $xmlns[] = $a . '="' . $this->q($v) . '"';
        }
        return join(' ', $xmlns);
    }

    public function xmlns($ns, $href)
    {
        $this->_htmlAttr['xmlns:' . $ns] = $href;
        return $this;
    }
}
