<?php

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return [

	/**
	 | here,
	 | array's key represents namespace and 
	 | array's value represents directory path used for the respective namespace
	 | whichever directory we return here, php files of that directory will be 
	 | available in bootex service object automaticaly..
	 */

	"bootex\\" => array($vendorDir. "/bootex/src")

];