Sellvana - Fcom Griddle Manual
============

### 1. Configuration: `Example config for all modules`

* Example code:

	>		$moduleConfig = [
				
	>				'config' => [
					
	>						'id' => '...',
	
	>						'caption' => '...',
	
	>						'data_mode' => '...',
	
	>						'data' => ...,
	
	>						'columns' => [
	>							...
	>						],
	
	>						'filters' => [
	>							...
	>						],
	
	>						'actions' => [
	>							...
	>						],
	
	>						'callbacks' => [
	>							...
	>						],
	
	>						'grid_before_create' => '...'
					
	>				]
				
	>		];

* Functionality:

	* Id (id): `Define id of grid`
	
	* Caption (caption): `Define caption of grid`
	
	* Data Mode (data_mode): `Define mode of grid, eg. server or local mode`
	
	* Data (data): `Init data for grid`
	
	* Columns (columns): `columns config for data table`
	
	* Filters (filters): `Define filter for each column and type of it`
	
	* Actions (actions): `Defind action button`
	
	* Callbacks (callbacks): `...`

___

### 2. Filters

* Config:

	> 		...

	> 		'filters' => [

	> 	    	['field' => 'column_name', 'type' => 'text'],

	> 	    	...

	> 		]

	> 		...

* Types:

	Filter's types: `text`, `date-range`, `num-range`, `multiselect`. Assign it by append `type` key-value pair to each column in filters block.

	> 		...

	> 		'filters' => [

	>	    	['field' => 'column_name', 'type' => 'date-range'],

	>	    	['field' => 'column_name', 'type' => 'num-range'],

	>	    	['field' => 'column_name', 'type' => 'multiselect']

	> 		]

	> 		...

* Functionality:

	_ Filters is searching functionality for each data column on grid, to register it and ability to see it on view please follow the example above.

___

### 3. Settings

_ Setting contains a textbox for quickly searching all data on grid and some action button like `Delete`, `Refresh` ...

* Config: 

	> 		...

	> 		'actions' => [

	> 			'new'    => ['caption' => 'Title of button'],
	
	> 			'edit'   => true,
	
	> 			'delete' => true

	> 			...

	> 		]

	> 		...

* Functionality:

	* New: `Add new data grid row`

		* Example code:
	
			>		...
		
			>		'new' => ['caption' => 'Title of button', 'class' => '... ...', 'modal' => true]
		
			>		...
				
		* Attributes:
		
			>		1. caption: Define name of button
		
			>		2. class: Class attributes for button
		
			>		3. modal: 
		
			>			3.1 true: show modal popup and render form depend on column metadata
		
			>			3.2 default: Add new blank row to data grid

	* Edit: `Edit data grid rows`

		* Example code:

			>		...

			>		'edit' => true

			>		...
		
		* Attributes:

			>		1. true: show modal popup and render form depend on column metadata

	* Delete: `Delete data grid row`

		* Example code:

			>		...

			>		'delete' => true

			>		...

		* Attributes:

			>		1. true: show modal popup and render form depend on column metadata

	* Custom: `Custom button that allow user make own functionality`

		* Example code:

			>		...

			>		'custom' => [

			>			'caption'  => '...',
						
			>			'type'     => '...',
						
			>			'id'       => '...', 
						
			>			'class'    => '... ...', 
						
			>			'caption'  => '...', 
						
			>			'callback' => '...'

			>		]

			>		...

		* Attributes:

			>		1. caption: Define its title

			>		2. class: Define class attribute

			>		3. id: Define id attribute

			>		4. type: Define type of button [ button / html ]

			>		5. callback: global callback for making own button functionality.

			>				window.globalFunction = function() {

			>						// Code for custom button here

			>				}

___
### 4.Data Table

* Config:

	> 		...

	> 			'columns' => [

	> 					['type' => '...', 'width' => ...],

	> 					[

	>						'type' => '...', 

	>						'buttons' => [

	>                			['name' => '...'],

	>							...

	>           			]

	>           		],
		
	>					['name' => '...', 'label' => '...', 'type' => '...', 'width' => '...', ...],

	> 				...

	> 			]

	> 		...

* Column Type:

	* Selector column: `contains checkbox so you can select one or more rows for edit or delete`

		* Example code:

			>		...

			>		['type' => 'row_select', 'width' => 55],

			>		...

		* Attributes:

			>		1. type: row_select

			>		2. width: Define width of column

	* Actions column: `contains action buttons so you can perform with specific row`

		* Example code:

			>		...

			>			[
			
			>				'type' => 'btn_group',

			>				'buttons' => [

		    >       				['name' => 'edit'],

		    >          				['name' => 'delete'],
	
		    >         				['name' => 'custom', 'cssClass' => '...', 'icon' => '...', 'callback' => '...']

		    >        		]

		    >       	]

			>		...

		* Attributes:

			>		1. type: btn_group

			>		2. buttons: Define buttons type

			>			* name: Define button's type eg. edit / delete ...

			>			* cssClass: Define button's css class.

			>			* callback: Define global callback for making own button functionality and only be affected on custom type button

			>					window.globalFunction = function() {

			>							// Code for custom button here

			>					}

	* Default: `columns depend on database`

		* Example code:

			>		...

			>		['name' => '...', 'label' => '...', 'type' => '...', 'width' => '...', ...],

			>		...

		* Column type:

			* input: 

				* Example code:
		
					>		...
		
					>				[
					
					>						'name'       => '...', 
										
					>						'label'      => '...', 
										
					>						'type'       => 'input', 
										
					>						'width'      => ..., 
										
					>						'editable'   => '...', 
										
					>						'editor'     => '...', 
										
					>						'validation' => [
					>								'required' => true, 
					>								...
					>						]
		
					>				]
	
					>		...

				* Attributes: 

					* editable: `This attribute define type of input contained on each column`

						* inline: `In inline mode each column will contains input that you had specified and had record value so can change it directly.`

						* Example code:

							>		...
				
							>				[
				
							>					...
															
							>					'type'       => 'input',
															
							>					'editable'   => 'inline', 
															
							>					'editor'     => '...',
				
							>					...
				
							>				]
				
							>		...

						* default: `Default column only contains the text value of record`

					* editor: `Set editor attribute so that you can choose what input type which contains on column eg. dropdown, textbox or ...`

						* Example code:

							>		...
				
							>				[
				
							>					...
														
							>					'editor'     => 'checkbox',
				
							>					...
				
							>				]
				
							>		...

						* Editor Options:

							>		1. checkbox
			
							>		2. radio
			
							>		3. textarea
			
							>		4. select
					
							>		5. textbox ( default )
							
						* Option Functionality:
						
							1. Checkbox:
						
								>		
								
							2. Radio:
								
								>		
								
							3. Textarea:
							
								>		
								
							4. Select:
							
								>		
								
							5. Textbox (default):
							
								>		
		
					* validation: `Set validation to input is simply`
					
						* Example code:
						
						
							>		...
				
							>			[
				
							>				...
														
							>				'validation'     => ['required' => true, ...],
				
							>				...
				
							>			]
				
							>		...
		
						* Validation options:
						
							>		1. required: Check if input is empty.

							>		2. email: Check if email is valid.

							>		3. number: Check if value is numeric

							>		4. digits: 

							>		5. ip: Check if ip is valid.

							>		6. url: Check if value is valid url.

							>		7. phoneus: ...

							>		8. minlength: ...

							>		9. maxlength: ...

							>		10. max: ...

							>		11. min: ...

							>		12. range: ...

							>		13. date: Check if value is valid date format
		
			* link: `Comming soon ...`
