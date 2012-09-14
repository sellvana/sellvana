<?php
    $user = FCom_Admin_Model_User::sessionUser();
    $modulesNotification = array();
    $this->hook('hook_modules_notification', array('modulesNotification' => &$modulesNotification));
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

<?php if ($modulesNotification): ?>
                <li class="sup-updates"><a href="<?=BApp::href('market/index')?>"><span class="icon"></span><span class="title">Notifications &nbsp;<em class="count"><?= count($modulesNotification)?></em></span></a>
                    <ul class="sub-section" style="width:200px">
                    <?php foreach($modulesNotification as $notifyTitle => $notifications): ?>
                            <li><a style="text-decoration: none; font-weight: bold; color:black;"><?=  strtoupper($notifyTitle)?></a></li>

                            <?php foreach($notifications as $notify): ?>
                                <?php if ($notify->url): ?>
                                    <li><a href="<?=BApp::href($notify->url)?>" title="<?=$notify->text?>"><?=$notify->module?></a></li>
                                <?php else:?>
                                    <li><?=$notify->module?>></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
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