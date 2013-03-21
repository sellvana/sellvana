<?php
$category = $this->category ? $this->category : BApp::i()->get('current_category');
$parent = false;
$siblings = false;
if ($category) {
    if ($category->parent_id) {
        $parent = FCom_Catalog_Model_Category::load($category->parent_id);
        $siblings = $category->siblings();
    }

    $children = $category->children();
}
?>

<section class="block block-filter">
   <header class="block-title"><span class="title"><?= BLocale::_("Narrow Results");?></span></header>
<form class="block-content" action="" method="get">
   <section class="block-sub">
	<header class="block-sub-title"><span class="title"><?= BLocale::_("Categories") ?></span></header>
        <a href="<?=BApp::href('catalog/search').'?'.BRequest::rawGet()?>">&lt; <?= BLocale::_("All categories") ?></a>
            <ul>
            <?php if($category) :?>
                    <?php if ($parent && $parent->node_name) :?>
                        <li style="padding-left: 10px;"><a href="<?=$this->q($parent->url().'?'.BRequest::rawGet())?>">&lt; <?=$this->q($parent->node_name)?></a></li>
                    <?php endif; ?>

                        <li style="padding-left: 20px;"><b><?=$this->q($category->node_name)?></b></li>
                        <?php foreach ($children as $c): ?>
                            <li style="padding-left: 30px;">
                                <a href="<?=$this->q($c->url().'?'.BRequest::rawGet())?>"><?=$this->q($c->node_name)?></a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif ?>
                    <?php if($siblings) :?>
                        <?php foreach ($siblings as $c): ?>
                            <li style="padding-left: 20px;">
                                <a href="<?=$this->q($c->url().'?'.BRequest::rawGet())?>"><?=$this->q($c->node_name)?></a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
            </ul>

   </section>

    <?=$this->hook('custom-fields-filters', array('category' => $category));?>


</form>
</section>

