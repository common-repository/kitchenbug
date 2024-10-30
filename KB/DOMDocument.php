<?php
/**
 * Kitchenbug dom handler
*/
class KB_DOMDocument extends DOMDocument {
	/**
	 * @var DOMElement $kBugRecipe
	 */
	public $kBugRecipe = null;
	
	const CONTAINER = "kb-recipe-container"; 
	const UTF8_META = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'; 
    const KB_RECIPE_CONTAINER_REGEX = '%^.*?(.*?)(<div +class *= *["\']kb-recipe-container-start[^>]></div>(.*?)<div +class *= *[\'"]kb-recipe-container-end.*?</div>)(.*?)$%si';
	
	public function __construct($post)
	{
		parent::__construct("1.0", "UTF-8");
		
		if (!@$this->loadHTML(self::UTF8_META . $post))
		{
			return ;
		}
		$this->_isKBPost();
	}
	
	private function _isKBPost()
	{ 
		return $this->kBugRecipe = $this->getElementByClassName(self::CONTAINER); 
	}
	
	/**
 	 * Remove P tags from our recipe without hurting the rest of the post 
	 * @return string 
	 */
	public function rmWpautop($content)
	{	
		if (!@preg_match(self::KB_RECIPE_CONTAINER_REGEX, $content, $regs))
		{
			return $content;
		}
		
		$before_recipe = $regs[1];
		$recipe_content = $regs[3];
		$after_recipe = $regs[4];

		// Manipulate the recipe content.
		$recipe_content = preg_replace("/<p>(.*)<\/p>/", '$1', $recipe_content);
		$recipe_content = preg_replace("/<br>/", '', $recipe_content);
		$recipe_content = preg_replace("/<br \/>/", '', $recipe_content);
		$recipe_content = str_replace("[br]", "<br />", $recipe_content);

		return $before_recipe . $recipe_content . $after_recipe;
	}
	
	/**
	 * @see http://stackoverflow.com/questions/5404941/php-domdocument-outerhtml-for-element
	 */
	public static function DOMinnerHTML($n, $outer=true) {
	    $d = new DOMDocument('1.0');
	    $b = $d->importNode($n->cloneNode(true),true);
	    $d->appendChild($b); $h = $d->saveHTML();
	    // remove outter tags
	    if (!$outer) $h = substr($h,strpos($h,'>')+1,-(strlen($n->nodeName)+4));
	    return $h;
	}
		
	/**
	* Get all elements that have a tag of $tag and class of $className
	*
	* @param string $className The class name to search for
	* @param string $tag       Tag of the items to search
	* @return array            Array of DOMNode items that match
	*/
	public function getElementsByClassName($className, $tag="*") {
		$nodes = array();
		$domNodeList = $this->getElementsByTagName($tag);
		for ($i = 0; $i < $domNodeList->length; $i++) {
			$item = $domNodeList->item($i)->attributes->getNamedItem('class');
			if ($item) {
				$classes = explode(" ", $item->nodeValue);
				for ($j = 0; $j < count($classes); $j++) {
					if ($classes[$j] == $className) {
						$nodes[] = $domNodeList->item($i);
					}
				}
			}
		}
		return $nodes;
	}
	
	/**
	 * Convenience method to return a single element by class name when we know there's only going to be one
	 *
	 * @param string $className The class name to search for
	 * @param string $tag       Tag of the items to search
	 * @return array            Array of DOMNode items that match
	 */
	public function getElementByClassName($className, $tag="*") {
		$nodes = $this->getElementsByClassName($className, $tag);
		return count($nodes) == 1 ? $nodes[0] : $nodes;
	}
	
}