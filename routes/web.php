<?php  

/*
 |------------------------------------------------------------------
 | APPLICATION ROUTES
 |------------------------------------------------------------------
 |
 | defined all apllication Routes here, only these routes are Authenticated
 | or allowed, other routes apart these will return 404 http error page
 | here, Routex api is used to handle routes. To, know more please visit
 | our documentation: http://texnder.com/documentation/
 */

use Routex\Route;

Route::get('/', 'App\controllers\homeController@index');

// or

Route::get('/texnder', function(){
	return view('app');
});