<?php
require_once('Pages/kb_recipe_list.php');

/**
 * Admin settings for wordpress plugin
 * @author
 */
class KB_Admin extends KB_Wordpress
{

	public function __construct()
	{
		parent::__construct();

		// Add page to the Admin settings menu
		add_action('admin_menu', array($this, 'addAdminMenu'), 1);
		add_action('admin_head', array($this, 'addAdminHead'), 1);

		add_action('save_post', array('KB_Posts', 'savePostCallback'), 10, 2);
		add_action('deleted_post', array('KB_Posts', 'deletePostCallback'), 11);

		// Set Ajax calls
		if (KB_Main::isAjaxCall())
		{
			$this->_setDefaultAjax();
		}

		// Enqueue all the scripts here since we need to do this after the plug-in has finished initialization
		add_action('admin_init', array($this, 'admin_init'));

		add_action('load-post.php', array($this, 'mceFullScreenButtonsStyle'));
		add_action('load-post-new.php', array($this, 'mceFullScreenButtonsStyle'));

		add_action('admin_enqueue_scripts', array($this, 'kbtuts_pointer_load'), 1000);
		add_filter('kbtuts_admin_pointers-post', array($this, 'kbtuts_register_pointer_testing'));

		switch ($GLOBALS["pagenow"])
		{
			case "post.php":
			case "post-new.php":
				// Add Thickbox Media Uploader actions to WordPress
				add_action('admin_print_scripts', array($this, 'my_admin_scripts'));
				add_action('admin_print_styles', array($this, 'my_admin_styles'));
				// init process for MCE button control
				add_action('init', array($this, 'add_mce_buttons'));
				add_action('admin_enqueue_scripts', array($this, 'beforeBuildRecipePage'), 1);
				add_action('admin_footer', array($this, 'buildRecipePageCallback'), 2);
				break;
		}
	}

	// Callback function registered for Media Uploader thickbox
	function my_admin_scripts()
	{
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_style('wp-jquery-ui-dialog');
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox', null, array('jquery'));
		wp_register_script('my-upload', $this->kitchenbug->assets['js'] . '/kbug-wp.js', array('jquery', 'media-upload', 'thickbox'));
		wp_enqueue_script('my-upload');
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_style("wp-jquery-ui-dialog");
	}

	// Callback function registered for  Media Uploader thickbox
	function my_admin_styles()
	{
		wp_enqueue_style('thickbox');
	}

	function beforeBuildRecipePage()
	{
		global $post;

		// The editor form
		wp_register_style('kitchenbug-plugin-jquery-ui-css', $this->kitchenbug->assets['css'] . '/jquery-ui-classic.css');
		wp_enqueue_style('kitchenbug-plugin-jquery-ui-css');
		wp_register_script('kbug-recipe', $this->siteURL . $this->kitchenbug->assets['js'] . '/kbug-recipe.js', array('jquery'));
		// Pass PHP params to javascript
		$page = new KB_Pages_Recipe();
		$texts = $page->getPageData();
		$settings = array('version' => $this->kitchenbug->config['plugin']['version'],
			'pluginURL' => $this->siteURL . $this->kitchenbug->pluginURL,
			'siteURL' => $this->siteURL,
			'ajaxURL' => admin_url('admin-ajax.php'),
			'configURL' => $this->kitchenbug->config['plugin']['url'],
			'editorPrefix' => $this->kitchenbug->editor->prefix->replaced,
			'permalink' => get_permalink($post->ID),
			'texts' => json_encode($texts)
		);
		wp_localize_script('kbug-recipe', 'kb_settings', $settings);
		wp_enqueue_script('kbug-recipe');

		// Editor form css
		wp_enqueue_style('kbug-recipe-editor', $this->siteURL . $this->kitchenbug->assets['css'] . '/kbug-recipe-editor.css');
	}

	// Callback function for enqueing all admin scripts
	function admin_init()
	{
		add_filter('plugin_action_links_' . 'kitchenbug/kitchenbug.php', array($this, '_pluginActionLinks'));
	}

	function add_mce_buttons()
	{
		add_filter('mce_css', array($this, 'kitchenbug_mce_css'));
		add_filter('mce_external_plugins', array($this, 'mcePlugins'));
		add_filter('mce_buttons', array($this, 'mceButtons'));
		add_filter('wp_fullscreen_buttons', array($this, 'mceFullScreenButtons'));
	}

