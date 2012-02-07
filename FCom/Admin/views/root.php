<?php
    $baseHref = BApp::url('FCom_Admin');
    $user = FCom_Admin_Model_User::sessionUser();
?>
<!DOCTYPE html>
<html>
<head>
    <?php echo $this->hook('head') ?>
    <script>
window.appConfig = {
    baseHref: '<?php echo $baseHref ?>'
}
    </script>
</head>
<body class="<?php echo $this->bodyClass ?>">
<?php if (FCom_Admin_Model_User::i()->isLoggedIn()): ?>
    <div id="root-layout" class="adm-wrapper">
        <div class="ui-layout-north">
		    <header class="adm-topbar">
			    <span class="adm-logo">Denteva Admin</span>
			    <nav class="sup-links">
				    <ul>
					    <li class="sup-quicksearch"><a href="#"><span class="icon"></span><span class="title">Quicksearch</span></a>
						    <form action="#" method="post" class="sub-section">
							    <fieldset>
								    <ul class="form-list">
									    <li>
										    <select>
											    <option value="#">Customers</option>
											    <option value="#">Products</option>
											    <option value="#">Orders</option>
										    </select>
									    </li>
									    <li><input type="text" name=""/></li>
								    </ul>
								    <input type="submit" value="Search" class="btn st2 sz2"/>
							    </fieldset>
						    </form>
					    </li>
					    <li class="sup-shortcuts"><a href="#"><span class="icon"></span><span class="title">Shortcuts</span></a>
                            <ul class="sub-section">
                                <li><a href="<?php echo BApp::m('FCom_Catalog')->baseHref()?>/products/form/">New Product</a></li>
                                <li><a href="<?php echo BApp::m('FCom_Catalog')->baseHref()?>/categories/form/">New Company</a></li>
                                <li><a href="<?php echo $baseHref?>/users/form/">New User</a></li>
                            </ul>
                        </li>
					    <li class="sup-updates"><a href="#"><span class="icon"></span><span class="title">Updates &nbsp;<em class="count">10</em></span></a></li>
					    <li class="sup-account"><a href="#"><span class="icon"></span><span class="title"><?php echo $this->q($user->fullname()) ?></span></a>
						    <ul class="sub-section">
							    <li><a href="<?php echo $baseHref ?>/my_account">My Account</a></li>
							    <li><a href="<?php echo $baseHref ?>/reports">My Reports</a></li>
							    <li><a href="<?php echo $baseHref ?>/logout">Log Out</a></li>
						    </ul>
					    </li>
				    </ul>
			    </nav>
			    <strong class="adm-group-title"><?php echo $this->title ? $this->q($this->title) : '&nbsp;' ?></strong>
		    </header>
        </div>
        <div class="ui-layout-west">
	        <section class="adm-nav-bg"></section>
            <nav class="adm-nav">
		        <?=$this->renderNodes() ?>
	        </nav>
        </div>
        <div class="adm-middle ui-layout-center"><?php echo $this->hook('main') ?></div>
    </div>
<script>
/*
head(function() {
    $('#root-layout').layout({
        north__spacingOpen:0,
        north__resizable:false,
        west__spacingOpen:0
    });
});
*/
</script>
<?php else: ?>
    <div id="root-layout" class="adm-wrapper">
        <?php echo $this->hook('main') ?>
    </div>
<?php endif ?>
</body>
</html>
