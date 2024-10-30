<?php

require_once('Db.php');
require_once('Pages/Settings.php');

class KB_Main
{

	public $paths = array();
	public $config;
	public $pluginURL;
	public $editor;
	public $themeNames = array();
	static private $_instance = false;

	const CONFIG_FILE = 'config.php';
	const AJAX_PAGE = 'admin-ajax.php';

	public static function getInstance($pluginPath = null)
	{
		if (!self::$_instance)
		{
			self::$_instance = new self($pluginPath);
		}
		return self::$_instance;
	}

	private function __construct($pluginPath)
	{
		$this->paths['include'] = self::getIncludePath();
		$this->paths['application'] = $this->paths['include'] . '/../application';
		$this->pluginURL = '/wp-content/plugins/' . $pluginPath;
		$this->themes = /* ABSPATH. */ $this->pluginURL . '/themes';
		$this->themesPath = ABSPATH . $this->pluginURL . '/themes';
		$assets = $this->pluginURL . '/application/assets';
		$this->assets = array(
			'js' => $assets . '/js',
			'css' => $assets . '/css',
			'img' => $assets . '/img',
		);

		$this->_getConfig();
		$this->_setEditorPrefix();

		add_action('admin_init', array($this, 'upgradePlugin'));
	}

	private function _setEditorPrefix()
	{
		$this->editor = new stdClass;
		$this->editor->prefix = array(
			'full' => $this->config['plugin']['editor-prefix'],
			'replaced' => str_replace('%d]', '', $this->config['plugin']['editor-prefix'])
		);
		$this->editor->prefix = (object) $this->editor->prefix;

		$this->placeholder = $this->config['plugin']['editor-placeholder'];
		$this->placeholder = str_replace('%s', get_site_url(), $this->placeholder);
	}

	public function InitializePlugin()
	{
		$settings = new KB_Pages_Settings();
		$settings->saveOption(array('KBPluginVersion' => $this->config['plugin']['version'],
				'nutritionOn' => 'on', 'excerptOn' => 'off', 'wikilinksOn' => 'on',
				'collectbuttonOn' => 'off', 'theme' => 'starter'));
	}

	public function upgradePlugin()
	{
		// Check if we need to upgrade
		$settings = new KB_Pages_Settings();
		$settingsData = $settings->select();

		// Upgrade from versions below 0.6.1
		if (isset($settingsData['KBPluginVersion']) && $settingsData['KBPluginVersion'] !== '0.6.4')
		{
			// Verify tables are updated
			global $wpdb;
			$recipes_table = $wpdb->prefix . "kb_recipes";
			$recipes_table_only = $wpdb->prefix . "kb_recipes_only";
			// Verify table exists
			if ($wpdb->get_var("SHOW TABLES LIKE '$recipes_table'") == $recipes_table)
			{
				$sql = "CREATE TABLE " . $recipes_table . " (
					post_id BIGINT(20) UNSIGNED NOT NULL UNIQUE KEY,
					recipeID BIGINT(20) UNSIGNED NOT NULL,
					content MEDIUMTEXT,
					kb_plugin_version varchar(20)
					)DEFAULT CHARSET=utf8;";

				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);

				$sql = "CREATE TABLE " . $recipes_table_only . " (
					recipeID BIGINT(20) UNSIGNED NOT NULL UNIQUE KEY,
					recipeName varchar(512) DEFAULT NULL,
					content MEDIUMTEXT,
					engine_version varchar(32),
					lastupdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
					)DEFAULT CHARSET=utf8;";

