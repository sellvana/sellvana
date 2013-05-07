<?php $m = $this->model; ?>
<?php if('edit' == $m->act):
    $countries = FCom_Geo_Model_Country::i()->options();
    /**
     * Edit
     * **/?>

<script>
    //var custModule = angular.module('custModule', []);

    function CustomerAddressesCtrl($scope) {
        $scope.addresses = <?=BUtil::toJson(BDb::many_as_array($m->addresses()))?>;
        $scope.countries = <?=BUtil::toJson(FCom_Geo_Model_Country::options())?>;
        $scope.regions = <?=BUtil::toJson(FCom_Geo_Model_Region::allOptions())?>;

        $scope.del_ids = [];
        $scope.newId = 0;

        $scope.addAddress = function() {
            $scope.addresses.push({edit_mode:true, id:--$scope.newId});
        }

        $scope.delAddress = function(addr) {
            for (var i = 0, ii = $scope.addresses.length; i<ii; i++) {
                if (addr === $scope.addresses[i]) {
                    $scope.addresses.splice(i, 1);
                }
            }
            if (addr.id>0) {
                $scope.del_ids.push(addr.id);
            }
        }
    }

    function CustomerAddressCtrl($scope) {

    }
</script>
<div ng-app ng-controller="CustomerAddressesCtrl">
    <header class="adm-main-header">Order Info</header>
    <div class="col-customer-info">
    <fieldset class="adm-section-group">
        <h3 class="form-group-title">Order Information</h3>
        <ul class="form-list">
            <li>
                <h4 class="label">Order: <?=$m->id?> </h4>
            </li>
            <li>
                <h4 class="label">Order Date </h4>
                <input type="text" name="model[purchased_dt]" value="<?=$m->purchased_dt?>">
            </li>
            <li>
                <h4 class="label">Order Status</h4>
                <select name="model[status_id]">
                    <?php
                    $status = $m->status() ? $m->status()->code : $m->status;
                    foreach(FCom_Sales_Model_OrderStatus::i()->statusList() as $stobj):?>
                        <option value="<?=$stobj->id?>" <?=$stobj->code==$status?'selected':''?>><?=$stobj->name?></option>
                    <?php endforeach; ?>
                </select>
            </li>
        </ul>
    </fieldset>

    <fieldset class="adm-section-group">
        <ul class="form-list">
            <li>
                <h4 class="label">Account information </h4>
            </li>

                <?php if(false == $m->customer->guest):?>
                    <li>Customer name: <?=$m->customer->firstname?> <?=$m->customer->lastname?></li>
                    <li>Email: <?=$m->customer->email?></li>
                    <li><a href="<?=BApp::href('customers/form?id='.$m->customer->id)?>">Edit customer account</a></li>
                <?php else:?>
                    <li>Purchased by guest</li>
                <?php endif; ?>

        </ul>
    </fieldset>

        <fieldset class="adm-section-group">
        <ul class="form-list">
            <li>
                <h4 class="label">Payment info </h4>
            </li>
            <li>
                Payment method: <br/>
                <input type="text" name="model[payment_method]" value="<?=$m->payment_method?>">
            </li>
            <?php if(BUtil::fromJson($m->payment_details)):?>
                <?php foreach(BUtil::fromJson($m->payment_details) as $paymentKey => $paymentValue):?>
                    <li><?=$paymentKey?>: <?=$paymentValue?></li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </fieldset>


    <fieldset class="adm-section-group">
        <ul class="form-list">
            <li>
                <h4 class="label">Shipping &amp; Handling information </h4>
            </li>
            <li>
                Shipping method: <br/>
                <input type="text" name="model[shipping_method]" value="<?=$m->shipping_method?>">
            </li>
            <li>
                Shipping service: <?=$m->shipping_service_title?>

                <input type="text" name="model[shipping_service_title]" value="<?=$m->shipping_service_title?>">
            </li>
        </ul>
    </fieldset>

    <fieldset class="adm-section-group">
        <ul class="form-list">
            <li>
                <h4 class="label">Items ordered </h4>
            </li>
            <?php if($m->items):?>
                <?php foreach($m->items as $item):
                    $product = BUtil::fromJson($item->product_info);
                    ?>
                    <li>Product: <?=$product['product_name']?></li>
                    <li>SKU: <?=$product['manuf_sku']?></li>
                    <li>Price: <?=$product['base_price']?></li>
                    <li>Qty:
                        <input type="text" name="model[items][<?=$item->id?>][qty]" value="<?=$item->qty?>" onkeyup="$('#total_<?=$item->id?>').val(<?=$product['base_price']?>*this.value)">
                    </li>
                    <li>Total:
                            <input type="text" name="model[items][<?=$item->id?>][total]" value="<?=$item->total?>" id="total_<?=$item->id?>">
                    </li>
                    <li>Delete:
                            <input type="checkbox" name="model[items][<?=$item->id?>][delete]" value="1" >
                    </li>
                    <li>---------------------------</li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </fieldset>
</div>