	function kitchenbug_mce_css($mce_css)
	{
		if (!empty($mce_css))
			$mce_css .= ',';
		$mce_css .= $this->siteURL . $this->kitchenbug->assets['css'] . '/kbug-editor-style.css';
		return $mce_css;
	}

	/**
	 * Set default admin ajax
	 * @return void
	 */
	private function _setDefaultAjax()
	{
		$this->_addAjaxRequest('saveTags', array($this, 'saveTagsPage'));
		$this->_addAjaxRequest('kbsaveRecipeInsert', array($this, 'saveRecipeInsert'));
		$this->_addAjaxRequest('updateUserId', array($this, 'updateUserId'));
		$this->_addAjaxRequest('kbbackupFiles', array($this, 'backupFiles'));
		$this->_addAjaxRequest('kbUpdateSource', array($this, 'updateSource'));
	}

	public function backupFiles()
	{
		$recipePage = new KB_Pages_Recipe();

		$recipes = $recipePage->selectAll();
		if (count($recipes) == 0)
		{
			header("HTTP/1.1 500 Server Error");
			echo "No recipes in the Kitchenbug database.";
			exit();
		}

		$recipeXML = $this->_createRecipeXML($recipes);
		// create file
		$upload_dir = wp_upload_dir();
		$filename = trailingslashit($upload_dir['path']) . 'recipes.xml';
		$fp = fopen($filename, "w");
		fputs($fp, $recipeXML);
		fclose($fp);

		header("HTTP/1.1 200 OK");
		echo $upload_dir['url'] . '/recipes.xml';
		exit();
	}

	private function _createRecipeXML($recipes)
	{
		// Create the xml document
		$xmlDoc = new DOMDocument();
		// Create the root element
		$root = $xmlDoc->appendChild($xmlDoc->createElement("Recipes"));
		// Iterate through the recipes
		foreach ($recipes as $recipe)
		{
			if (!is_array($recipe))
			{
				continue;
			}

			// Create a recipe element
			$recipeElement = $root->appendChild($xmlDoc->createElement("Recipe"));
			// Add recipe fields
			$recipeElement->appendChild($xmlDoc->createElement("RecipeID", $recipe['recipe_id']));
			$recipeElement->appendChild($xmlDoc->createElement("Name", htmlentities($recipe['name'])));
			$recipeElement->appendChild($xmlDoc->createElement("Description", htmlentities($recipe['intro'])));
			$recipeElement->appendChild($xmlDoc->createElement("PreparationTime", $recipe['prep_time']));
			$recipeElement->appendChild($xmlDoc->createElement("CookingTime", $recipe['cook_time']));
			$recipeElement->appendChild($xmlDoc->createElement("Servings", $recipe['servings']));
			$ingsElement = $recipeElement->appendChild($xmlDoc->createElement("Ingredients"));

			$ingredients = explode("\n", $recipe['ingredients'][0]);
			foreach ($ingredients as $ingredient)
			{
				$ingsElement->appendChild($xmlDoc->createElement("Ingredient", htmlspecialchars($ingredient)));
			}
			$directionsElement = $recipeElement->appendChild($xmlDoc->createElement("Directions"));
			$directions = explode("\n", $recipe['directions'][0]);
			foreach ($directions as $direction)
			{
				$directionsElement->appendChild($xmlDoc->createElement("Direction", htmlspecialchars($direction)));
			}
			$recipeElement->appendChild($xmlDoc->createElement("Image", $recipe['img_source']));
			$recipeElement->appendChild($xmlDoc->createElement("Tips", htmlspecialchars($recipe['tips'])));
		}

		// Make the output pretty
		$xmlDoc->formatOutput = true;

		return $xmlDoc->saveXML();
	}

	public function updateUserId()
	{
		//$page = new KB_Pages_Settings();
		//$pageData = $page->save();
		die('0');
	}

	public function saveTagsPage()
	{
		$page = new KB_Pages_Recipe();
		$select = $page->save();

		echo sprintf($this->kitchenbug->editor->prefix->full, $select['recipe_id']);

		die();
	}

	public function saveRecipeInsert()
	{
		if (isset($_REQUEST['recipe_id']))
		{
			$page = new KB_Pages_Recipe();
			$page->insertRecipePostLink($_REQUEST['recipe_id'], $this->version);
			echo sprintf($this->kitchenbug->editor->prefix->full, $_REQUEST['recipe_id']);
		}

		die();
	}

