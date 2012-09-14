<?php
    $user = FCom_Admin_Model_User::sessionUser();
    $modulesToUpdate = array();
    $this->hook('find_modules_to_update', array('modulesToUpdate' => &$modulesToUpdate));
?>
<div class="ui-layout-north">
    <header class="adm-topbar">
        <nav class="adm-nav">
            <a href="#" class="start">
                <span class="adm-logo">Fulleron Admin</span>
            </a>
            <?php echo $this->renderNodes() ?>
        </nav>

        <nav class="sup-links">
            <ul>
<?php if (!empty($this->_quickSearches)): ?>
                <li class="sup-quicksearch"><a href="#"><span class="icon"></span><span class="title">Quicksearch</span></a>
                    <form action="#" method="post" class="sub-section">
                        <fieldset>
                            <ul class="form-list">
                                <li>
                                    <select>
<?php foreach ($this->_quickSearches as $qs): ?>
                                        <option value="<?php echo $this->q($qs['href']) ?>"><?php echo $this->q($qs['label']) ?></option>
<?php endforeach ?>
                                    </select>
                                </li>
                                <li><input type="text" name=""/></li>
                            </ul>
                            <input type="submit" value="Search" class="btn st2 sz2"/>
                        </fieldset>
                    </form>
                </li>
<?php endif ?>
<?php if (!empty($this->_shortcuts)): ?>
                <li class="sup-shortcuts"><a href="#"><span class="icon"></span><span class="title">Shortcuts</span></a>
                    <ul class="sub-section">
<?php foreach ($this->_shortcuts as $sc): ?>
                        <li><a href="<?php echo $this->q($sc['href']) ?>"><?php echo $this->q($sc['label']) ?></a></li>
<?php endforeach ?>
                    </ul>
                </li>
<?php endif ?>

<?php if ($modulesToUpdate): ?>
                <li class="sup-updates"><a href="<?=BApp::href('market/index')?>"><span class="icon"></span><span class="title">Updates &nbsp;<em class="count"><?= count($modulesToUpdate)?></em></span></a>
                    <ul class="sub-section" style="width:200px">
                        <?php foreach($modulesToUpdate as $mod): ?>
                        <li><a href="<?=BApp::href('market/form')?>?id=<?=$mod->id?>"><?=$mod->mod_name?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
<?php endif; ?>
                <li class="sup-account"><a href="#"><span class="icon"></span><span class="title"><?php echo $this->q($user->fullname()) ?></span></a>
                    <ul class="sub-section">
                        <li><img src="<?=BUtil::gravatar($user->email)?>" style="margin:3px 13px"/></li>
                        <li><a href="<?php echo BApp::href('/my_account')?>">My Account</a></li>
                        <li><a href="<?php echo BApp::href('/reports')?>">My Reports</a></li>
                        <li><a href="<?php echo BApp::href('/logout')?>">Log Out</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
        <strong class="adm-group-title"><?php echo $this->title ? $this->q($this->title) : '&nbsp;' ?></strong>
    </header>
</div>