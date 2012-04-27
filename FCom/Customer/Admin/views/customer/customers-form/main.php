<?php $m = $this->model ?>
<script>
function CustomerAddressesCtrl($scope) {
    $scope.addresses = <?=BUtil::toJson(BDb::many_as_array($m->addresses()))?>;
    $scope.countries = <?=BUtil::toJson(FCom_Geo_Model_Country::options())?>;
    $scope.regions = <?=BUtil::toJson(FCom_Geo_Model_Region::allOptions())?>;

    $scope.addAddress = function() {
        $scope.addresses.push({edit_mode:true, customer_id:<?=$m->id?>});
    }

    $scope.delAddress = function(addr) {
        for (var i = 0, ii = $scope.addresses.length; i<ii; i++) {
            if (addr === $scope.addresses[i]) {
                $scope.addresses.splice(i, 1);
            }
        }
    }
}

function CustomerAddressCtrl($scope) {

}
</script>

<img src="<?=BUtil::gravatar($m->email)?>"/>
<?=$this->q($m->firstname)?> <?=$this->q($m->lastname)?>

<div ng-controller="CustomerAddressesCtrl">
    <div ng-repeat="a in addresses">
        <a href ng-click="delAddress(a)">[X]</a>
        <label><input type="checkbox" ng-model="a.edit_mode"/> Edit</label>
        <div class="adr">
            <div class="street-address">
                <input type="text" ng-show="a.edit_mode" ng-model="a.street1"/>
                <span ng-hide="a.edit_mode" ng-bind="a.street1"></span>
            </div>
            <div class="extended-address" ng-show="a.street2 || a.edit_mode">
                <input type="text" ng-show="a.edit_mode" ng-model="a.street2"/>
                <span ng-hide="a.edit_mode" ng-bind="a.street2"></span>
            </div>
            <div class="extended-address" ng-show="a.street3 || a.edit_mode">
                <input type="text" ng-show="a.edit_mode" ng-model="a.street3"/>
                <span ng-hide="a.edit_mode" ng-bind="a.street3"></span>
            </div>
            <span class="locality">
                <input type="text" ng-show="a.edit_mode" ng-model="a.city"/>
                <span ng-hide="a.edit_mode" ng-bind="a.city"></span>
            </span>,
            <span class="region">
                <select ng-show="a.edit_mode && regions[a.country]" ng-options="name for (key,name) in regions[a.country]" ng-model="a.region"><option></option></select>
                <input ng-show="a.edit_mode && !regions[a.country]" type="text" ng-model="a.region"/>
                <span ng-hide="a.edit_mode" ng-bind="a.region"></span>
            </span>
            <span class="postal-code">
                <input type="text" ng-show="a.edit_mode" ng-model="a.postcode"/>
                <span ng-hide="a.edit_mode" ng-bind="a.postcode"></span>
            </span>
            <div class="country-name">
                <select ng-show="a.edit_mode" ng-options="key as name for (key,name) in countries" ng-model="a.country"><option></option></select>
                <span ng-hide="a.edit_mode" ng-bind="countries[a.country]"></span>
            </div>
        </div>
    </div>
    <a href ng-click="addAddress()">Add</a>
</div>