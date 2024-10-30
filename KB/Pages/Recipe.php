<?php

// Utility for converting object to array
function object_to_array($data)
{
	if ((!is_array($data)) and (!is_object($data)))
		return 'xxx'; //$data;

	$result = array();

	$data = (array) $data;
	foreach ($data as $key => $value)
	{
		if (is_object($value))
			$value = (array) $value;
		if (is_array($value))
			$result[$key] = object_to_array($value);
		else
			$result[$key] = $value;
	}

	return $result;
}

class KB_Pages_Recipe
{
	public $postID;
	protected $_tableName = 'kb_recipes';
	const POST_NAME = 'recipe';

	final public function __construct()
	{
		$this->postID = (int) @$_POST['post_id'];
		if ($this->postID == null)
		{
			$this->postID = (int) @$_GET['post'];
		}

		$this->lang = new KB_Lang;
	}

	/**
	 * Translate Texts with KB_Lang instance (just more comfortable to use)
	 * @param string $name
	 * @return string
	 */
	final protected function _translate($name)
	{
		return $this->lang->translate($name);
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
		$strPost = json_decode(stripslashes($_POST[$name]), TRUE);

		foreach ($strPost[$name] as $key => &$val)
		{
			if (is_array($val))
			{
				unset($val);
				continue;
			}

			$val = htmlspecialchars(stripslashes($val));
		}

		return $strPost;
	}

	/**
	 * Save post data & query current
	 * @return Array
	 */
	public function save()
	{
		$post = $this->getPost(self::POST_NAME);
		$select = $this->select($this->postID);

		if (empty($post) || !is_array($post))
		{
			return isset($select) ? $select : false;
		}

		if (null === $select)
		{
			$this->insert(array($this->postID => $post['recipe']));
		}
		else
		{
			$data[$this->postID] = array_merge($select, $post['recipe']);
			$this->update($data);
		}

		return $post['recipe'];
	}

	public function insertRecipePostLink($recipeID, $pluginVersion)
	{
		global $wpdb;

		if ($this->postID == 0)
		{
			return;
		}

		// Check if recipe already exists for this post
		$postHasLink = $wpdb->get_row("SELECT post_id FROM " . $wpdb->prefix . 'kb_recipes WHERE post_id=' . $this->postID);

		if ($postHasLink)
		{
			// Update a post-recipe link
			$wpdb->update($wpdb->prefix . $this->_tableName,
				array('recipeID' => $recipeID, 'kb_plugin_version' => $pluginVersion),
				array('post_id' => $this->postID),
				array('%d', '%s')
				);
		}
		else
		{
			// Insert a new post-recipe link
			$wpdb->insert($wpdb->prefix . $this->_tableName,
				array('post_id' => $this->postID, 'recipeID' => $recipeID, 'kb_plugin_version' => $pluginVersion),
				array('%d', '%d', '%s')
				);
		}
	}

	public function selectAll()
	{
		global $wpdb;
		$results = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . $this->_tableName . '_only');
		$recipes = array();

		foreach ($results as $recipe)
		{
			$recipes[] = $this->convertToDatafromDB($recipe);
		}

