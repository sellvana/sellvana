<!DOCTYPE html>
<html>
<head>
    <?php echo $this->hook('head') ?>
</head>
<body class="<?php echo $this->bodyClass ?>">
	<div class="adm-wrapper">
        <?php if (FCom_Admin_Model_User::i()->isLoggedIn()): ?>
		<header class="adm-topbar">
			<span class="adm-logo">Denteva Admin</span>
			<nav class="sup-links">
				<ul>
					<li class="sup-updates"><a href="#">Updates</a></li>
					<li class="sup-shortcuts"><a href="#">Shortcuts</a></li>
					<li class="sup-account"><a href="#">Scott Walsh</a></li>
                    <li><a href="<?php echo BApp::m('FCom_Admin')->baseHref()?>/logout">Log Out</a></li>
				</ul>
			</nav>
		</header>
	    <section class="adm-nav-bg"></section>
        <nav class="adm-nav">
		    <?=$this->renderNodes() ?>
	    </nav>
        <?php endif ?>
		<?php if (FCom_Admin_Model_User::i()->isLoggedIn()): ?><div class="adm-middle"><?php endif ?>
			<?php echo $this->hook('main') ?>
		<?php if (FCom_Admin_Model_User::i()->isLoggedIn()): ?></div><?php endif ?>
	</div>
</body>
</html>