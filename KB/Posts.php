<?php

require_once('Pages/Settings.php');

class KB_Posts
{ 
	public $view;
	private $settingsData;
	private $theme_path;
	
	public function __construct()
	{
		$settings = new KB_Pages_Settings();
		$this->settingsData = $settings->select();
		$this->theme_path = $this->getThemePath();
	}
	
	public function manipulateContent($content)
	{
		$kb_main = KB_Main::getInstance();
		global $post;
		
		if ((strpos($content, $kb_main->getEditorPrefixReplaced()) === false) || $this->_isValidPreviewPost($post->ID))
		{
			return $content;
		}
		else
		{
			if (is_archive() || is_home())
			{
				$content = $this->renderExcerptToPost($content, $post->ID);
				return $content;
			}
			else
			{
				$content = $this->renderRecipeToPost($content, $post->ID);
				return $content;
			}
		}	
	}
	
	public function renderRecipeToPost($content, $postID)
	{
		$kb_main = KB_Main::getInstance();

		$recipe_data = $this->getRecipeViewData($postID);
		if (is_null($recipe_data))
		{
			return $content;
		}
		
		// Constructing the page from the wrappers, common elements and the theme's template.
		$recipe_view = $this->view->render('wrapper_start');
		$recipe_view .= $this->view->render($this->theme_path . '/template-recipe.php');
		$recipe_view .= $this->view->render('wiki_bubble');
		$recipe_view .= $this->view->render('footer');
		$recipe_view .= $this->view->render('wrapper_end');
		
		$recipe_view = str_replace("\n", "", $recipe_view);
		
		$kb_placeholder = $kb_main->getEditorPrefix();
		$kb_placeholder = sprintf($kb_placeholder, $recipe_data->id);
		$content = preg_replace("/" . preg_quote($kb_placeholder) . "/i", $recipe_view, $content);
		
		return $content;
	}
	
	public function printRecipe()
	{
		$postID = -1;
		if (isset($_REQUEST['print']))
		{
			$postID = $_REQUEST['print'];
		}
		else
		{
			return;
		}
		
		$recipe_data = $this->getRecipeViewData($postID);
		if (is_null($recipe_data))
		{
			return;
		}
		
		// Correct the display of the description break.
		$this->view->description = preg_replace("/\[br[^\]]*\]/", "<br />", $this->view->description);
			
		$recipe_view = $this->view->render('print_wrapper_start');
		$recipe_view .= $this->view->render($this->theme_path . '/template-recipe-print.php');
		$recipe_view .= $this->view->render('footer');
		$recipe_view .= $this->view->render('print_wrapper_end');
		echo $recipe_view;
		exit();
	}
	
	public function renderExcerptToPost($content, $postID)
	{
		$recipe_data = $this->getRecipeViewData($postID);
		if (is_null($recipe_data) || !isset($this->settingsData['excerptOn']) ||
			$this->settingsData['excerptOn'] == 'off')
		{
			return $this->renderRecipeToPost($content, $postID);
		}
			
		$excerptContent = $recipe_data->name;
		if (!empty($recipe_data->category) && !empty($recipe_data->cuisine))
		{
			$excerptContent .= ' (' . $recipe_data->category . ' | ' . $recipe_data->cuisine . ')';
		}
		
		if (!empty($recipe_data->description))
		{
			$excerptContent .= ': <br/>' . preg_replace("/\[br[^\]]*\]/", "<br />", $recipe_data->description);
		}
		
		$kb_placeholder = KB_Main::getInstance()->getEditorPrefix();
		$kb_placeholder = sprintf($kb_placeholder, $recipe_data->id);
			
		return $excerptContent;
	}
	
	public function getThemeName()
	{
		$settings = KB_Pages_Settings::read();
		
		if (isset($settings[KB_Pages_Settings::THEME]))
		{
			return $settings[KB_Pages_Settings::THEME];
		}
		else
		{
			return 'starter';
		}
	}
	
	public function getThemePath()
	{
		$kb_main = KB_Main::getInstance();
		
		$theme_name = $this->getThemeName();
		return $kb_main->themesPath . '/' . $theme_name;
	}
	
	public function getThemeURL()
	{
		$kb_main = KB_Main::getInstance();
		
		$theme_name = $this->getThemeName();
		return get_site_url() . $kb_main->themes . '/' . $theme_name;
	}
	
	public function getPluginURL()
	{
		$kb_main = KB_Main::getInstance();
		
		return get_site_url() . $kb_main->pluginURL;
	}
	
