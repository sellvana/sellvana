Sellvana - Fcom Product Price Manual
===
___

### Table Content:

1. **[Configuration](#1-configuration):** `Example configuration for price`

2. **[Title](#2-title) (title):** `Define price grid title`

3. **[Prices](#3-prices) (prices):** `Specify initial price data for grid`

4. **[Price Types](#4-price-types) (priceTypes):** `Specify price types for grid eg. Cost, Sale ...`

5. **[Editable Prices](#5-editable-prices) (editablePrices):** `Specify which price type could be edited`

6. **[Customer Groups](#6-customer-groups) (customerGroups):** 

7. **[Validate Prices](#7-validate-prices) (validatePrices):** `Validate function for checking unique price type`

8. **[Price Relation Options](#8-price-relation-options) (priceRelationOptions):** `Options related to price and depend on it for calculating price amount`

9. **[Operation Options](#9-operation-options) (operationOptions):** `Operation options for calculating price amount`

___

1. Configuration: 
===
Price component receive 2 important options: *`id`* and *`options`*

* **id:** Unique name of grid, using for validation, determined what view we are working on and some functionality.

    >       var id = 'product';

* **options:** Configurations for prices grid.

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

### 1.1 Usage:

Define *`react component`* to render grid *(recommended)*, so you can re-use validation methods from `fcom.catalog.components` and ease to react with `price component`

>       require([..., 'fcom.catalog.product.price', 'fcom.catalog.components', ...], function(..., Price) {
>           var PriceGrid = React.createClass({
>               // Include [FCom.PriceMixin] to react mixins for re-using validation method
>               mixins: [FCom.PriceMixin],
>               componentDidMount: function() {
>                   // React with price component after component is mounted on initial load
>               },
>               ...
>               render: function() {
>                   var options = { ... };
>                   ...
>                   return React.createElement(Price, { id: 'product', options: options });
>               }
>           });
>       });
>       

### 1.2 Functionality:

Upon this component rendered, there are some functionality follow with it.

* **Add New Prices:** when new one is added it bring along with a callback so that you can do something after that.

    * Copy and insert this line below to *`Options`* configuration.

        >       ...
        >       addPriceCallback: 'callbackFunctionName',
        >       ...

    * 

        >       ...
        >       window.callbackFunctionName = function(prices, gridName) {
        >           // This callback bring back two args 
        >           // `prices`: list of all prices
        >           // 'gridName': grid name that contains this lists
        >           ...
        >       }
        >       ...

* **Others:** `Comming soon ...`

___

2. Title
===

This config simply allow you to define title of grid.

### 2.1 Config:

>       ...
>       'title': 'My Example Price',
>       ...

### 2.2 Usage:

Note: wrap it with Locale functionality for multi languages (Recommended).

>       ...
>       'title': Locale._('My Example Price'),
>       ...

___

3. Prices
===

### 3.1 Config:

>       ...
>       'prices' => [ ... ],
>       ...

### 3.2 Usage:

`Comming soon ...`

___

4. Price Types
===

### 4.1 Config:

>       ...
>       'priceTypes': [ ... ],
>       ...

### 4.2 Types:
Default: If missing price types, component will load these below types

* [Base Price](#base-price): ...
* [Cost](#cost-price): ...
* [Map](#map-price): ...
* [MSRP](#msrp-price): ...
* [Promo Price](#promo-price): ...
* [Sale Price](#sale-price): ...
* [Tier Price](#tier-price): ...

### 4.3 Functionality:

>       Comming soon ...

___

5. Editable Prices
===

### 5.1 Config:

>       ...
>       'editablePrices': [ ... ],
>       ...

### 5.2 Functionality:

>       This config allow prices type are edited or not

___

6. Customer Groups
===

### 6.1 Config:

>       ...
>       'customerGroups': { ... },
>       ...

### 6.2 Group Types:
Default: If missing customer groups, component will load these below types as initial data

* [General](#general): ...
* [NOT LOGGED IN](#not-log-in): ...
* [Retail](#retail): ...
* [ALL](#all): ...

### 6.3 Functionality:

>       Comming soon ...

___

7. Validate Prices
===

### 7.1 Config:

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

### 7.2 Usage:

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

* For more detail *`validateUniquePrice()`* please refer `fcom.catalog.components`

___

8. Price Relation Options
===

### 8.1 Config:

Each price type has own related options, you can calculate specific price amount depend on it

>       ...
>       priceRelationOptions: [ ... ],
>       ...

### 8.2 Options: 
Related options of each price type is listed in below list

* Base Type: [Cost](#cost-price) and [MRSP](#mrsp-price)

* Cost Type: [Base](#base-price) and [Sale](#sale-price)

* Sale Type: [Cost](#cost-price) and [Base](#base-price)

* Map Type: [Cost](#cost-price) and [MRSP](#mrsp-price)

* MRSP Type: [Cost](#cost-price) and [Base](#base-price)

* Tier Type: [Cost](#cost-price), [Base](#base-price) and [Sale](#sale-price)

9. Operation Options
===
Each operation  we can calculate amount such as `Set % of`, `Subtract % of`, ...

### 9.1 Config:

>       ...
>       'operationOptions': [ ... ],
>       ...

### 9.2 Options:

* [Fixed](#fixed) `(alias: =$)`
* [Times](#times) `(alias: *$)`
* [Add to](#add-to) `(alias: +$)`
* [Subtract from](#subtract-form) `(alias: -$)`
* [Set % of](#set-%-of) `(alias: *%)`
* [Add % to](#add-%-to) `(alias: +%)`
* [Subtract % from](#subtract-%-from) `(alias: -%)`

### 9.3 Functionality:

>       Comming soon ...
