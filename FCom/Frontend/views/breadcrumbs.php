<?
if ($this->crumbs) {
    $crumbs = $this->crumbs;
} elseif ($this->navNode) {
    $crumbs = array('home');
    if (($asc = $this->navNode->ascendants())) {
        foreach ($asc as $a) {
            if (!$a->node_name) continue;
            $crumbs[] = array(
                'href'=>$a->url_href ? BApp::baseUrl().trim('/'.$a->url_href, '/') : null,
                'title'=>$a->node_name,
                'label'=>$a->node_name,
            );
        }
    }
    $crumbs[] = array('label'=>$this->navNode->node_name, 'active'=>true);
}

if (!empty($crumbs)):

foreach ($crumbs as $i=>&$c) {
    if ($c=='home') $c = array('href'=>BApp::href(), 'label'=>'Home', 'li_class'=>'home');
    if (!isset($c['title'])) $c['title'] = $c['label'];
}
unset($c);

?>
    <div class="breadcrumbs">
        <ul>
<?php foreach ($crumbs as $c): ?>
            <li <?=!empty($c['li_class'])?'class="'.$c['li_class'].'"':''?>>
<?php if (!empty($c['href'])): ?><a href="<?=$c['href']?>" <?=!empty($c['title'])?'title="'.$c['title'].'"':''?>><?=$this->q($c['label'])?></a>
<?php else: ?><strong><?=$this->q($c['label'])?></strong>
<?php endif ?>
            </li>
<?php endforeach ?>
        </ul>
    </div>
<?php endif ?>