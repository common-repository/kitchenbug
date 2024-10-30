<?php

class KB_Pages_Settings
{
	private $_dbName = 'KBSettings';
	const POST_NAME = 'settings';
	const DEFAULT_THEME ='starter';
	const THEME ='theme';
	protected $_db;

	public function __construct()
	{
		$this->_db = KB_Db::getInstance($this->_dbName);
	}

	public static function read()
	{
		$class = get_called_class();
		$self = new $class();
		return $self->select();
	}

	public function getPost($name)
	{
		if (!$name)
		{
			return $_POST;
		}

		if (empty($_POST[$name]))
		{
			return false;
		}

		$_POST = array_map('stripslashes_deep', $_POST);
		foreach ($_POST[$name] as $key => &$val)
		{
			if (is_array($val))
			{
				unset($val);
				continue;
			}

			$val = htmlspecialchars(stripslashes($val));
		}

		return $_POST[$name];
	}

	final protected function _translate($name)
	{
		return $name;
	}

	/**
	 * Query data from  page settings db
	 * @TODO set query to work
	 */
	public function select($query=null)
	{
		$this->_db->_setDb($this->_dbName);
		$data = $this->_db->select();
		if (!$query)
		{
			return $data;
		}
		return (!empty($data[$query])) ? $data[$query] : false;
	}

	/**
	 * Save post data & query current
	 * @return Array
	 */
	public function save()
	{
		$posts = $this->getPost(self::POST_NAME);

		if (is_array($posts))
		{
			if (!key_exists('nutritionOn', $posts))
			{
				$posts['nutritionOn'] = 'off';
			}
			if (!key_exists('excerptOn', $posts))
			{
				$posts['excerptOn'] = 'off';
			}
			if (!key_exists('wikilinksOn', $posts))
			{
				$posts['wikilinksOn'] = 'off';
			}
			if (!key_exists('collectbuttonOn', $posts))
			{
				$posts['collectbuttonOn'] = 'off';
			}
		}
		else
		{
			$posts['excerptOn'] = 'off';
			$posts['nutritionOn'] = 'on';
			$posts['wikilinksOn'] = 'on';
			$posts['collectbuttonOn'] = 'off';
		}

		$select = $this->select();

		if (empty($posts) || !is_array($posts))
		{
			return $select;
		}

		if (false === $select)
		{
			$this->_db->insert($posts);
		}
		else
		{
			if (is_array($select))
			{
				$posts = array_merge($select, $posts);
			}
			$this->_db->update($posts);
		}

		return $posts;
	}

	public function saveOption(array $data)
	{
		$select = $this->select();

		if (false === $select)
		{
			$this->_db->insert($data);
		}
		else
		{
			if (is_array($select))
			{
				$data = array_merge($select, $data);
			}
			$this->_db->update($data);
		}
	}

	/**
	 * Returns all page default data, such as: Title, h1, images
	 * @TODO : use $this->getValue($key) or $this->getValues() at Page_Abstract
	 * @return Array
	 */
	public function getPageData()
	{
		$stdClass = new stdClass();

		$stdClass->userNumber = $this->_translate('User ID Number');
		$stdClass->submitButton = $this->_translate('Save Changes');
		$stdClass->defaultTheme = self::DEFAULT_THEME;
		$stdClass->themeLabel = $this->_translate('Select theme');
		$stdClass->nutritionalAnalysisLabel = $this->_translate('Show nutrition facts by default');
		$stdClass->excerptOnLabel = $this->_translate('Create recipe excerpt for Category, Tag, Author or Date based page');
		$stdClass->wikilinksOnLabel = $this->_translate('Add wikipedia links to ingredients');
		$stdClass->collectbuttonOnLabel = $this->_translate('Add the Kitchenbug Collect button which allows users to save your recipe to their recipe box');
		$stdClass->backupButton = $this->_translate('Backup Recipes');
		return array(
			'pageTitle' => $this->_translate('Kitchenbug Settings'),
			'page' => $stdClass
		);
	}

}