	public function updateSource()
	{

		// Save the source change so we don't go through this again
		$page = new KB_Pages_Recipe();
		$page->save();

		if (isset($_POST['post_id']))
		{
			$select = $page->select($_POST['post_id']);

			// Update source on server so we can find the analysis next time
			$result = KB_Http::getInstance()->request("http://" . $this->kitchenbug->config['plugin']['url'] . "/analyzer/engine/source", true, array('recipe_id' => $select['recipe_id'], 'source' => urlencode($select['source']))
			);
		}

		die();
	}

	public function mcePlugins($plugins)
	{
		$plugins = (array) $plugins;
		$plugins["kitchenbug"] = $this->siteURL . $this->kitchenbug->assets['js'] . '/kbug-mce.js?v=' . $this->version;
		return $plugins;
	}

	function mceButtons($buttons)
	{
		$buttons[] = 'separator';
		$buttons[] = 'kbugRecipe';
		return $buttons;
	}

	function mceFullScreenButtons($buttons)
	{
		$buttons[] = 'separator';
		$buttons['kitchenbug'] = array('title' => 'Kitchenbug',
			'onclick' => "tinyMCE.execCommand('openkitchenbugAddNew');",
			'both' => false);
		return $buttons;
	}

	function mceFullScreenButtonsStyle()
	{
		wp_enqueue_style('fs-style', $this->siteURL . $this->kitchenbug->assets['css'] . '/kbug-fs-button.css');
	}

	public function settingsPageCallback()
	{
		$page = new KB_Pages_Settings;
		$pageData = null;

		foreach ($page->getPageData() as $key => $val)
		{
			if (!$key)
			{
				continue;
			}
			$this->view->$key = $val;
		}

		if (isset($_REQUEST['install-kitchenbug-theme']))
		{
			$url = wp_nonce_url('admin.php?page=kitchenbug_main_menu', 'install-kitchenbug-theme');
			if (false === ($creds = request_filesystem_credentials($url, '', false, false, '')))
			{
				return true; // stop the normal page form from displaying
			}
			// now we have some credentials, try to get the wp_filesystem running
			if (! WP_Filesystem($creds))
			{
				// our credentials were no good, ask the user for them again
				request_filesystem_credentials($url, '', true, false, '');
				return true;
			}

			if (!function_exists('wp_handle_upload'))
				require_once(ABSPATH . 'wp-admin/includes/file.php' );
			$uploadedfile = $_FILES['kbthemezip'];
			$upload_overrides = array('test_form' => false);
			$movefile = wp_handle_upload($uploadedfile, $upload_overrides);
			if (isset($movefile['error']))
			{
				$this->view->kb_file_err = "<div id=\"kb-setting-error-file_updated\" class=\"kb-settings-error\" style=\"margin-left: 20px;width: 370px;\">" .
						$movefile['error'] . "</div>";
			}
			else
			{
				if ($movefile['type'] !== "application/zip")
				{
					$this->view->kb_file_err = "<div id=\"kb-setting-error-file_updated\" class=\"kb-settings-error\" style=\"margin-left: 20px;width: 370px;\">Theme file should be in zip format.</div>";
				}
				else
				{
					try
					{
						// Extract theme in upload folder (overwrite?)
						if (!unzip_file($movefile['file'], ABSPATH . 'wp-content/plugins/kitchenbug/themes/'))
						{
							$this->view->kb_file_err = "<div id=\"kb-setting-error-file_updated\" class=\"kb-settings-error\" style=\"margin-left: 20px;width: 370px;\">A problem has occurred: Failed to unzip theme file.</div>";;
						}

						$page->saveOption(array('customThemeLocation' => str_replace('\\', '/', $movefile['file']),
							  KB_Pages_Settings::THEME => preg_replace('/[\d]+/', '', basename($movefile['file'], '.zip'))));
						$pageData = $page->select();

						$this->view->kb_file_err = "<div id=\"kb-setting-error-file_updated\" class=\"kb-settings-success\" style=\"margin-left: 20px;width: 370px;\">Theme changed successfully.</div>";
					}
					catch (Exception $e)
					{
						$this->view->kb_file_err = "<div id=\"kb-setting-error-file_updated\" class=\"kb-settings-error\" style=\"margin-left: 20px;width: 370px;\">A problem has occurred: " . $e->getMessage() . "</div>";;
					}
				}
			}
		}
		elseif (isset($_REQUEST['submit']))
		{
			$pageData = $page->save();
		}
		else
		{
			$pageData = $page->select();
		}


		if (!isset($pageData[KB_Pages_Settings::THEME]))
		{
			$pageData[KB_Pages_Settings::THEME] = KB_Pages_Settings::DEFAULT_THEME;
		}
		if (!empty($pageData))
		{
			foreach ($pageData as $setKey => $setVal)
			{
				if (!$setKey)
				{
					continue;
				}
				$this->view->$setKey = $setVal;
			}
		}

		$this->view->themes = $this->getThemes();

		echo $this->view->render('options-general');
	}

