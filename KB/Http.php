<?php

class KB_Http
{
	private $_headers = array();
	static public $_instance = false;

	const USER_AGENT = "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5";
	const HTTP = 'http://';
	const HTTPS = 'https://';

	/**
	 * Singleton design pattern 
	 * @return KB_Http
	 */
	public function getInstance()
	{
		if (!self::$_instance)
		{
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	/**
	 * Singleton handling 
	 */
	private function __construct()
	{
		
	}

	private function __clone()
	{
		
	}

	/**
	 * HTTP Request handler with file_get_contents 
	 * @param boolean $isPost
	 * @param array $postParams
	 * @return array 
	 */
	public function request($url, $isPost = false, $postParams = array())
	{
		if(!class_exists('WP_Http'))
		{
			include_once(ABSPATH . WPINC . '/class-http.php');
		}
		
		try
		{
			$request = new WP_Http;
			if ($isPost)
			{
				return $request->request($url, array('method' => 'POST', 'body' => $postParams));
			}
			else
			{
				return $request->request($url);
			}
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
		
		
	}

}

