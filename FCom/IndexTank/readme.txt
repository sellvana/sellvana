==IndexDen Search==

IndexDen hosted, realtime search 

IndexDen brings nice, instant search to your website, with automatic suggestions of query terms, and super-fast Amazon-style search.

==Features==
* InstantSearch: See results as you type!
* AutoComplete.
* Facets. Filter results by categories, custom fields or price range.
* Sorting. Order results by **relevance**, **newest** or **price**.
* Pagination.
* Index administraion. You could easily extend Index with new fields or scoring functions.

==Installation==
* Activate IndexTank module through the Modules page.
* Run migration scripts to create new index 'products' and install predefined functions.
* Go to the 'Admin' -> 'Settings' and select 'IndexDen API' tab
* If you have an account, enter your API URL and hit 'Save All'. If not, hit the **'Get one'** button.
* Go to the 'IndexDen' -> 'Product fields' and hit the **'Index All Products'** button

You are all set, now just query something on your website.

==Customization==
Product fields
Product index fields used for full text search, for filters and grouping or for scroing functions.
Product index field could be filled from product table field or from function.

Add new product field into index:
* 'Field' 		- Choose unique field name which is used internall by IndexDen
* 'Label' 		- Choose readable name, readable names used for facets filters
* 'Search' 		- Choose 'Search' if field should be searchable
* 'Facets' 		- Choose 'Facets' to build facets and to allow filtering by field
* 'Scoring variable' 	- Choose scoring variable if field will be used in scoring function
* 'Priority' 		- Set priority for the field - only used for searchable fields
* 'Variable number'  	- Set variable number - only used for scoring variables fields
* 'Display as' 		- Choose how to display facets filter at search results - as a link or as a checkbox
* 'Filter type' 	- Filter type 'inclusive' allow to select several options and filter type 'exclusive' allow to select only one option - applicable only for facets
* 'Source type'		- Choose source type of field - 'product' or 'function'
* 'Source value'	- For 'product' 'source value' should be field from products table. For 'function' 'source value' should be function name.


Functions
Scoring functions are defined by mathematic formulas that take data from the document, the query and the textual relevance in order to assign a score to each matching document for a query. The resulting scores are used when searching the index to provide specific orderings for the results. 

You could add or modify scoring function to define new formula of sorting search results.
Funciton number determine the number of the function in IndexDen system.
Function definition determine the function formula.
For instance you want to sorts documents considering the distance between doc and a point passed in the query.
You need to create function like this:
miles(query.var[0], query.var[1], doc.var[0], doc.var[1])

Read more about functions here: http://www.indexden.com/documentation/function-definition

How to add new functions
For instance you want to sorts documents rating.
* Add new product index field 'rating', check it as scoring variable and set variable number i.e. '3'. Select the source for the field (field should have some value).
* Click button 'Index All Products' to add new variable to the index. 
* Add new function 'ranking' with definition: doc.var[3] (3 is variable number)
* Add new sort option to frontend view: open IndexTank/FrontEnd/views/indextank/product/pager.php and add new option 'rating' => 'Sort by rating' to the $sortOptions array.
* Now you are ready to sort by rating!

* In the same way you could filter by rating.
* Open pager.php again and add two new controls with names v[rating][from] and v[rating][to]. Example:
Filter by rating: from <input type=text name="v[rating][from]">  to <input type=text name="v[rating][to]">




























