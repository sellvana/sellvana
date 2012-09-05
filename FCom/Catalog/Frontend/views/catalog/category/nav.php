<?php
if (BConfig::i()->get('modules/FCom_Frontend/nav_top/type') == 'categories_root') {
    $rootId = BConfig::i()->get('modules/FCom_Frontend/nav_top/root_category');
    if (!$rootId){
        $rootId = 1;
    }
    $categories = FCom_Catalog_Model_Category::i()->orm()->where('parent_id', $rootId)->find_many();
} else {
    $categories = FCom_Catalog_Model_Category::i()->orm()->where('top_menu', 1)->find_many();
}
?>

<?php foreach($categories as $cat): ?>
    <li><a href="<?=Bapp::href($cat->url_path)?>"><?=$cat->node_name?><em class="icon"></em></a></li>
<?php endforeach; ?>