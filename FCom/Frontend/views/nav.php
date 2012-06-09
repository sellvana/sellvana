<?php if (BConfig::i()->get('modules/FCom_Frontend/nav_top/type') == 'cms'): ?>
                        <?=$this->view('cms/nav')->set('root_id', BConfig::i()->get('modules/FCom_Frontend/nav_top/root_cms'))?>
                    <?php elseif (BConfig::i()->get('modules/FCom_Frontend/nav_top/type') == 'categories') :?>
                        <?=$this->view('category/nav')->set('root_id', BConfig::i()->get('modules/FCom_Frontend/nav_top/root_category'))?>
                    <?php else: ?>
                        Custom menu here
                    <?php endif; ?>