<!DOCTYPE html>
<html <?php echo $this->getHtmlAttributes() ?>>
<head>
    <?php echo $this->hook('head') ?>
    <script>
window.appConfig = {
    baseHref: '<?php echo BApp::baseUrl(true) ?>'
}
    </script>
</head>
<body class="<?php echo $this->bodyClass ?>">
    <div id="top-message" style="position:fixed; top:10px; left:50%; background:#fff1b7; border:1px solid #ebc27b; border-bottom-color:#deb060; border-radius:3px; -moz-border-radius:3px; -webkit-border-radius:3px; z-index:99999; padding:5px 15px; line-height:1.3em; box-shadow:0 2px 3px #ddd; display:none">
	    Something happened here!
    </div>

<?php if (FCom_Admin_Model_User::i()->isLoggedIn()): ?>

    <div id="root-layout" class="ui-layout-center adm-wrapper">
        <?php echo $this->view('admin/header') ?>
        <div class="adm-middle ui-layout-center" id="main-container"><?php echo $this->hook('main') ?></div>
    </div>
<script>

head(function() {

    var $main = $('.adm-content-box'), $win = $(window), $doc = $(document);
    if ($main.length) {
        $win.resize(function() { $main.height(Math.max($doc.height(), $win.height())-$main.offset().top); }).trigger('resize');
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
