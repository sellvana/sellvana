<form action="<?=BApp::href('customer/address')?>" method="post">
    <?php if (!empty($this->address->id)): ?>
        <input type="hidden" name="id" value="<?=$this->address->id?>">
    <?php endif; ?>
    <fieldset>
      <div class="row-fluid">
        <div class="control-group span6">
          <label for="#" class="control-label required"><?= BLocale::_("First name") ?></label>
          <div class="controls">
            <input type="text" name="firstname" value="<?=$this->address->firstname?>">
          </div>
        </div>
        <div class="control-group span6">
          <label for="#" class="control-label required"><?= BLocale::_("Last name") ?></label>
          <div class="controls">
            <input type="text" name="lastname" value="<?=$this->address->firstname?>">
          </div>
        </div>
      </div>
      <div class="control-group">
        <label for="#" class="control-label required"><?= BLocale::_("Street 1") ?></label>
        <div class="controls">
          <input type="text" name="street1" value="<?=$this->address->street1?>">
        </div>
      </div>
      <div class="control-group">
        <label for="#" class="control-label"><?= BLocale::_("Street 2") ?></label>
        <div class="controls">
          <input type="text" name="street2" value="<?=$this->address->street2?>">
        </div>
      </div>
      <div class="row-fluid">
        <div class="control-group span6">
          <label for="#" class="control-label required"><?= BLocale::_("City") ?></label>
          <div class="controls">
            <input type="text" name="city" value="<?=$this->address->city?>">
          </div>
        </div>
        <div class="control-group span6">
          <?=$this->view('geo/embed')?>    
          <script>
              require(['jquery'], function($) {
                  $(function() {
                      $('.geo-country').geoCountryRegion({country:'<?=$this->address->country?>', region:'<?=$this->address->region?>'});
                  })
              })
          </script>
          <label for="#" class="control-label required"><?= BLocale::_("Country") ?></label>
          <div class="controls">
            <select class="geo-country select2" name="country" id="country">
                <option value=""><?= BLocale::_("Select an option") ?></option>
            </select>
          </div>
        </div>
      </div>
      <div class="row-fluid">
        <div class="control-group span6">
          <label for="#" class="control-label required"><?= BLocale::_("State/Region") ?></label>
          <div class="controls">
            <select class="geo-region required select2" name="region" id="region">
                <option value=""><?= BLocale::_("Select an option") ?></option>
            </select>
            <input type="text" class="geo-region" name="region" />
          </div>
        </div>
        <div class="control-group span6">
          <label for="#" class="control-label required"><?= BLocale::_("Zip") ?></label>
          <div class="controls">
            <input type="text" name="postcode" value="<?=$this->address->postcode?>">
          </div>
        </div>
      </div>
      <div class="checkbox">
        <label for="address_default_shipping">
          <input type="checkbox" id="address_default_shipping" name="address_default_shipping" value="1"
          <?=$this->default_shipping == 1?'checked':''?>>
          <?= BLocale::_("Set as default shipping address") ?>
        </label>
      </div>
      <div class="checkbox">
        <label for="address_default_shipping">
          <input type="checkbox" name="address_default_billing" value="1"
           <?=$this->default_billing == 1?'checked':''?>>
          <?= BLocale::_("Set as default billing address") ?>
        </label>
      </div>
      <input type="submit" class="btn btn-primary" value="<?= BLocale::_("Save address") ?>">
    </fieldset>
</form>
