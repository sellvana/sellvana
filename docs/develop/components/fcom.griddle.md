Sellvana - Fcom Griddle Manual
===
___

### Table Content:

1. [Configuration](#1-configuration): `Example configuration for all modules`

2. [Data Mode](#2-data-mode) (data_mode): `Define mode of grid, eg. server or local mode`

3. [Data](#3-data) (data): `Init data for grid`

4. [Columns](#4-columns) (columns): `Columns config for data table`
4.1 [Config](#41-config)
4.2 [Detail](#42-detail)
	* [Selector Columns](#selector-column-this-column-contains-checkbox-so-you-can-select-one-or-more-rows-for-editable-or-removable)
	* [Action Columns](#actions-column-this-column-contains-action-buttons-so-you-can-perform-with-specific-row)
	* [Default Columns](#default-columns-column-default-depend-on-database)

5. [Filters](#5-filters) (filters): `Define filter for each column and type of it`

6. [Actions](#6-actions) (actions): `Defind action button`

7. [Callbacks](#7-callbacks) (callbacks): `Global function of react components`
7.1 [Config](#71-config)
7.2 [Detail](#72-detail)
	* [componentDidMount](#721-componentDidMount)
	* [componentDidMount](#722-componentDidUpdate)
___

1. Configuration: 
===

`Configuration for all modules`

### Example:

>		$moduleConfig = [
>			'config' => [
>				'id'        => '...',
>				'caption'   => '...',
>				'data_mode' => '...',
>				'data'      => [ ... ],
>				'columns'   => [ ... ],
>				'filters'   => [ ... ],
>				'actions'   => [ ... ],
>				'callbacks' => [ ... ],
>			]
>		];

2. Data Mode
===

`Comming soon ...`

[Back to top](#sellvana-fcom-griddle-manual)

___

3. Data
===

`Comming soon ...`

[Back to top](#sellvana-fcom-griddle-manual)

___

4. Columns
===

###### Define columns config for data table

#### 4.1 Config:
> 		...
> 		'columns' => [
> 			['type' => '...', 'width' => ...],
> 			[
>				'type' => '...',
>				'buttons' => [
>					['name' => '...', ...],
>					...
>				]
>			],
>			['name' => '...', 'label' => '...', 'type' => '...', 'width' => '...', ...],
> 			...
> 		]
> 		...

#### 4.2 Detail:

##### Selector Column: `This column contains checkbox so you can select one or more rows for editable or removable`

* Example code:

	>		...
	>		['type' => 'row_select', 'width' => 55],
	>		...

* Attributes:

	>		1. type: row_select
	>		2. width: Define width of column

##### Actions Column: `This column contains action buttons so you can perform with specific row`

* Example code:

	>		...
	>		[
	>			'type' => 'btn_group',
	>			'buttons' => [
	>       		['name' => 'edit'],
	>          		['name' => 'delete'],
	>         		['name' => 'custom', 'cssClass' => '...', 'icon' => '...', 'callback' => '...']
	>        	]
	>       ]
	>		...

* Attributes:

	1. type: Just set `btn_group` to `type` key pair

	2. buttons: Define buttons type, `eg. edit, delete button`

		* name: Define button's type eg. edit / delete ...
		* cssClass: Define button's css class.
		* icon: Define icon class for this button, just copy and paste source class of font-awesome or whatever

			* Example code:

				>		...
				>		['name' => 'custom', 'icon' => 'fa fa-pencil', ...]
				>		...

		* callback: Define global callback for making own button functionality and only be affected on custom type button

			* Example code:

				*Subcribe on backend*
				>		...
				>		['name' => 'custom', ..., 'callback' => 'globalCallbackFunction']
				>		...

				*Define it on view*
				>		window.globalCallbackFunction = function() {
				>			// Code for custom button here
				>		}


##### Default Column:

* Example code:

	>		...
	>		['name' => '...', 'label' => '..., 'width' => '...', ...],
	>		...

* Types:

	#### input: 

	* Example code:

		>		...
		>		[
		>			'name' => '...',
		>			'label' => '...',
		>			'type' => 'input'
		>			...
		>		]
		>		...

	* Attributes: 

		* editable: `This attribute define type of input contained on each column`

			* inline: `In inline mode each column will contains input that you had specified and had record value so can change it directly.`

			* Example code:

				>		...
				>		[
				>			...
				>			'type'       => 'input',		
				>			'editable'   => 'inline',
				>			...
				>		]
				>		...

			* default: `Default is only contains the text value of record`

		* editor: `Set editor attribute so that you can specify what input type which is contained on table column eg. dropdown, textbox or ...`

			* Example code:

				>		...
				>		[
				>			...
				>			'type'       => 'input',		
				>			'editable'   => 'inline',
				>			'editor'     => 'checkbox',
				>			...
				>		]
				>		...

			* Editor Options:

				1. [checkbox](#1-checkbox)
				2. [radio](#2-radio)
				3. [textarea](#3-textarea)
				4. [select](#4-select)
				5. [textbox](#5-textbox-default) ( default )
						
		* validation: `Set validation to input is simply`

			[Validation Rules](#validation-rules)
				
			* Example code:
			
				>		...
				>		[
				>			...	
				>			'validation' => ['required' => true, ...],
				>			...
				>		]
				>		...

				###### Validation rules:
				
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

		* Option Functionality:
					
			###### 1. Checkbox:
				
			* Example code:

				>		...
				>		[
				>			...
				>			'type'       => 'input',		
				>			'editable'   => 'inline',
				>			'editor'     => 'checkbox',
				>			...
				>		]
				>		...
							
			###### 2. Radio:
			
			* Example code:

				>		...
				>		[
				>			...
				>			'type'       => 'input',		
				>			'editable'   => 'inline',
				>			'editor'     => 'radio',
				>			...
				>		]
				>		...
					
			###### 3. Textarea:
				
			* Example code:

				>		...
				>		[
				>			...
				>			'type'     => 'input',		
				>			'editable' => 'inline',
				>			'editor'   => 'textarea',
				>			'rows'     => '...',
				>			'cols'     => '...',
				>			...
				>		]
				>		...
					
			###### 4. Select:
			
			* Example code:

				>		...
				>		[
				>			...
				>			'type'     => 'input',		
				>			'editable' => 'inline',
				>			'editor'   => 'select',
				>			'options'  => [1 => 'Yes', 0 => 'No'],
				>			'default'  => 0,
				>			...
				>		]
				>		...
					
			###### 5. Textbox (default):
			
			* Example code:

				>		...
				>		[
				>			...
				>			'type'       => 'input',		
				>			'editable'   => 'inline',
				>			...
				>		]
				>		...
	
	#### link: `Comming soon ...`

[Back to top](#sellvana-fcom-griddle-manual)

___

5. Filters
===

###### Filters is searching functionality for each data column on grid, to register it and ability to see it on view please follow the example above.

#### 5.1 Config:

> 		...
> 		'filters' => [
> 	    	['field' => 'column_name', 'type' => 'text'],
> 	    	...
> 		]
> 		...

#### 5.2 Detail:

Filter's types: `text`, `date-range`, `num-range`, `multiselect`. Assign it by append `type` key-value pair to each column in filters block.

> 		...
> 		'filters' => [
>	    	['field' => 'column_name', 'type' => 'date-range'],
>	    	['field' => 'column_name', 'type' => 'num-range'],
>	    	['field' => 'column_name', 'type' => 'multiselect']
> 		]
> 		...

[Back to top](#sellvana-fcom-griddle-manual)

___

6. Settings
===

###### Setting contains a textbox for quickly searching all data on grid and some action button like `Delete`, `Refresh` ...

#### 6.1 Config: 

> 		...
> 		'actions' => [
> 			'new'    => ['caption' => 'Title of button'],
> 			'edit'   => true,
> 			'delete' => true
> 			...
> 		]
> 		...

#### 6.2 Detail:

* New:

	* Example code:

		>		...
		>		'new' => ['caption' => 'Title of button', 'class' => '... ...', 'modal' => true]
		>		...
			
	* Attributes:
	
		>		1. caption: Define name of button
		>		2. class: Class attributes for button
		>		3. modal: 
		>			* true: show modal popup and render form depend on column metadata
		>			* default: Add new blank row to data grid

* Edit:

	* Example code:

		>		...
		>		'edit' => true
		>		...
	
	* Attributes:

		>		1. true: show modal popup and render form depend on column metadata

* Delete:

	* Example code:

		>		...
		>		'delete' => true
		>		...

	* Attributes:

		>		1. true: show modal popup and render form depend on column metadata

* Custom:

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

		1. caption: Define its title
		2. class: Define class attribute
		3. id: Define id attribute
		4. type: Define type of button [ button / html ]
		5. callback: 
			*Subcribe on backend*

			>		...
			>		'custom' => [
			>			...
			>			'callback' => 'globalCallbackFunction'
			>			...
			>		]
			>		...

			*Define it on view*

			>		window.globalCallbackFunction = function() {
			>				// Code for custom button here
			>		}

[Back to top](#sellvana-fcom-griddle-manual)

___

7. Callbacks
===

###### Receive grid's config and execute some functionality when initial load

#### 7.1 Config:

>		...
>		'callbacks' => [
>			'componentDidMount' => '...'
>			'componentDidUpdate' => '...'
>		]
>		...
>		

#### 7.2 Detail:

##### 7.2.1 ComponentDiDMount: 

This function will be called after grid is finish `rendered` on initial load and return grid's configuration.

* Example code:

	*Subcribe on backend*
	>		...
	>		'componentDidMount' => 'myGridRegister'
	>		...

	*Define on view*
	>		...
	>		window.myGridRegister = function() {
	>			// Code for componentDidMount here
	>		}
	>		...

##### 7.2.2 ComponentDidUpdate (Optional):

This function will be called after grid is `re-rendered`

* Example code:

	*Subcribe on backend*
	>		...
	>		'componentDidUpdate' => 'myGridAfterDidUpdate'
	>		...

	*Define on view*
	>		...
	>		window.myGridAfterDidUpdate = function() {
	>			// Code for componentDidUpdate here
	>		}
	>		...

[Back to top](#sellvana-fcom-griddle-manual)
