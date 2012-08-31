<?
$category = $this->category ? $this->category : BApp::i()->get('current_category');
$children = $category->children();
?>
<?php if ($children): ?>
<div class="block block-filter">
   <header class="block-title">
        <strong class="title"><?= BLocale::_("Browse By") ?></strong>
    </header>
    <div class="block-content">
<?php if ($children): ?>
        <dl id="narrow-by-list2">
            <dt class="last odd"><?= BLocale::_("Category") ?></dt>
            <dd class="last odd">
                <ol>
<?php foreach ($children as $c): ?>
                    <li>
                        <a href="<?=$this->q($c->url())?>"><span class="count"><?=(int)$c->num_products?></span><?=$this->q($c->node_name)?></a>
                    </li>
<?php endforeach ?>
                </ol>
            </dd>
        </dl>
<?php endif ?>
    </div>
</div>
<?php endif ?>