		return $recipes;
	}

	public function getRecipesMetadata()
	{
		global $wpdb;
		$results = $wpdb->get_results("SELECT recipeID, recipeName FROM " . $wpdb->prefix . $this->_tableName . '_only ORDER BY recipeName ASC');
		$recipes = array();

		foreach ($results as $recipe)
		{
			$recipes[] = array('recipeID' => $recipe->recipeID, 'recipeName' => $recipe->recipeName);
		}

		return $recipes;
	}

	public function select($postId)
	{
		global $wpdb;

		$recipe = $wpdb->get_row("SELECT recipes.* FROM " . $wpdb->prefix . $this->_tableName . ' AS posts JOIN ' .
			$wpdb->prefix . $this->_tableName . '_only as recipes ON posts.recipeID = recipes.recipeID ' .
			'WHERE posts.post_id=' . $postId);

		return $this->convertToDatafromDB($recipe);
	}

	private function convertToDatafromDB($dbData)
	{
		if ($dbData == null)
		{
			return null;
		}

		$data = json_decode($dbData->content);
		$data = object_to_array($data);

		return $data;
	}

	public function update($data)
	{
		global $wpdb;

		if (isset($data))
		{
			$value = reset($data);

			$recipeID = $value['recipe_id'];
			$pluginVersion = $recipeName = '';
			$engineVersion = '1.0.0';
			$preparedDate = date('Y-m-d H:i:s', time());

			if (isset($value['plugin_version']))
			{
				$pluginVersion = $value['plugin_version'];
			}

			if (isset($value['engine_version']))
			{
				$engineVersion = $value['engine_version'];
			}

			if (isset($value['name']))
			{
				$recipeName = $value['name'];
			}

			// Check if recipe already exists
			$recipeHasID = $wpdb->get_row("SELECT recipeID FROM " . $wpdb->prefix . 'kb_recipes_only WHERE recipeID=' . $recipeID);

			// Insert a post-recipe link
			$wpdb->update($wpdb->prefix . $this->_tableName,
				array('recipeID' => $recipeID, 'kb_plugin_version' => $pluginVersion),
				array('post_id' => $this->postID),
				array('%d', '%s')
				);

			// Insert into recipes tables
			if (isset($recipeHasID))
			{
				$wpdb->update($wpdb->prefix . 'kb_recipes_only',
					array('recipeName' => $recipeName,
						'content' => json_encode($data[$this->postID]),
						'engine_version' => $engineVersion,
						'lastupdate' => $preparedDate),
					array('recipeID' => $recipeID),
					array('%s', '%s', '%s', '%s'));
			}
			else
			{
				$wpdb->insert($wpdb->prefix . 'kb_recipes_only',
					array('recipeID' => $recipeID,
						'recipeName' => $recipeName,
						'content' => json_encode($data[$this->postID]),
						'engine_version' => $engineVersion,
						'lastupdate' => $preparedDate),
					array('%d', '%s', '%s', '%s', '%s'));
			}

		}
	}

	public function insert($data)
	{
		global $wpdb;

		if (isset($data))
		{
			$value = reset($data);

			if (!isset($value['recipe_id']))
				return;

			$recipeID = $value['recipe_id'];
			$pluginVersion = $recipeName = '';
			$engineVersion = '1.0.0';
			$preparedDate = date('Y-m-d H:i:s', time());

			if (isset($value['plugin_version']))
			{
				$pluginVersion = $value['plugin_version'];
			}

			if (isset($value['engine_version']))
			{
				$engineVersion = $value['engine_version'];
			}

			if (isset($value['name']))
			{
				$recipeName = $value['name'];
			}

			// Check if recipe already exists
			$recipeHasID = $wpdb->get_row("SELECT recipeID FROM " . $wpdb->prefix . 'kb_recipes_only WHERE recipeID=' . $recipeID);

			// Insert a post-recipe link
			$wpdb->insert($wpdb->prefix . $this->_tableName,
				array('post_id' => $this->postID, 'recipeID' => $recipeID, 'kb_plugin_version' => $pluginVersion),
				array('%d', '%d', '%s'));

			// Insert into recipes tables
			if (isset($recipeHasID))
			{
				$wpdb->update($wpdb->prefix . 'kb_recipes_only',
					array('recipeName' => $recipeName,
						'content' => json_encode($data[$this->postID]),
						'engine_version' => $engineVersion,
						'lastupdate' => $preparedDate),
					array('recipeID' => $recipeID),
					array('%s', '%s', '%s', '%s'));
			}
			else
			{
				$wpdb->insert($wpdb->prefix . 'kb_recipes_only',
					array('recipeID' => $recipeID,
						'recipeName' => $recipeName,
						'content' => json_encode($data[$this->postID]),
						'engine_version' => $engineVersion,
						'lastupdate' => $preparedDate),
					array('%d', '%s', '%s', '%s', '%s'));
			}

		}
	}

	public function getPageData()
	{
		$page = new stdClass();

		$page->recipeName = $this->_translate('Recipe Name');
		$page->shortDescription = $this->_translate('Short Description');
		$page->prepTime = $this->_translate('Prep Time (hh:mm)');
		$page->cookTime = $this->_translate('Cook Time (hh:mm)');
		$page->Yield = $this->_translate('Servings');
		$page->uploadImageTitle = $this->_translate('WordPress Gallery Image');
		$page->uploadImage = $this->_translate('Open Gallery');
		$page->uploadImageInstructions = $this->_translate("Choose an image from the gallery and click on `Insert into Post`");
		$page->ingredients = $this->_translate('Ingredients');
		$page->directions = $this->_translate('Directions');
		$page->cookingTips = $this->_translate('Cooking Tips');
		$page->tags = $this->_translate('Tags');
		$page->autoTagsInstructionsBody = $this->_translate('Click on auto tag to add to list');
		$page->autoTagsInstructionsHead = $this->_translate('Auto Tags');
		$page->noTagsFound = $this->_translate('No tags found.');
		$page->continue = $this->_translate('Continue');
		$page->addRecipeToPost = $this->_translate('Add recipe to post');
		$page->usefullTips = $this->_translate('Useful tips');
		$page->forceContinue = $this->_translate('Skip');
		$page->tryAgain = $this->_translate('Try Again');
		$page->category = $this->_translate('Category');
		$page->cuisine = $this->_translate('Cuisine');
		$page->printAlternative = $this->_translate('Take it to the kitchen!');
		$page->collectAlternative = $this->_translate('Collect to your recipe box');
		$page->digInAlternative = $this->_translate('Detailed nutritional info');
		$page->showNutritionalValues = $this->_translate('Show nutritional values for the recipe');

		$page->cuisines = array(
			1 => 'American',
			2 => 'Asian',
			26 => 'Australian',
			3 => 'BBQ',
			4 => 'Cajun',
			5 => 'Canadian',
			6 => 'Chinese',
			7 => 'English and Irish',
			8 => 'French',
			9 => 'German',
			10 => 'Hungarian',
			11 => 'Indian',
			12 => 'Italian',
			13 => 'Jamaican',
			14 => 'Japanese',
			15 => 'Mediterranean or Greek',
			16 => 'Mexican',
			17 => 'Moroccan',
			27 => 'Peruvian',
			18 => 'Portuguese',
			19 => 'Russian',
			20 => 'Scandinavian',
			21 => 'Soul food',
			22 => 'Spanish',
			23 => 'Swiss',
			24 => 'Thai',
			25 => 'Vietnamese',
		);

		$page->categories = array(
			1 => 'Appetizers',
			2 => 'Beverages',
			3 => 'Breakfast and Brunch',
			10 => 'Cakes and Cookies',
			4 => 'Condiments and Sauces',
			5 => 'Desserts',
			6 => 'Main Dishes',
			7 => 'Salads',
			8 => 'Side Dishes',
			9 => 'Soups',
			11 => 'Thanksgiving',
			12 => 'Halloween',
		);

		$page->pTips = array(
			1 => $this->_translate('pTip1'),
			2 => $this->_translate('pTip2'),
			3 => $this->_translate('pTip3'),
			4 => $this->_translate('pTip4'),
			5 => $this->_translate('pTip5'),
		);

		$page->recipelist = $this->getRecipesMetadata();

		return array(
			'analyzing' => $this->_translate('Analyzing...'),
			'savingRecipe' => $this->_translate('Saving Recipe...'),
			'updatingRecipe' => $this->_translate('Updating Recipe...'),
			'page' => $page,
		);
	}

	public function delete()
	{
		if (!isset($_GET['post']))
		{
			return false;
		}

		$postID = (int) $_GET['post'];

		global $wpdb;

		$recipe = $wpdb->query("DELETE FROM " . $wpdb->prefix . $this->_tableName . " where post_id = '" . $postID . "'");

		return true;
	}

}