<?php
$categories = FCom_Catalog_Model_Category::i()->orm()->where('top_menu', 1)->find_many();
?>

<?php foreach($categories as $cat): ?>
    <li><a href="<?=Bapp::href($cat->url_path)?>"><?=$cat->node_name?><em class="icon"></em></a></li>
<?php endforeach; ?>