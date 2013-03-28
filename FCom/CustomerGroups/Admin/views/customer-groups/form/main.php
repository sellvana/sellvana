<?php
/* @var $this FCom_Admin_View_Default */

$model = $this->model;

?>

<div>
    <header class="adm-main-header"><?=BLocale::_("Customer Group Details");?></header>
    <div class="customer-group-details">
        <fieldset>
            <h3 class="form-group-title"><?php echo BLocale::_("Group Details");?></h3>
            <ul class="form-list">
                <li class="label-l">
                    <label for="model_title"><?php echo BLocale::_("Group Title");?></label>
                    <input type="text" id="model_title" name="model[title]"
                           value="<?php echo $this->q($model->title); ?>">
                </li>
                <li class="label-l">
                    <label for="model_code"><?php echo BLocale::_("Group Code");?></label>
                    <input type="text" id="model_code" name="model[code]"
                           value="<?php echo $this->q($model->code); ?>">
                </li>
            </ul>
        </fieldset>
    </div>
</div>