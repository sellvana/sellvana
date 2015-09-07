Sellvana - Fcom Griddle Manual
============

___

1. ### Filters

	* #### Config:

		Please see the example below.

		> 		...

		> 		'filters' => [

		> 	    	['field' => 'column_name', 'type' => 'text'],

		> 	    	...

		> 		]

		> 		...

	* #### Types:

		Filter's types: `text`, `date-range`, `num-range`, `multiselect`.

		Assign it by append `type` key-value pair to each column in filters block.

		> 		...

		> 		'filters' => [

		>	    	['field' => 'column_name', 'type' => 'date-range'],

		>	    	['field' => 'column_name', 'type' => 'num-range'],

		>	    	['field' => 'column_name', 'type' => 'multiselect']

		> 		]

		> 		...

	* #### Functionality:

		_ Filters is searching functionality for each data column on grid, 
		to register it and ability to see it on view please follow the example above.

2. ### Settings

	_ Setting contains a textbox for quickly searching all data on grid and some action button like `Delete`, `Refresh` ...

	* #### Config: 

		> 		...

		> 		'actions' => [

		> 			'new'    => ['caption' => 'Title of button'],
		
		> 			'edit'   => true,
		
		> 			'delete' => true

		> 			...

		> 		]

		> 		...

	* #### Functionality:

		* New: 

			Add new data grid row

				* Example code:

					...

					'new' => ['caption' => 'Title of button', 'class' => '... ...', 'modal' => true]

					...
		
				* Attributes:

					1. caption: Define name of button

					2. class: Class attributes for button

					3. modal: 

						* true: show modal popup and render form depend on column metadata

						* default: Add new blank row to data grid

		* Edit: 

			Edit data grid rows

				* Example code:

					...

					'edit' => true

					...
		
				* Attributes:

					1. true: show modal popup and render form depend on column metadata

		* Delete:

			Delete data grid row

				* Example code:

					...

					'delete' => true

					...

				* Attributes:

					1. true: show modal popup and render form depend on column metadata

		* Custom:

			Custom button that allow user make own functionality

				* Example code:

					...

					'custom' => [

						'caption'  => '...',
						
						'type'     => '...',
						
						'id'       => '...', 
						
						'class'    => '... ...', 
						
						'caption'  => '...', 
						
						'callback' => '...'

					]

					...

				* Attributes:

					1. caption: Define its title

					2. class: Define class attribute

					3. id: Define id attribute

					4. type: Define type of button [ button / html ]

					5. callback: global callback for making own button functionality.

						window.globalFunction = function() {

							// Code for custom button here

						}


3. ### Data Grid Table

	Grid contains data of

	* #### Config:

		> 		...

		> 		'columns' => [

		> 			['type' => '...', 'width' => ...],

		> 			[

		>				'type' => '...', 

		>				'buttons' => [

		>                	['name' => '...'],

		>					...

		>           	]

		>           ],

        >    		['name' => '...', 'label' => '...', 'index' => '...', 'width' => ..., 'hidden' => ...],

		> 			...

		> 		]

		> 		...

	* #### Column Type:

		* Selector column: 

			Column contains checkbox so user can select rows for edit or delete

				* Example code:

					...

					['type' => 'row_select', 'width' => 55],

					...

				* Attributes:

					1. type: row_select

					2. width: Define width of column

		* Actions column: 

			Column contains action buttons so you can perform with specific row

				* Example code:

					...

					[
						'type' => 'btn_group',

						'buttons' => [

		                	['name' => 'edit'],

		                	['name' => 'delete'],

		                	['name' => 'custom', 'cssClass' => '...', 'icon' => '...', 'callback' => '...']

		            	]

		            ]

					...

				* Attributes:

					1. type: btn_group

					2. buttons: Define buttons type

						* name: Define button's type eg. edit / delete ...

						* cssClass: Define button's css class.

						* callback: Define global callback for making own button functionality.

							window.globalFunction = function() {

								// Code for custom button here

							}

		* Default:

			Columns depend on database columns

				* Example code:

					...

					['name' => '...', 'label' => '...', 'type' => '...', 'width' => ...],

					...

				* Column type:

					1. input: 

						* Example code:

							...

							[
								'name'       => '...', 
								
								'label'      => '...', 
								
								'type'       => 'input', 
								
								'width'      => ..., 
								
								'addable'    => true, 
								
								'editable'   => '...', 
								
								'editor'     => '...', 
								
								'validation' => [

									'required' => true, 

									...

								]

							]

							...

						* Attributes: 

							1. editable: 

							2. editor:

							3. validation:

					2. link:

					3. default:
