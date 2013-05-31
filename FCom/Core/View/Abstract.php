<?php

class FCom_Core_View_Abstract extends BView
{
    public function messagesHtml($namespace=null)
    {
        $messages = $this->messages;
        if (!$messages && $namespace) {
            $messages = BSession::i()->messages($namespace);
        }
        $html = '';
        if ($messages) {
            $html .= '<ul class="msgs">';
            foreach ($messages as $m) {
                $html .= '<li class="'.$m['type'].'-msg">'.$this->q($m['msg']).'</li>';
            }
            $html .= '</ul>';
        }
        return $html;
    }
}
