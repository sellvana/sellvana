<?php
if (!$this->root_id){
    $this->root_id = 1;
}
$navlist = FCom_Cms_Model_Nav::i()->orm()->where('parent_id', $this->root_id)->find_many();
?>
<?php foreach($navlist as $nav):?>
    <li><a href="<?=$nav->getUrl()?>"><?=$nav->node_name?></a></li>
<?php endforeach; ?>