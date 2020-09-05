<?php

if (! function_exists("view")) {
	/**
	 * function view is for routing,
	 * it sets view path and data
	 * @param   	string 	$path
	 * @param   	array 	$var
	 * @return   	object  stdClass
	 */
	function view(string $path,array $var = [])
	{
		$view = new stdClass;
		$view->path = preg_replace("/\./", "/", $path);
		if (count($var) > 0) {
			$view->data = $var;
		}
		return $view;
	}
}

if (! function_exists("E404")) {
	/*
	* Error 404 when page not found..
	*/
	function E404()
	{	
		header("HTTP/1.0 404 Not Found");
		die();
	}
}

if (! function_exists('redirect')) {
	/*
	* redirect url to different location
	*/
	function redirect(string $url)
	{
		return new Routex\http\Redirect($url);
	}
}


if (! function_exists("url")) {
	/*
	* full url is for view pages,
	* to link images,css or js files..
	*/
	function url(string $path)
	{
		$protocol = ($_SERVER['REQUEST_SCHEME'] == "https") ? "https://" : "http://";
		return $protocol.$_SERVER['HTTP_HOST']."/".trim($path,"/");
	}
}
