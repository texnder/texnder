<?php 
namespace Routex\method;

use Routex\Exception\RouteException;
use Aditex\src\Container;
use Routex\http\Redirect;

trait parserTrait{

	/**
	 * first argument for get or post method
	 *
	 * from Routex\Route class..
	 *
	 * @var string
	 */
	public $authUrl;

	/**
	 * clean Url
	 *
	 * @var string
	 */
	public $url;
	
	/**
	 * if get or post method 2nd argument is
	 *
	 * string then it should be like Controller@method
	 *
	 * @var string
	 */
	public $controller;

	/**
	 * controller method..
	 *
	 * @var string
	 */
	public $method;

	/**
	 * response view path
	 *
	 * @var string
	 */
	public $view;

	/**
	 * response data sending to view
	 *
	 * @var string
	 */
	public $data;

	/**
	 * set when controller & view path not available
	 *
	 * @var any
	 */
	public $callback;



	/**
	 * get Route for view
	 *
	 * @param 		$view 		closure function or string
	 * @return 		string
	 */
	private function getViewRoute($view)
	{
		if (is_object($view)) {
			return $view(Container::$storedServices);
		}elseif (is_string($view)) {
			$this->setControllerRoute($view);
		}else{
			throw new RouteException("syntax error! argument 2 should be string or callback function");
		}
	}

	/**
	 * set Controller Route for request url
	 *
	 * @param 	$view 	string
	 */
	private function setControllerRoute($view)
	{
		$view = explode("@", $view);
		if (count($view) == 2) {
			$this->controller = $view[0];
			$this->method = $view[1];
		}else{
		 	throw new RouteException("syntax error!");
		}
	}

	/**
	 * resolve row Url 
	 *
	 * @param 	string 	$rowUrl
	 */
	public function resolveUrl($rowUrl)
	{

		$this->authUrl = $rowUrl;
		$this->url = $this->absoluteUrlPath($rowUrl);
		
		if (get_called_class() === 'Routex\method\get') {
			$this->queryKeys = $this->queryKeys($rowUrl);
		}
	}

	/**
	 * removing first & last slash form the urls..
	 * return exploded url Array using "/"
	 *
	 * @var 	$url 	string
	 *
	 * @return 	Array
	 */
	protected function trimSlashs(string $url)
	{
		$url = trim($url,"/");
		return explode("/", $url);
	}

	/**
	 * splitting url path from url query..
	 * Data keys name for GET URL is under curlly braces {key}..
	 *
	 * @var 	$url 	string
	 * @return 	string
	 */
	protected function absoluteUrlPath(string $url)
	{
		if ($param_pos = strpos($url,"{")) {

			$url_path = substr($url,0,$param_pos);
			
			return implode("/", $this->trimSlashs($url_path));
		}else{
			return implode("/", $this->trimSlashs($url));
		}
	}

	/**
	 * parsing query key form url..
	 * query keys are under curlly braces {key}..
	 * returns array of keys name..
	 * 
	 * @var 		string 		$url
	 * @return 	array
	 */
	protected function queryKeys(string $url)
	{
		$queryKey = array();

		if ($param_pos = strpos($url,"{")) {

			$url = substr($url,$param_pos);

			foreach ($this->trimSlashs($url) as $key => $url_words) {
				if (preg_match("/^{+[a-zA-Z0-9-]+}$/", $url_words)) {
					
					$param = preg_replace("/[{}]/", "", $url_words);
					$queryKey[$key] = $param;
				}
			}
		}
		return $queryKey;
	}

	/**
	 * get View path or callback
	 * 
	 * @param 	any 	$view
	 */
	public function getView($view)
	{
		$viewRoute = $this->getViewRoute($view);

		if (is_object($viewRoute)) {

			if ($viewRoute instanceof Redirect) {
				Redirect::$redirectContainer[$this->url] = $viewRoute;
			}
			
			 $this->setView($viewRoute);
		}

		$this->callback = $viewRoute;
	}

	/**
	 * set view path and data
	 * 
	 * @param 	object 	$viewRoute
	 */
	public function setView($viewRoute)
	{
		
		$this->view = isset($viewRoute->path) ? $viewRoute->path : null;
		
		$this->data = isset($viewRoute->data) ? $viewRoute->data : null;
	}
}