	public function getRecipeViewData($postID)
	{
		$page = new KB_Pages_Recipe();
		$texts = $page->getPageData();
		$page_data = $page->select($postID);
		
		if (is_null($page_data))
		{
			// No recipe exists for this post ID, there are various reasons for this, usually an uninstall 
			// of the plugin without removal of the shortcode.
			return null;
		}

		$prep_time = $this->formatTime($page_data['prep_time']);
		$cook_time = $this->formatTime($page_data['cook_time']);
		$total_time = $this->formatTime($page_data['prep_time'] + $page_data['cook_time']);

		// Get the directions
		$workdirections = array();
		$directions = array();
		
		foreach ($page_data['directions'] as $direction)
		{
			$workdirections = array_merge($workdirections, explode("\n", $direction));
		}
		$workdirections = array_filter($workdirections);
		foreach ($workdirections as $direction)
		{
			$directions[] = $this->addAnchorsToURLs($direction);
		}
		
		// Set the tips
		$tips = array();
		if (isset($page_data['tips']))
		{
			$page_data['tips'] = array_filter(explode("\n", $page_data['tips']));
		}
		foreach ($page_data['tips'] as $tip)
		{
			$tips[] = $this->addAnchorsToURLs($tip);
		}

		// Get the nutrition highlights.
		$nutrition_highlights = null;
		
		if ($page_data['analyzed']['nfacts'])
		{
			$nutrition_highlights = $this->getNutritionHighlights(
				$page_data['analyzed']['nfacts'], 
				$page_data['analyzed']['tags'], 
				$page_data['analyzed']['servingSizeInGrams']
			);
		}

		$category = isset($texts['page']->categories[$page_data['category']]) ? $texts['page']->categories[$page_data['category']] : '';
		$cuisine = isset($texts['page']->cuisines[$page_data['cuisine']]) ? $texts['page']->cuisines[$page_data['cuisine']] : '';

		$post_permalink = get_permalink($postID);
		$print_link = '';

		if (preg_match("|/$|", get_permalink($postID)))
		{
			$print_link = preg_replace("|/$|", '', $post_permalink) . '?print=' . $postID;
		}
		else if (preg_match("|\?|", get_permalink($postID)))
		{
			$print_link = $post_permalink . '&print=' . $postID;
		}
		else
		{
			$print_link = $post_permalink . '?print=' . $postID;
		}
		
		// A hack for wordpress' rendering. Will be removed when printing the recipe.
		$description = preg_replace("/<br[^>]*>/", "[br]", nl2br($page_data['intro']));
		// Add anchors to the URLs inside the description
		$description = $this->addAnchorsToURLs($description);
		
		
		$ingredients_description = isset($page_data['analyzed']['ingredients_description']) ? json_encode($page_data['analyzed']['ingredients_description']) : '';

		$recipe_data = (object) array(
			'id' => $page_data['recipe_id'],
			'publisherName' => get_bloginfo('name'),
			'publisherURL' => get_bloginfo('url'),
			'postPermalink' => $post_permalink,
			'printAlternative' => $texts['page']->printAlternative,
			'digInAlternative' => $texts['page']->digInAlternative,
			'collectAlternative' => $texts['page']->collectAlternative,
			'printPermalink' => $print_link,
			'themeURL' => $this->getThemeURL(),
			'pluginURL' => $this->getPluginURL(),
			'name' => $page_data['name'],
			'category' => $category,
			'cuisine' => $cuisine,
			'description' => $description,
			'image' => $page_data['img_source'],
			'minServings' => $page_data['analyzed']['min_servings'],
			'maxServings' => $page_data['analyzed']['max_servings'],
			'servings' => $page_data['servings'],
			'scale' => $page_data['analyzed']['system'],
			'prepTime' => $prep_time->time,
			'prepTimeContent' => $prep_time->timeISO8601,
			'cookTime' => $cook_time->time,
			'cookTimeContent' => $cook_time->timeISO8601,
			'totalTime' => $total_time->time,
			'totalTimeContent' => $total_time->timeISO8601,
			'ingredients' => json_decode($page_data['analyzed']['ingredients']),
			'directions' => $directions,
			'tips' => $tips,
			'nfacts' => $page_data['analyzed']['nfacts'],
			'autoTags' => $page_data['analyzed']['tags'],
			'nutritionHighlights' => $nutrition_highlights,
			'nutritionOn' => isset($page_data['nutritionOn']) ? $page_data['nutritionOn'] : 'on',
			'servingSizeInGrams' => $page_data['analyzed']['servingSizeInGrams'],
			'ingredientsDescription' => $ingredients_description,
			'isFeed' => is_feed(),
			'ajaxURL' => admin_url('admin-ajax.php'),
		);
		
		foreach ($recipe_data as $var => $value)
		{
			$this->view->$var = $value;
		}
		
		
		if (isset($this->settingsData['wikilinksOn']))
		{
			$this->view->wikilinksOn = $this->settingsData['wikilinksOn'];
		}
		else
		{
			$this->view->wikilinksOn = 'off';
		}
		
		if (!is_archive() && !is_home())
		{
			if (isset($this->settingsData['collectbuttonOn']))
			{
				$this->view->collectbuttonOn = $this->settingsData['collectbuttonOn'];
			}
			else
			{
				$this->view->collectbuttonOn = 'off';
			}
		}
		
		return $recipe_data;
	}
	
