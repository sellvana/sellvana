<div style="margin-top:100px; margin-left:200px">
    <form method="post" action="<?=BApp::m('FCom_Admin')->baseHref()?>/login">
        <fieldset>
            <input type="text" name="login[username]">
            <input type="password" name="login[password]">
            <input type="submit" name="Login">
        </fieldset>
    </form>
</div>