	public function menuRecipesPageCallback()
	{
		global $wpdb;

		$message = "<div></div>";
		if (isset($_REQUEST['action']))
		{
			if ($_REQUEST['action'] === 'delete' || $_REQUEST['action'] === 'trash')
			{
				$recipesToDelete = array();
				if (is_array($_REQUEST['recipe']))
				{
					foreach ($_REQUEST['recipe'] as $value)
					{
						$recipesToDelete[] = $value;
					}
				}
				else
				{
					$recipesToDelete[] = $_REQUEST['recipe'];
				}

				foreach ($recipesToDelete as $value)
				{
					// Delete post-recipe link
					$wpdb->delete($wpdb->prefix . 'kb_recipes', array('recipeID' => $value), array('%d'));
					// Delete recipe itself
					$wpdb->delete($wpdb->prefix . 'kb_recipes_only', array('recipeID' => $value), array('%d'));
					// Remove the shortcode from the posts
					$shortCode = str_replace('%d', $value, KB_Main::getInstance()->getEditorPrefix());
					$sql = "UPDATE ' . $wpdb->prefix .'_posts SET post_content = replace(post_content, '" . $shortCode . "', '');";
					$wpdb->query($sql);
				}

				if (count($recipesToDelete) > 1)
				{
					$message = '<div id="message" class="updated below-h2">Recipes have been deleted</div>';
				}
				else
				{
					$message = '<div id="message" class="updated below-h2">Recipe has been deleted</div>';
				}
			}
		}

		$kb_recipeListTable = new KB_Recipe_List_Table();
		$kb_recipeListTable->prepare_items();
		//<a href="" class="add-new-h2">Add New</a>
		?>
		<div class="wrap">
			<h2>Recipes</h2>
				<?php echo $message ?>
			<!-- Wrap the table in one to use features like bulk actions -->
			<form id="movies-filter" method="get">
				<!-- Ensure that the form posts back to our current page -->
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<!-- Render the completed list table -->
		<?php $kb_recipeListTable->display() ?>
			</form>

		</div>
		<?php
	}

	public function getThemes()
	{
		$themes_path = ABSPATH . 'wp-content/plugins/kitchenbug/themes';

		$themes = array();

		if (is_dir($themes_path))
		{
			if ($dh = opendir($themes_path))
			{
				while (($file = readdir($dh)) !== false)
				{
					if ($file == '.' || $file == '..')
						continue;

					if (filetype($themes_path . '/' . $file) != 'dir')
						continue;

					if ($theme = $this->getThemeInfo($themes_path . '/' . $file, $file))
					{
						$themes[] = $theme;
					}
				}
				closedir($dh);
			}
		}

		return $themes;
	}

	private function getThemeInfo($theme_dir, $theme_slug)
	{
		if (!file_exists($theme_dir . '/style.css'))
			return null;

		$theme_info = array();

		if (is_dir($theme_dir))
		{
			if ($dh = opendir($theme_dir))
			{
				while (($file = readdir($dh)) !== false)
				{
					// Skip over system folders.
					if ($file == '.' || $file == '..')
						continue;

					// Get the contents of the style.css file, from which we will retrieve the
					// theme's info.
					$contents = file_get_contents($theme_dir . '/style.css');

					// Get the theme info from the style.css contents.
					if (!preg_match("|(?s)/\*(.*?)\*/|i", $contents, $matches))
						return null;

					// Retrieve the different info fields from the theme info section.
					$theme_info['slug'] = $theme_slug;
					$theme_info['theme_name'] = $this->getThemeAttribute($matches[1], 'theme name');
					$theme_info['author'] = $this->getThemeAttribute($matches[1], 'author');
					$theme_info['author_uri'] = $this->getThemeAttribute($matches[1], 'author uri');
					$theme_info['description'] = $this->getThemeAttribute($matches[1], 'description');
					$theme_info['version'] = $this->getThemeAttribute($matches[1], 'version');
					$theme_info['path'] = $theme_dir;
				}
				closedir($dh);

				return $theme_info;
			}
		}

		return null;
	}

