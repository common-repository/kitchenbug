<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class KB_Recipe_List_Table extends WP_List_Table
{
	/**	 * ***********************************************************************
	 * REQUIRED. Set up a constructor that references the parent constructor. We 
	 * use the parent reference to set some default configs.
	 * ************************************************************************* */
	function __construct()
	{
		global $status, $page;

		//Set parent defaults
		parent::__construct(array(
			'singular' => 'recipe', //singular name of the listed records
			'plural' => 'recipes', //plural name of the listed records
			'ajax' => false		//does this table support ajax?
		));
	}

	function column_default($item, $column_name)
	{
		switch ($column_name)
		{
			case 'lastupdate':
				return $item[$column_name];
			default:
				return print_r($item, true); //Show the whole array for troubleshooting purposes
		}
	}

	function column_title($item)
	{
		// Build row actions
		$actions = array(
			//'edit' => sprintf('<a href="?page=%s&action=%s&recipe=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['ID']),
			'delete' => sprintf('<a href="?page=%s&action=%s&recipe=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['ID']),
		);

		// Return the title contents
		return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
						/* $1%s */ $item['title'],
						/* $2%s */ $item['ID'],
						/* $3%s */ $this->row_actions($actions)
		);
	}

	function column_cb($item)
	{
		return sprintf(
						'<input type="checkbox" name="%1$s[]" value="%2$s" />',
						/* $1%s */ $this->_args['singular'], //Let's simply repurpose the table's singular label
						/* $2%s */ $item['ID']				//The value of the checkbox should be the record's id
		);
	}

	function get_columns()
	{
		$columns = array(
			'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
			'title' => 'Recipe Title',
			'lastupdate' => 'Last Update',
			
		);
		return $columns;
	}

	/**	 * ***********************************************************************
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
	 * you will need to register it here. This should return an array where the 
	 * key is the column that needs to be sortable, and the value is db column to 
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 * 
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 * 
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 * ************************************************************************ */
	function get_sortable_columns()
	{
		$sortable_columns = array(
			'title' => array('title', false), //true means it's already sorted
			'lastupdate' => array('lastupdate', false),
		);
		return $sortable_columns;
	}

	function get_bulk_actions()
	{
		$actions = array(
			'trash' => 'Move To Trash'
		);
		return $actions;
	}

	/**	 * ***********************************************************************
	 * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
	 * For this example package, we will handle it in the class to keep things
	 * clean and organized.
	 * 
	 * @see $this->prepare_items()
	 * ************************************************************************ */
	function process_bulk_action()
	{
		// Detect when a bulk action is being triggered...
		if ('trash' === $this->current_action())
		{
			
		}
	}

	/**	 * ***********************************************************************
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args(), although the following properties and methods
	 * are frequently interacted with here...
	 * 
	 * @global WPDB $wpdb
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 * ************************************************************************ */
	function prepare_items()
	{
		global $wpdb;

		$per_page = 8;

		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();


		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column 
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array($columns, $hidden, $sortable);


		/**
		 * Optional. You can handle your bulk actions however you see fit. In this
		 * case, we'll handle them within our package just to keep things clean.
		 */
		$this->process_bulk_action();

		// Fetch the data from the database
		$objdata = $wpdb->get_results("SELECT recipeID as ID, recipeName as title, lastupdate FROM " . $wpdb->prefix . 'kb_recipes_only');
		$data = array();
		// Objects to array get_permalink( $post->ID );
		foreach ($objdata as $value)
		{
			$data[] = (array)$value;
		}

		/**
		 * This checks for sorting input and sorts the data in our array accordingly.
		 * 
		 * In a real-world situation involving a database, you would probably want 
		 * to handle sorting by passing the 'orderby' and 'order' values directly 
		 * to a custom query. The returned data will be pre-sorted, and this array
		 * sorting technique would be unnecessary.
		 */
		function usort_reorder($a, $b)
		{
			$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
			$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
			$result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
			return ($order === 'asc') ? $result : -$result; //Send final sort direction to usort
		}

		usort($data, 'usort_reorder');

		/**
		 * REQUIRED for pagination. Let's figure out what page the user is currently 
		 * looking at. We'll need this later, so you should always include it in 
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();

		/**
		 * REQUIRED for pagination. Let's check how many items are in our data array. 
		 * In real-world use, this would be the total number of items in your database, 
		 * without filtering. We'll need this later, so you should always include it 
		 * in your own package classes.
		 */
		$total_items = count($data);


		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to 
		 */
		$data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where 
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;


		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args(array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page' => $per_page, //WE have to determine how many items to show on a page
			'total_pages' => ceil($total_items / $per_page)   //WE have to calculate the total number of pages
		));
	}

}