<?php
    $user = FCom_Admin_Model_User::sessionUser();
    $loggedIn = FCom_Admin_Model_User::i()->isLoggedIn();
?>
<!DOCTYPE html>
<html ng-app <?php echo $this->getHtmlAttributes() ?>>
<head>
    <?php echo $this->hook('head') ?>
    <script>
window.appConfig = {
    baseHref: '<?php echo BApp::baseUrl(true) ?>'
}
    </script>
</head>
<body class="<?php echo $this->bodyClass ?>">
<div style="position:fixed; top:10px; left:50%; background:#fff1b7; border:1px solid #ebc27b; border-bottom-color:#deb060; border-radius:3px; -moz-border-radius:3px; -webkit-border-radius:3px; z-index:99999; padding:5px 15px; line-height:1.3em; box-shadow:0 2px 3px #ddd;">
	Something happened here!
</div>
<?php if ($loggedIn): ?>
    <div id="root-layout" class="ui-layout-center adm-wrapper">
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
					    <li class="sup-updates"><a href="#"><span class="icon"></span><span class="title">Updates &nbsp;<em class="count">10</em></span></a>
                            <ul class="sub-section" style="width:200px">
                                <li><a href="#">Module update 1</a></li>
                                <li><a href="#">Module update 2</a></li>
                                <li><a href="#">Workflow update 1</a></li>
                                <li><a href="#">Workflow update 2</a></li>
                            </ul>

                        </li>
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
        <div class="adm-middle ui-layout-center" id="main-container"><?php echo $this->hook('main') ?></div>
    </div>
<script>

head(function() {

    var $main = $('.adm-content-box'), $win = $(window);
    if ($main.length) {
        $win.resize(function() { $main.height($win.height()-$main.offset().top); }).trigger('resize');
    }

    //$('#root-layout > .ui-layout-west').width(180).height(1000).resizable({handles:'e'});
    //var bodyLayout = $('body').layout();
/*
    var rootLayout = $('#root-layout').layout({
        north__spacingOpen:0,
        north__resizable:false,
        west__spacingOpen:0
    });

    $('#root-layout > .ui-layout-north')
        .mouseover(function() { rootLayout.allowOverflow(this); })
        .mouseout(function() { rootLayout.resetOverflow(this); });
*/
});

</script>
<?php else: ?>
    <div id="root-layout" class="adm-wrapper">
        <?php echo $this->hook('main') ?>
    </div>
<?php endif ?>
</body>
</html>