	private function addAnchorsToURLs($text)
	{
		// Detect URLs inside the description and add a link to them.
		$urls = preg_match_all("/(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w\.-]*)*\/?/i", $text, $matches);

		foreach ($matches[0] as $url)
		{
			if (!preg_match("/^https?:\/\//", $url))
			{
				$full_url = 'http://' . $url;	
			}
			else
			{
				$full_url = $url;	
			}
			
			$text = preg_replace("|$url|", "<a href=\"$full_url\" target=\"_blank\">$url</a>", $text);
		}

		return $text;
	}
	
	public function formatTime($time_in_minutes)
	{
		$time = sprintf(
			"%02d:%02d", 
			abs((int)$time_in_minutes / 60), 
			abs((int)$time_in_minutes % 60)
		);
		$time_ISO8601 = sprintf(
			"PT%dH%dM",
			abs((int)$time_in_minutes / 60), 
			abs((int)$time_in_minutes % 60)
		);
		
		return (object) array(
			'time' => $time,
			'timeISO8601' => $time_ISO8601,
		);
	}
	
	public function getNutritionHighlights($nfacts, $auto_tags, $serving_size)
	{
		$classified_highlights = array();
		$other_highlights = array();
		
		$classified_highlights_codes = array(
			'208' => 'cal',
			'204' => 'fat',
			'606' => 'sat fat',
			'601' => 'chol',
			'307' => 'sodium',
			'205' => 'carbs',
		);

		$other_highlights_codes = array(
			'291' => 'fiber',
			'203' => 'protein',
			'269' => 'sugar',
		);
		
		$highlights_rich_snippets_prop = array(
			'208' => 'calories',
			'204' => 'fatContent',
			'606' => 'saturatedFatContent',
			'601' => 'cholesterolContent',
			'307' => 'sodiumContent',
			'205' => 'carbohydrateContent',
			'291' => 'fiberContent',
			'203' => 'proteinContent',
			'269' => 'sugarContent',
		);	
		
		foreach ($classified_highlights_codes as $code => $desc)
		{
			$classified_highlights[$code] = (object) array(
				'nfact' => $nfacts[$code],
				'tag' => isset($auto_tags[$code]) ? $auto_tags[$code] : null,
				'shortDesc' => $desc,
				'itemprop' => $highlights_rich_snippets_prop[$code],
			);
		}

		foreach ($other_highlights_codes as $code => $desc)
		{
			$other_highlights[$code] = (object) array(
				'nfact' => $nfacts[$code],
				'shortDesc' => $desc,
				'itemprop' => $highlights_rich_snippets_prop[$code],
			);
		}
		
		$stats = (object) array(
			'servingSize' => $serving_size,
			'calFromFat' => $nfacts['cal_from_fat'],
		);
		
		$nutrition_highlights = (object) array(
			'classified' => $classified_highlights,
			'stats' => $stats,
			'other' => $other_highlights,			
		);

		return $nutrition_highlights;
	}

	public function footerAction()
	{
		$footer_html = $this->view->render('overlay');
		$footer_html .= $this->view->render('recipe_explorer');
		echo $footer_html;
	}

	/**
	 * Check if we need to manipulate only one post 
	 * @param int $postID
	 * @return bool
	 */
	private function _isValidPreviewPost($postID)
	{
        return isset($_GET['preview']) && isset($_GET['p']) && $_GET['p'] != $postID;
	}

	/**
	 * Get & save post id on new posts 
	 * @param int $post_ID
	 * @TODO: check that $post_ID always exists
	 * @return void 
	 */
	public static function savePostCallback($post_ID)
	{
		KB_Wordpress::$postID = @$post_ID;
	}
	
	/**
	 * Delete recipe while deleting post (clear user's db) 
	 * @return void 
	 */
	public static function deletePostCallback($post_ID)
	{
		//TODO: remove this line
		if (empty($post_ID)) { return ; } 
		$page = new KB_Pages_Recipe;
		$page->delete($post_ID);
	}
}