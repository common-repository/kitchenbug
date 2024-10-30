<?php 
/**
 * Language class with translation support 
 * @author 
 */
class KB_Lang { 
	/**
	 * @var array language holder
	 */
	private $_language = array();
	/**
	 * @var const default language
	 */ 
	const DEFAULT_LANG = 'en';
	
	public function __construct($language=self::DEFAULT_LANG){ 
		$this->_getLang($language);	
	}
	
	/**
	 * Get language from data file and replace special variables 
	 * @param unknown_type $language
	 */
	private function _getLang($language){
		if (!empty($this->_language[$language])){
			return ; 
		}
		
		$kbInstance = KB_Main::getInstance();
		$file 		= $kbInstance->paths['include'] . '/../application/data/' . $language . '.php'; 
		
		if (!file_exists($file)){
			throw new Exception("File $file doesn't exist"); 
		}
		
		require($file); 
		
		foreach($language as $key=>$val){
			if (preg_match_all('/%([\S]*)?%/',$val,$matches)){
				foreach ($matches[1] as $k=>$v){
					if (isset($kbInstance->config['plugin'][$v])){		
						$val = str_replace("%$v%",$kbInstance->config['plugin'][$v],$val);
						//echo $val,'<Br/>';
					}

					$this->_language[$key] = $val;
				}
				continue ; 
			} 
			$this->_language[$key] = $val;
			
		}
	}
	
	/**
	 * Translate plugin texts
	 * @param string $name
	 */
	public function translate($name){
		if (empty($this->_language[$name])){
			return $name;
		}
		
		return $this->_language[$name];
	}
	
	/**
	 * Incomplete method
	 * @TODO: Check if lang supported by checking existing lang files
	 * 		  Return false for loading default lang 
	 */
	private function _isLangSupported(){
		
	}
}

