Sellvana - Fcom Product Price Manual
===
___

### Table Content:

1. [Configuration](#1-configuration): `Example configuration for price`

2. [Title](#2-title) (title): `Define price grid title`

3. [Data Mode](#3-data-mode) (dataMode): `Define mode for price gird eg. local or server`

4. [Prices](#4-prices) (prices): `Specify initial price data for grid`

5. [Price Types](#5-price-types) (priceTypes): `Specify price types for grid eg. Cost, Sale ...`

6. [Editable Prices](#6-editable-prices) (editablePrices): `Specify which price type could be edited`

7. [Customer Groups](#7-customer-groups) (customerGroups): 

8. [Validate Prices](#8validate-prices) (validatePrices): `Validate function for checking unique price type`

9. [Price Relation Options](#9price-relation-options) (priceRelationOptions): `Options related to price and depend on it for calculating price amount`

10. [Operation Options](#operation-options) (operationOptions): `Operation options for calculating price amount`

___

1. Configuration: 
===
Price component receive 2 important options: `id` and `options`

    * id: Unique id for price grid, using for validation and some functionality.
    * options: Configurations for rendering prices.

### 1.1 Example:
*`Id`* attribute example:

>       var id = 'product';

*`Options`* attribute example:
>       var options = {
>           'title'               : ' ... ',
>           'dataMode'            : ' ... ',
>           'prices'              : [ ... ],
>           'priceTypes'          : [ ... ],
>           'editablePrices'      : [ ... ],
>           'customerGroups'      : [ ... ],
>           'sites'               : [ ... ],
>           'productId'           : ' ... ',
>           'validatePrices'      : ' ... ',
>           'priceRelationOptions': [ ... ],
>           'operationOptions'    : [ ... ],
>           'saleDateSeparator'   : ' ... ',
>           'showCustomers'       : ' ... ',
>           'showSites'           : ' ... ',
>           'showCurrency'        : ' ... ',
>       };

### 1.2 Usage:
Create a *`react component`* to render price grid *(recommended)*, so you can re-use validation methods from `fcom.catalog.components`

>       require([..., 'fcom.catalog.product.price', 'fcom.catalog.components', ...], function(..., Price) {
>           var PriceGrid = React.createClass({
>               // Include [FCom.PriceMixin] to react mixins for re-using validation method
>               mixins: [FCom.PriceMixin],
>               render: function() {
>                   var options = { ... };
>                   ...
>                   return React.createElement(Price, { id: 'product', options: options });
>               }
>           });
>       });
>       

### 1.3 Functionality:

Upon this component rendered, there are some functionality follow with it.

* Add New Prices: when new one is added it bring along with a callback so that you can do something after that.

    * Copy and insert this line below to *`Options`* configuration.

    >       ...
    >       addPriceCallback: 'callbackFunctionName',
    >       ...

    * After subcribe it in grid options configuration

    >       ...
    >       window.callbackFunctionName = function(prices, priceGridId) {
    >           // This callback bring back two args 
    >           // `prices`: list of prices include new one
    >           // 'priceGridId': name of grid that contains this lists
    >           ...
    >       }
    >       ...

___

2. Title
===
This config simply allow you to define title of grid.

### 2.1 Config:

>       ...
>       'title': 'My Example Price',
>       ...

___

3. Data Mode
===

### 3.1 Config:

>       ...
>       'dataMode' => 'local',
>       ...

### 3.2 Functionality:
`Comming soon ...`

___

4. Prices
===

### 4.1 Config:

>       ...
>       'prices' => [ ... ],
>       ...
___

5. Price Types
===

### 5.1 Config:

>       ...
>       'priceTypes': [ ... ],
>       ...

### 5.2 Types:
Default: If missing price types, component will load these below types as initial types

* [Base Price](#base-price): ...
* [Cost](#cost-price): ...
* [Map](#map-price): ...
* [MSRP](#msrp-price): ...
* [Promo Price](#promo-price): ...
* [Sale Price](#sale-price): ...
* [Tier Price](#tier-price): ...

### 5.3 Functionality:

>       Comming soon ...

___

6. Editable Prices
===

### 6.1 Config:

>       ...
>       'editablePrices': [ ... ],
>       ...

___

7. Customer Groups
===

### 7.1 Config:

>       ...
>       'customerGroups': { ... },
>       ...

### 7.2 Group Types:
Default: If missing customer groups, component will load these below types as initial data

* [General](#general): ...
* [NOT LOGGED IN](#not-log-in): ...
* [Retail](#retail): ...
* [ALL](#all): ...

### 7.3 Functionality:

>       Comming soon ...

___

8. Validate Prices
===

### 8.1 Config:

In order to apply validation to price, you need to require `fcom.catalog.components`, `jquery.validate`
>       require([..., 'fcom.catalog.components', 'jquery.validate', ...], function() {
>       
>           ...
>           var options = {
>               ...
>               'validatePrices': uniquePriceValidator,
>               ...
>           };
>           ...
>       });

### 8.2 Usage:

Example function for unique validation
>       function uniquePriceValidator() {
>           var valid = true;
>           $('#price_grid_wrapper_id').find('select.productPriceUnique').each(function (el) {
>               if (!$(this).valid()) {
>                   valid = false;
>               }
>           });
>           return valid;
>       }

And do not forget subcribing unique class to jquery validate [addMethod](http://jqueryvalidation.org/jQuery.validator.addMethod) and [addClassRules](http://jqueryvalidation.org/jQuery.validator.addClassRules)

>       $.validator.addMethod('productPriceUnique', this.validateUniquePrice('product'), '{{"Validate message."|_}}');
>       $.validator.addClassRules("productPriceUnique", {
>           productPriceUnique: true
>       });

* *`validateUniquePrice()`* is called from `fcom.catalog.components`

___

9. Price Relation Options
===

### 9.1 Config:

Each price type has own related options, you can calculate specific price amount depend on it

>       ...
>       priceRelationOptions: [ ... ],
>       ...

### 9.2 Options: 
Related options of each price type is listed in below list

* Base Type: [Cost](#cost-price) and [MRSP](#mrsp-price)

* Cost Type: [Base](#base-price) and [Sale](#sale-price)

* Sale Type: [Cost](#cost-price) and [Base](#base-price)

* Map Type: [Cost](#cost-price) and [MRSP](#mrsp-price)

* MRSP Type: [Cost](#cost-price) and [Base](#base-price)

* Tier Type: [Cost](#cost-price), [Base](#base-price) and [Sale](#sale-price)

10. Operation Options
===
Each operation  we can calculate amount such as `Set % of`, `Subtract % of`, ...

### 10.1 Config:

>       ...
>       'operationOptions': [ ... ],
>       ...

### 10.2 Options:

* [Fixed](#fixed) `(alias: =$)`
* [Times](#times) `(alias: *$)`
* [Add to](#add-to) `(alias: +$)`
* [Subtract from](#subtract-form) `(alias: -$)`
* [Set % of](#set-%-of) `(alias: *%)`
* [Add % to](#add-%-to) `(alias: +%)`
* [Subtract % from](#subtract-%-from) `(alias: -%)`

### 10.3 Functionality:

