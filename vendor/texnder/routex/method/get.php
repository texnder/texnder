<?php 
namespace Routex\method;

use Routex\method\parserTrait;
use Routex\Route;


class get
{	

	use parserTrait;

	public function __construct($rowUrl, $view)
	{
		$this->resolveUrl($rowUrl);
		$this->getView($view);
	}

	/**
	 * add middelware class to work before response sent to user
	 *
	 * @return null
	 */
	public function middelware($name)
	{
		array_pop(Route::$authGetRequests);
		$this->middelware = $name;
		array_push(Route::$authGetRequests, $this);
	}
}