<div class="col-customer-address" ng-controller="CustomerAddressesCtrl" ng-show="true" style="display:none">
        <h3 class="form-group-title">Addresses</h3>
        <div ng-repeat="a in addresses">
            <h4>{{a.atype }} address</h4>
            <label><input type="checkbox" ng-model="a.edit_mode"/> Edit</label>

            <div class="adr">
                <div class="street-address">
                    <input type="text" ng-show="a.edit_mode" ng-model="a.street1"/>
                    <span ng-hide="a.edit_mode" ng-bind="a.street1" placeholder="Street 1"></span>
                </div>
                <div class="extended-address" ng-show="a.street2 || a.edit_mode">
                    <input type="text" ng-show="a.edit_mode" ng-model="a.street2" placeholder="Street 2"/>
                    <span ng-hide="a.edit_mode" ng-bind="a.street2"></span>
                </div>
                <div class="extended-address" ng-show="a.street3 || a.edit_mode">
                    <input type="text" ng-show="a.edit_mode" ng-model="a.street3" placeholder="Street 3"/>
                    <span ng-hide="a.edit_mode" ng-bind="a.street3"></span>
                </div>
                <span class="locality">
                    <input type="text" ng-show="a.edit_mode" ng-model="a.city" placeholder="City"/>
                    <span ng-hide="a.edit_mode" ng-bind="a.city"></span>
                </span>,
                <span class="region">
                    <select ng-show="a.edit_mode && regions[a.country]" ng-options="name for (key,name) in regions[a.country]" ng-model="a.region"><option></option></select>
                    <input ng-show="a.edit_mode && !regions[a.country]" type="text" ng-model="a.region" placeholder="Region/State"/>
                    <span ng-hide="a.edit_mode" ng-bind="a.region"></span>
                </span>
                <span class="postal-code">
                    <input type="text" ng-show="a.edit_mode" ng-model="a.postcode" size="6" placeholder="Zip"/>
                    <span ng-hide="a.edit_mode" ng-bind="a.postcode"></span>
                </span>
                <div class="country-name">
                    <select ng-show="a.edit_mode" ng-options="key as name for (key,name) in countries" ng-model="a.country"><option></option></select>
                    <span ng-hide="a.edit_mode" ng-bind="countries[a.country]"></span>
                </div>
                <div class="phone">
                    <input type="text" ng-show="a.edit_mode" ng-model="a.phone" placeholder="Phone"/>
                    <span ng-hide="a.edit_mode" ng-bind="a.phone"></span>
                </div>
                <div class="fax">
                    <input type="text" ng-show="a.edit_mode" ng-model="a.fax" placeholder="Fax"/>
                    <span ng-hide="a.edit_mode" ng-bind="a.fax"></span>
                </div>
            </div>
        </div>
        <input type="hidden" name="address[data_json]" value="{{addresses}}"/>
        <input type="hidden" name="address[del_json]" value="{{del_ids}}"/>
    </div>




</div>

<?php else:
    /**
     * View
     * **/
    ?>

    <fieldset class="adm-section-group">
        <ul class="form-list">
            <li>
                <h4 class="label">Order: <?=$m->id?> </h4>
            </li>
            <li>
                <h4 class="label">Order Date: <?=$m->purchased_dt?> </h4>
            </li>
            <li>
                <h4 class="label">Order Status: <?=$m->status() ? $m->status()->name : $m->status?> </h4>
            </li>
        </ul>
    </fieldset>

    <fieldset class="adm-section-group">
        <ul class="form-list">
            <li>
                <h4 class="label">Account information </h4>
            </li>

                <?php if(false == $m->customer->guest):?>
                    <li>Customer name: <?=$m->customer->firstname?> <?=$m->customer->lastname?></li>
                    <li>Email: <?=$m->customer->email?></li>
                <?php else:?>
                    <li>Purchased by guest</li>
                <?php endif; ?>

        </ul>
    </fieldset>

    <fieldset class="adm-section-group">
        <ul class="form-list">
            <li>
                <h4 class="label">Billing info </h4>
            </li>
            <li>
                <?=$m->billing_name?>
            </li>
            <li>
                <?=$m->billing_address?>
            </li>
        </ul>
    </fieldset>


    <fieldset class="adm-section-group">
        <ul class="form-list">
            <li>
                <h4 class="label">Shipping info </h4>
            </li>
            <li>
                <?=$m->shipping_name?>
            </li>
            <li>
                <?=$m->shipping_address?>
            </li>
        </ul>
    </fieldset>

    <fieldset class="adm-section-group">
        <ul class="form-list">
            <li>
                <h4 class="label">Payment info </h4>
            </li>
            <li>
                Payment method: <?=$m->payment_method?>
            </li>
            <?php if(BUtil::fromJson($m->payment_details)):?>
                <?php foreach(BUtil::fromJson($m->payment_details) as $paymentKey => $paymentValue):?>
                    <li><?=$paymentKey?>: <?=$paymentValue?></li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </fieldset>


    <fieldset class="adm-section-group">
        <ul class="form-list">
            <li>
                <h4 class="label">Shipping &amp; Handling information </h4>
            </li>
            <li>
                Shipping method: <?=$m->shipping_method?>
            </li>
            <li>
                Shipping service: <?=$m->shipping_service_title?>
            </li>
        </ul>
    </fieldset>

    <fieldset class="adm-section-group">
        <ul class="form-list">
            <li>
                <h4 class="label">Items ordered </h4>
            </li>
            <?php if($m->items):?>
                <?php foreach($m->items as $item):
                    $product = BUtil::fromJson($item->product_info);
                    ?>
                    <li>Product: <?=$product['product_name']?></li>
                    <li>SKU: <?=$product['manuf_sku']?></li>
                    <li>Price: <?=$product['base_price']?></li>
                    <li>Qty: <?=$item->qty?></li>
                    <li>Total: <?=$item->total?></li>
                    <li>---------------------------</li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </fieldset>
<?php endif; ?>

