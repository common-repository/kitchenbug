<?php

require_once('Pages/Recipe.php');
require_once('Pages/Settings.php');

abstract class KB_Wordpress
{
	const DEFAULT_POST_STATUS = 'draft';
	const OUR_ADMIN_MENU = 'my-new-menu.php';

	public $kitchenbug;
	public $version;
	public $siteURL;
	public $ajaxURL;
	public $view;
	static public $postID;

	public function __construct()
	{
		$this->kitchenbug = KB_Main::getInstance();
		$this->version = $this->kitchenbug->config['plugin']['version'];
		$this->pluginsURL = WP_PLUGIN_URL;
		$this->pluginsDIR = WP_PLUGIN_DIR;
		$this->siteURL = get_site_url();
		$this->ajaxURL = admin_url('admin-ajax.php');
		$this->fullSiteURL = $this->siteURL . $this->kitchenbug->pluginURL;

		$this->view = new KB_View($this);

		// Set plugin config into recipe page
		$this->view->config = $this->kitchenbug->config['plugin'];
		$this->view->siteURL = $this->fullSiteURL;
		$this->view->version = $this->version;
		$this->view->ajaxURL = $this->ajaxURL;
	}

	public function buildRecipePageCallback()
	{
		//Get post data
		if (isset($_REQUEST['post']))
		{
			$post = get_post($_REQUEST['post']);
			$this->view->postStatus = $post->post_status;
		}
		else
		{
			$this->view->postStatus = self::DEFAULT_POST_STATUS;
		}

		$page = new KB_Pages_Recipe();
		$texts = $page->getPageData();
		foreach ($texts as $key => $val)
		{
			$this->view->$key = $val;
		}

		$pageData = ($page->postID) ? $page->select($page->postID) : null;

		if (!empty($pageData))
		{
			$pageData = array_merge(array(
				'id' => null,
				'source' => null,
				'name' => null,
				'intro' => null,
				'prep_time' => null,
				'cook_time' => null,
				'servings' => null,
				'units' => null,
				'catgeory' => null,
				'cuisine' => null,
				'scale' => null,
				'calories' => null,
				'cc' => null,
				'img_source' => null,
				'record_state' => null,
				'ing_section' => array(0 => null),
				'ingredients' => array(0 => null),
				'directions' => array(0 => null),
				'tips' => null,
				'tags' => null,
				'nutritionOn' => null,
					), $pageData);


			foreach ($pageData as $setKey => $setVal)
			{
				if (!$setKey)
				{
					continue;
				}
				$this->view->$setKey = $setVal;
			}
		}

		$settings = KB_Pages_Settings::read();
		$this->view->userId = @$settings['userId'];
		if (!isset($this->view->nutritionOn))
		{
			if (isset($settings['nutritionOn']))
			{
				$this->view->nutritionOn = $settings['nutritionOn'];
			}
			else
			{
				$this->view->nutritionOn = 'off';
			}
		}


		$html = $this->view->render('recipe-editor');

		$postID = (self::$postID) ? self::$postID : (int) @$_GET['post'];
		echo str_replace('{POST_ID}', $postID, $html);
	}

	/**
	 * add Ajax request to wordpress
	 * @param string $name
	 * @param array $args
	 * @return void
	 */
	protected function _addAjaxRequest($name, array $args)
	{
		add_action('wp_ajax_' . $name, $args);
	}

	/**
	 * Add admin access to top bar menu
	 * @todo change 'meta' javascript
	 * @return void
	 */
	function adminBarMenu()
	{
		global $wp_admin_bar;
		$root_menu = array(
			'parent' => false,
			'id' => $this->kitchenbug->config['DOM']['ADMIN_BAR']['MENU'],
			'title' => $this->kitchenbug->config['DOM']['ADMIN_BAR']['TITLE'],
			'href' => admin_url(self::OUR_ADMIN_MENU),
			//TODO: fix this to kitchenbug
			'meta' => array('onclick' => 'kitchenbug.openFormat(); return false')
		);
		$wp_admin_bar->add_menu($root_menu);
	}

	/**
	 * Add JS & CSS assets to wordpress queue
	 * @param string $file
	 * @param array $dependancy
	 * @param bool $isUniqueName
	 * @return void;
	 */
	public function addAsset($file, $dependancy = array(), $isFooter = false, $isUniqueName = false, $type=null)
	{
		if (!$type)
		{
			$fileData = explode('.', $file);
		}
		else
		{
			$fileData[0] = str_replace(substr($file, strpos($file, '.' . $type)), '', $file);
			$fileData[1] = $type;
		}

		if ($isUniqueName)
		{
			$name = 'KB_' . $fileData[0];
		}
		else
		{
			$name = $fileData[0];
		}

		$path = '';

		if ('css' == $fileData[1])
		{
			if (KB_Main::isURL($name))
			{
				$path = $file;
			}
			else
			{
				$path = $this->kitchenbug->assets['css'] . '/' . $file;
			}
			wp_enqueue_style($name, $path, $dependancy, $this->kitchenbug->config['plugin']['version']);
		}
		elseif ('js' == $fileData[1])
		{
			if (KB_Main::isURL($name))
			{
				$path = $file;
			}
			else
			{
				$path = $this->kitchenbug->assets['js'] . '/' . $file;
			}
			wp_enqueue_script($name, $path, $dependancy, $this->kitchenbug->config['plugin']['version'], $isFooter);
		}
	}

	/**
	 * add a Kitchenbug theme
	 * @param string $name
	 * @param string $file
	 * @param array $dependancy
	 */
	public function addTheme($name, $dependency = array())
	{
		// Create the theme's URL path.
		$path = $this->kitchenbug->themes . '/' . $name;
		// Add the theme's CSS file.
		wp_enqueue_style(
				$name, $path . '/style.css', $dependency, $this->kitchenbug->config['plugin']['version']);
		$this->kitchenbug->themeNames[$name] = $name;
	}

	public function getThemes()
	{
		return $this->kitchenbug->themeNames;
	}

}