	private function getThemeAttribute($theme_header, $attribute)
	{
		preg_match("|(?s)$attribute: ([^\r\n]*)|i", $theme_header, $matches);
		return $matches[1];
	}

	public function addAdminHead()
	{
		?>
			<style type="text/css">
				.mce-btn.kb_hovered i { background-image: url(<?php _e($this->siteURL . $this->kitchenbug->assets['img'] . '/icon_kb-logo_settings_hover.png') ?>) !important; }
				/* .ui-dialog-titlebar-close:before { line-height: 14px !important; } */
				.ui-icon-closethick { display: none !important; }
			</style>
			<script type="text/javascript">
				jQuery(function ($)
				{
					$(window).load(function ()
					{
						$(".mce-btn[aria-label*=Kitchenbug]").hover(
							function ()
							{
								$(this).addClass("kb_hovered");
							},
							function ()
							{
								$(this).removeClass("kb_hovered");
							}
						);
					});
				});
			</script>
		<?php
	}

	public function addAdminMenu()
	{
		// Create top-level menu item
		add_menu_page($this->kitchenbug->config['plugin']['menu_title'], $this->kitchenbug->config['plugin']['menu_title'], 'edit_others_posts', // capability (permissions)
				'kitchenbug_main_menu', // menu slug
				array($this, 'settingsPageCallback'), // Function
				$this->siteURL . $this->kitchenbug->pluginURL . '/application/assets/img/icon_kb-logo_menu.png');
		// Create a sub-menu for settings under the top-level menu
		add_submenu_page('kitchenbug_main_menu', 'General Settings', 'General Settings', 'activate_plugins', 'kitchenbug_main_menu', array($this, 'settingsPageCallback'));
		// Create a sub-menu for recipes under the top-level menu
		add_submenu_page('kitchenbug_main_menu', 'Recipes', 'Recipes', 'edit_others_posts', 'kitchenbug_sub_recipes_menu', array($this, 'menuRecipesPageCallback'));
	}

	public function _pluginActionLinks($links)
	{
		return array_merge(
						array('settings' => sprintf('<a href="%s">%s</a>', get_bloginfo('wpurl') . '/wp-admin/admin.php?page=kitchenbug_main_menu', __('Settings', 'Kitchenbug'))), $links
		);
		return $links;
	}

	public function kbtuts_pointer_load($hook_suffix)
	{
		// Don't run on WP < 3.3
		if (get_bloginfo('version') < '3.3')
			return;

		$screen = get_current_screen();
		$screen_id = $screen->id;

		// Get pointers for this screen
		$pointers = apply_filters('kbtuts_admin_pointers-' . $screen_id, array());
		if (!$pointers || !is_array($pointers))
			return;

		// Get dismissed pointers
		$dismissed = explode(',', (string) get_user_meta(get_current_user_id(), 'dismissed_wp_pointers', true));
		$valid_pointers = array();

		// Check pointers and remove dismissed ones.
		foreach ($pointers as $pointer_id => $pointer)
		{
			// Sanity check
			if (in_array($pointer_id, $dismissed) || empty($pointer) || empty($pointer_id) || empty($pointer['target']) || empty($pointer['options']))
				continue;

			$pointer['pointer_id'] = $pointer_id;
			// Add the pointer to $valid_pointers array
			$valid_pointers['pointers'][] = $pointer;
		}

		// No valid pointers? Stop here.
		if (empty($valid_pointers))
			return;

		// Add pointers style to queue.
		wp_enqueue_style('wp-pointer');
		// Add pointers script to queue. Add custom script.
		wp_enqueue_script('kbtuts-pointer', $this->kitchenbug->assets['js'] . '/kbtuts-pointer.js', array('wp-pointer'));
		// Add pointer options to script.
		wp_localize_script('kbtuts-pointer', 'kbtutsPointer', $valid_pointers);
	}

	public function kbtuts_register_pointer_testing($p)
	{
		$p['kitchenbug_ptr1'] = array(
			'target' => '#toplevel_page_kitchenbug_main_menu',
			'options' => array(
				'content' => sprintf('<h3> %s </h3><p><ul style="list-style-type: circle; margin-left: 20px;"><li>%s</li></p>', __('What\'s new on Kitchenbug?', 'plugindomain'), __('Works on Wordpress in HTTPS', 'plugindomain')
				),
				'position' => array('edge' => 'left', 'align' => 'center'),
				'pointerWidth' => 400
			)
		);
		return $p;
	}

}