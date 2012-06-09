<?php
if (!$this->root_id){
    $this->root_id = 1;
}
$categories = FCom_Catalog_Model_Category::i()->orm()->where('parent_id', $this->root_id)->find_many();
?>

<?php foreach($categories as $cat): ?>
    <li><a href="<?=Bapp::href($cat->url_path)?>"><?=$cat->node_name?></a></li>
<?php endforeach; ?>