				dbDelta($sql);
			}

			// Update settings
			if (!isset($settingsData['nutritionOn']))
			{
				$settingsData['nutritionOn'] = 'on';
			}

			if (!isset($settingsData['excerptOn']))
			{
				$settingsData['excerptOn'] = 'off';
			}

			if (!isset($settingsData['wikilinksOn']))
			{
				$settingsData['wikilinksOn'] = 'on';
			}

			if (!isset($settingsData['collectbuttonOn']))
			{
				$settingsData['collectbuttonOn'] = 'off';
			}

			// Update Database (only if version below 0.6.0)
			if ($settingsData['KBPluginVersion'] !== '0.6.0' && $settingsData['KBPluginVersion'] !== '0.6.1'
				&& $settingsData['KBPluginVersion'] !== '0.6.2' && $settingsData['KBPluginVersion'] !== '0.6.3'
				&& $settingsData['KBPluginVersion'] !== '0.6.4')
			{
				$this->upgradeDBData();
			}

			$settingsData['KBPluginVersion'] = $this->config['plugin']['version'];
			$settings->saveOption($settingsData);

			// Code for upgrading when a custom theme exists will be inserted
			// here in version 0.6.5 (no need yet)

		}
	}

	private function upgradeDBData()
	{
		global $wpdb;

		$results = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "kb_recipes");
		if (!empty($results))
		{
			foreach ($results as $result)
			{
				$postID = $result->post_id;
				$recipeID = 0;
				$plVersion = $recipeName = '';

				$content = json_decode($result->content, TRUE);
				// Get the distributed data
				if (isset($content['recipe_id']))
				{
					$recipeID = $content['recipe_id'];
				}

				if (isset($content['name']))
				{
					$recipeName = $content['name'];
				}

				$wpdb->insert($wpdb->prefix . "kb_recipes_only",
						array('recipeID' => $recipeID, 'recipeName' => $recipeName, 'engine_version' => '1.0.56', 'content' => $result->content),
						array('%d', '%s', '%s', '%s'));
				$wpdb->update($wpdb->prefix . "kb_recipes",
						array('recipeID' => $recipeID, 'kb_plugin_version' => '0.6.4'),
						array('post_id' => $postID),
						array('%d', '%s'));

			}
		}
	}

	public function getEditorPrefix()
	{
		return $this->editor->prefix->full;
	}

	public function getEditorPrefixReplaced()
	{
		return $this->editor->prefix->replaced;
	}

	public function getEditorPlaceHolder()
	{
		return $this->placeholder;
	}

	private function _getConfig()
	{
		$this->config = null;
		$this->config = @include_once($this->paths['application'] . '/config/' . self::CONFIG_FILE);
	}

	public static function array_merge_recursive_helper(array $arr1, array $arr2)
	{
		foreach ($arr1 as $key => $value)
		{
			if (!isset($arr2[$key]))
			{
				$arr2[$key] = $value;
				continue;
			}

			if (!is_array($value))
			{
				$arr2[$key] = $arr1[$key];
				continue;
			}

			$arr2[$key] = self::array_merge_recursive_helper($arr1[$key], $arr2[$key]);
		}
		return $arr2;
	}

	public static function isAjaxCall()
	{
		if (self::AJAX_PAGE == $GLOBALS["pagenow"])
		{
			if (isset($_REQUEST["action"]))
			{
				return true;
			}
		}
		return false;
	}

	public static function getIncludePath()
	{
		return dirname(__FILE__);
	}

	public static function isURL($text)
	{
		return preg_match('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $text);
	}

	public function __set($name, $value)
	{
		$this->$name = $value;
	}

	public function __get($name)
	{
		if (isset($this->$name))
		{
			return $this->$name;
		}
	}
}

/**
 * Fix for PHP V < 5.3
 * get_called_class
 */
if (!function_exists('get_called_class'))
{

	function get_called_class()
	{
		$bt = debug_backtrace();
		$l = 0;
		do
		{
			$l++;
			$lines = file($bt[$l]['file']);
			$callerLine = $lines[$bt[$l]['line'] - 1];
			preg_match('/([a-zA-Z0-9\_]+)::' . $bt[$l]['function'] . '/', $callerLine, $matches);

			if ($matches[1] == 'self')
			{
				$line = $bt[$l]['line'] - 1;
				while ($line > 0 && strpos($lines[$line], 'class') === false)
				{
					$line--;
				}
				preg_match('/class[\s]+(.+?)[\s]+/si', $lines[$line], $matches);
			}
		}
		while ($matches[1] == 'parent' && $matches[1]);
		return $matches[1];
	}

}