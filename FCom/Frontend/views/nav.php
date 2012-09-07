<?php if (BConfig::i()->get('modules/FCom_Frontend/nav_top/type') == 'cms'): ?>
    <?=$this->view('cms/nav')->set('root_id', BConfig::i()->get('modules/FCom_Frontend/nav_top/root_cms'))?>
<?php elseif (BConfig::i()->get('modules/FCom_Frontend/nav_top/type') == 'categories_root') :?>
    <?=$this->view('catalog/category/nav')?>
<?php elseif (BConfig::i()->get('modules/FCom_Frontend/nav_top/type') == 'categories_custom') :?>
    <?=$this->view('catalog/category/nav')?>
<?php else: ?>
    <li>Custom menu</li>
<?php endif; ?>