<?php

/**
 * Wordpress database instnace 
 * @author 
 *
 */
class KB_Db
{

	/**
	 * @var KB_Db $_instance 
	 */
	private static $_instance = false;
	/**
	 * @var String $_db 
	 */
	private $_db;

	/**
	 * Get singleton instance 
	 * @param String $db
	 * @return KB_Db
	 */
	public function getInstance($db=null)
	{
		if (!self::$_instance)
		{
			self::$_instance = new self($db);
		}
		elseif ($db)
		{
			self::$_instance->_setDb($db);
		}
		return self::$_instance;
	}

	/**
	 * Constructor - Setup database name
	 * @param String $db 
	 */
	private function __construct($db)
	{
		$this->_setDb($db);
	}

	/**
	 * Set database name
	 * @param String $db
	 * @return boolean
	 */
	public function _setDb($db)
	{
		if (!$db)
		{
			return false;
		}
		$this->_db = $db;
	}

	public function select($query = null)
	{
		$data = get_option($this->_db);

		if (!$query)
		{
			return $data;
		}
		//TODO: $query;? 
	}

	/**
	 * Insert data to wordpress option db 
	 * @param array $query
	 * @return void 
	 */
	public function insert(array $query)
	{
		add_option($this->_db, $query);
	}

	/**
	 * Update data in wordpress option db 
	 * @param array $query
	 * @return void 
	 */
	public function update(array $query)
	{
		update_option($this->_db, $query);
	}

	/**
	 * Create db in wordpress option db 
	 */
	public function create()
	{
		add_option($this->_db, array());
	}

}