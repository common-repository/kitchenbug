<?php

require_once('DOMDocument.php');

class KB_User extends KB_Wordpress
{
	public $postsPage;

	public function __construct()
	{
		parent::__construct();
		$this->init();
	}

	// Callback function for enqueing all scripts
	function callback_enqueue_scripts()
	{
		$this->addAsset('controls.js', array('jquery'));
		$this->addAsset('RecipeConverter.js', array('jquery'));
		$this->addAsset('RecipeExplorer.js', array('jquery'));
		$this->addAsset('WikiBubble.js', array('jquery'));
		$this->addAsset('kbug.js', array('jquery'));
		$this->addAsset('ScreenOverlay.js', array('jquery'));
		$this->addAsset('jquery-hotkeys.js', array('jquery'));

		$this->addAsset('recipe-reset.css');
		$this->addAsset('recipe-view.css');
		$this->addAsset('analyzed-by.css');
		$this->addAsset('wiki-bubble-ie8.css');
		$this->addAsset('wiki-bubble.css');
		$this->addAsset('action-menu.css');
		$this->addAsset('clearfix.css');
		$this->addAsset('nut-highlights.css');
		$this->addAsset('recipe-explorer-ie8.css');
		$this->addAsset('recipe-explorer.css');

		// Load themes
		$settings = KB_Pages_Settings::read();

		if (isset($settings[KB_Pages_Settings::THEME]))
		{
			$this->addTheme($settings[KB_Pages_Settings::THEME]);
		}
		else
		{
			$this->addTheme('starter');
		}

	}

	public function init()
	{
		// Enque all the scripts here since we need to do this
		// after the plug-in has finished the init part
		add_action('wp_enqueue_scripts', array($this, 'callback_enqueue_scripts'));

		// Add IE8 filter
		add_filter('style_loader_tag', array($this, 'ie_conditional_comments' ), 10, 2 );

		/*
		 * @ WE NEED THOSE COMMENTS
		 * It's critical we get to process the posts before anything else has a
		 * chance to mess with them so specify a ridiculously high priority here
		 */
		$this->postsPage = new KB_Posts;
		$this->postsPage->view = $this->view;
		// Add post manipulation actions
		if (isset($_REQUEST['print']))
		{
			add_action('wp_headers', array($this->postsPage, 'printRecipe'), 0);
		}
		else
		{
			add_filter('the_content', array($this->postsPage, 'manipulateContent'), 2);
			// Remove wpautop from our recipe
			add_filter('the_content', array($this, 'rm_wpautop'), 12);
		}

		add_action('wp_footer', array($this->postsPage, 'footerAction'), 2);
	}

	/**
	 * remove auto wp p tags from kb recipe
	 * @param string $content
	 * @return string
	 */
	public function rm_wpautop($content)
	{
		$kbDomDoc = new KB_DOMDocument($content);
		$content = $kbDomDoc->rmWpautop($content);
		return $content;
	}

	public function ie_conditional_comments($tag, $handle)
	{
		if ($handle === 'wiki-bubble-ie8')
		{
            $tag = '<!--[if lt IE 9]>' . $tag . '<![endif]-->';
		}

		if ($handle === 'recipe-explorer-ie8')
		{
            $tag = '<!--[if lt IE 9]>' . $tag . '<![endif]-->';
        }
        return $tag;
	}

	/**
	 * Check if is print mode
	 * @return void
	 */
	private function _isPrintMode()
	{
		return (bool) @$_REQUEST['KB_Print'];
	}
}