<?php
    $m = $this->model;
    $hlp = FCom_Admin_Model_User::i();
?>
<fieldset class="adm-section-group">
    <ul class="form-list">
        <li>
            <h4 class="label">User Name</h4>
            <input type="text" id="model-username" name="model[username]" class="validate[required]" value="<?php echo $this->q($m->username) ?>"/>
        </li>
        <li>
            <h4 class="label">Super Admin?</h4>
            <select name="model[is_superadmin]">
                <?php echo $this->optionsHtml($hlp->fieldOptions('is_superadmin'), $m->is_superadmin) ?>
            </select>
        </li>
        <li>
            <h4 class="label">Role</h4>
            <select name="model[role_id]">
                <option></option>
                <?php echo $this->optionsHtml(FCom_Admin_Model_Role::options(), $m->role_id) ?>
            </select>
        </li>
        <li>
            <h4 class="label">Status</h4>
            <select name="model[status]">
                <?php echo $this->optionsHtml($hlp->fieldOptions('status'), $m->status) ?>
            </select>
        </li>
        <li>
            <h4 class="label">Email</h4>
            <input type="text" id="model-email" name="model[email]" class="validate[required,custom[email]]" value="<?php echo $this->q($m->email) ?>"/>
        </li>
        <li>
            <h4 class="label">Password</h4>
            <input type="password" id="model-password" name="model[password]" value=""/>
        </li>
        <li>
            <h4 class="label">Confirm Password</h4>
            <input type="password" id="model-password_confirm" name="model[password_confirm]" class="validate[equals[model-password]]" value=""/>
        </li>
        <li>
            <h4 class="label">First Name</h4>
            <input type="text" id="model-firstname" name="model[firstname]" class="validate[required]" value="<?php echo $this->q($m->firstname) ?>"/>
        </li>
        <li>
            <h4 class="label">Last Name</h4>
            <input type="text" id="model-lastname" name="model[lastname]" class="validate[required]" value="<?php echo $this->q($m->lastname) ?>"/>
        </li>
    </ul>
</fieldset>