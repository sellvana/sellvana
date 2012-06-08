<?php $m = $this->model ?>
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
    <header class="adm-main-header">Customer Info</header>
    <div class="col-customer-info">
        <fieldset>
            <h3 class="form-group-title">Personal Information</h3>
            <ul class="form-list">
                <li class="label-l">
                    <label for="model_firstname">First Name</label>
                    <input type="text" id="model-firstname" name="model[firstname]" value="<?=$this->q($m->firstname)?>"/>
                </li>
                <li class="label-l">
                    <label for="model-lastname">Last Name</label>
                    <input type="text" id="model-lastname" name="model[lastname]" value="<?=$this->q($m->lastname)?>"/>
                </li>
                <li class="label-l">
                    <label for="model-email">Email</label>
                    <input type="text" id="model-email" name="model[email]" value="<?=$this->q($m->email)?>"/>
                </li>
                <!--
                <li class="label-l">
                    <label for="model-phone">Daytime Phone</label>
                    <input type="text" id="model-phone" name="model[phone]" value="<?=$this->q($m->phone)?>"/>
                </li>
                <li class="label-l">
                    <label for="model-phone_work">Work Phone</label>
                    <input type="text" id="model-phone_work" name="model[phone_work]" value="<?=$this->q($m->phone_work)?>"/>
                </li>
                -->
                <!--
                <li class="label-l">
                <label for="model-status">Status</label>
                <select id="model-status" name="model[status]">
                <?//=$this->optionsHtml($m->fieldOptions('status'), $m->status)?>
                </select>
                </li>
                -->
            </ul>
        </fieldset>
        <fieldset>
            <h3 class="form-group-title">Password Reset</h3>
            <ul class="form-list">
                <li class="label-l">
                    <label for="#">New Password</label>
                    <input type="password"/>
                </li>
                <li class="label-l">
                    <label for="#">Confirm Password</label>
                    <input type="password"/>
                </li>
            </ul>
        </fieldset>
    </div>
    <div class="col-customer-address" ng-controller="CustomerAddressesCtrl" ng-show="true" style="display:none">
        <h3 class="form-group-title">Addresses</h3>
        <div ng-repeat="a in addresses">
            <a href ng-click="delAddress(a)" class="btn-remove">[X]</a>
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
        <a href ng-click="addAddress()" class="add-link">+ Add Another </a>
        <input type="hidden" name="address[data_json]" value="{{addresses}}"/>
        <input type="hidden" name="address[del_json]" value="{{del_ids}}"/>
    </div>
</div>
