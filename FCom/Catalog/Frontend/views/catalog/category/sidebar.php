<?
$category = $this->category ? $this->category : BApp::i()->get('current_category');
$parent = FCom_Catalog_Model_Category::load($category->parent_id);
$siblings = $category->siblings();
$children = $category->children();
?>

<div class="block block-filter">
   <header class="block-title">
        <strong class="title"><?= BLocale::_("Browse By") ?></strong>
    </header>
    <div class="block-content">

        <dl id="narrow-by-list2">
            <dt class="last odd"><?= BLocale::_("Category") ?></dt>

            <dd class="last odd">
                <ol>
                    <li><a href="<?=BApp::href('catalog/search').'?'.BRequest::rawGet()?>"><?= BLocale::_("All categories") ?></a></li>
                    <?php if ($parent) :?>
                        <li><a href="<?=$this->q($parent->url())?>"><?=$this->q($parent->node_name)?></a></li>
                    <?php endif; ?>

                        <li style="padding-left: 10px;"><b><?=$this->q($category->node_name)?></b></li>
                        <?php foreach ($children as $c): ?>
                            <li style="padding-left: 20px;">
                                <a href="<?=$this->q($c->url())?>"><?=$this->q($c->node_name)?></a>
                            </li>
                        <?php endforeach; ?>
                    <?php foreach ($siblings as $c): ?>
                            <li style="padding-left: 10px;">
                                <a href="<?=$this->q($c->url())?>"><?=$this->q($c->node_name)?></a>
                            </li>
                    <?php endforeach; ?>
                </ol>
            </dd>

        </dl>

    </div>
</div>
