<?php


class KB_View
{
	public $kitchenbug;
	public $wordpress;

	/**
	 * Constructor 
	 * Applys output_buffering setting if is off
	 * TODO: Check if output_buffering works well on different PHP versions\ini settings
	 */
	public function __construct(KB_Wordpress $wordpress)
	{
		ini_set('output_buffering', '4096');
		$this->kitchenbug = KB_Main::getInstance();
		$this->wordpress = $wordpress;
	}

	/**
	 * Render .phtml file and return his data 
	 * @param string $name
	 * @return string
	 */
	public function render($name)
	{
		if (!is_file($name))
		{
			$file = $this->kitchenbug->paths['application'] . '/views/' . $name . '.phtml';
		}
		else
		{
			$file = $name;
		}
		if (file_exists($file))
		{
			// Output buffers are stackable, that is, we may call ob_start() while another ob_start() is active
			ob_start();
			require($file);
			$file1 = ob_get_contents();
			ob_end_clean();
		}
		return $file1;
	}

	/**
	 * Magic set
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		if (!$name)
		{
			echo "<pre>";
			var_dump($name, $value);
			debug_print_backtrace();
		}
		$this->$name = $value;
	}

	/**
	 * Magic get
	 * @param string $name
	 * @return string 
	 */
	public function __get($name)
	{
		if (isset($this->$name))
		{
			return $this->$name;
		}
		return null;
	}

	/**
	 * Add js files to wordpress queue
	 * @param array $scripts
	 * @return void
	 */
	public function setScripts(array $scripts)
	{
		foreach ($scripts as $key => $script)
		{
			$this->wordpress->addAsset($script, array('jquery'), (!is_numeric($key)));
		}
	}

	/**
	 * Add css files to wordpress queue 
	 * @param array $links
	 * @return void
	 */
	public function setLinks(array $links, $addPrint = false)
	{
		$ret = '';
		foreach ($links as $key => $link)
		{
			$this->wordpress->addAsset($link);
			$ret .= '<link href="' . $this->wordpress->fullSiteURL . '/application/assets/css/' . $link . '" media="screen" rel="stylesheet" type="text/css" >' . "\n";
			$ret .= '<link href="' . $this->wordpress->fullSiteURL . '/application/assets/css/' . $link . '" media="print" rel="stylesheet" type="text/css" >' . "\n";
		}

		return $ret;
	}

	public function renderWrappedArray(array $array, $tag = 'li')
	{
		foreach ($array as $item)
		{
			echo "<$tag>" . $item . "</$tag>\r\n";
		}
	}
	
	public function renderDirectionsArray(array $array, $tag = 'li')
	{
		$value = 1;
		
		foreach ($array as $item)
		{
			if (substr($item, 0, 1) === ":")
			{
				echo "<$tag class=\"kb-section-header\">" . substr($item, 1) . "</$tag>\r\n";
			}
			else
			{
				echo "<$tag value=\"$value\">" . $item . "</$tag>\r\n";
				$value += 1;
			}
		}
	}
	
	public function showNutHighlight()
	{
		$classified_highlights = array();
		
		// Don't show the nutrition highlights if there are none.
		if (!$this->nutritionHighlights) return;
		
		foreach ($this->nutritionHighlights->classified as $highlight)
		{
			$classified_highlights[] = (object) array(
				'class' => isset($highlight->tag['class']) ? $highlight->tag['class'] : 'normal',
				'rating' => isset($highlight->tag['rating']) ? $highlight->tag['rating'] : 'neutral',
				'value' => $highlight->nfact['value'],
				'units' => $highlight->nfact['units'],
				'desc' => isset($highlight->tag['abbr']) ? $highlight->tag['abbr'] : $highlight->shortDesc,
				'hiddenDesc' => $highlight->nfact['desc'],
				'itemprop' => $highlight->itemprop,
			);
		}
		
		$this->classifiedHighlights = $classified_highlights;
		
		$other_highlights = array(
			(object) array(
				'desc' => 'Serving size',
				'value' => $this->nutritionHighlights->stats->servingSize,
				'units' => 'g',
				'itemprop' => 'servingSize',
			),
			(object) array(
				'desc' => 'Calories from fat',
				'value' => $this->nutritionHighlights->stats->calFromFat,
				'units' => 'kcal',
				'itemprop' => null,
			),
		);
		
		foreach ($this->nutritionHighlights->other as $highlight)
		{
			$other_highlights[] = (object) array(
				'desc' => $highlight->shortDesc,
				'value' => $highlight->nfact['value'],
				'units' => $highlight->nfact['units'],
				'itemprop' => $highlight->itemprop,
			);
		}
		
		$this->otherHighlights = $other_highlights;
		
		ob_start();
		require($this->kitchenbug->paths['application'] . '/views/nutrition_highlight.phtml');
		ob_end_flush();
	}
	
	public function showActionMenu()
	{
		ob_start();
		require($this->kitchenbug->paths['application'] . '/views/action_menu.phtml');
		ob_end_flush();
	}
	
	public function showScaleSelect($min, $max, $select)
	{
		echo '<select class="kb-select-servings">';
		echo "\n";

		for ($i = $min; $i <= $max; $i++)
		{
			echo '<option value="' . $i . '"';
			
			if ($select == $i)
			{
				echo ' selected="selected"';
			}
			
			echo '>' . $i . '</option>';
			echo "\n";
		}
		
		echo '</select>';
		echo "\n";
	}
	
	public function showConvertSelect($select)
	{
		if ($select == 'G' || $select == 'I')
		{
			return; // No need for conversion if measurements are generic
		}
		
		echo '<select class="kb-select-scale">';
		echo "\n";
		echo '<option value="M"';
		
		if ($select == 'M')
		{
			echo ' selected="selected"';
		}
		
		echo '>Metric</option>';
		echo "\n";
		echo '<option value="US"';
		
		if ($select == 'US')
		{
			echo ' selected="selected"';
		}

		echo '>US</option>';
		echo "\n";
		echo '</select>';
		echo "\n";
	}
}