# routex
light and fast routing system for PHP framework

## Installation
install this library using composer.

	syntax:
		composer require texnder/routex

## Routex Response
To get client response data create [Routex\http\Response class] new instance and call its getResponse method. Routex\http\Response has a [Routex\http\Request class] dependency. so inject it by creating this instance or simply create [Routex\http\Response class] using aditex dependency injector. Routex is dependent on aditex, it means it will install automatically, when you install Routex using composer.

## Clean Url
using routex library you can write clean urls for application pages. It's easy and fast in use. It provides professional look to your website and also helps you indexing high in google search. clean url very much helps in digital marketing and search engine optimisation(SEO).

## defined your own routes
Routex provides full freedom to define custom Urls for each pages of application. if client request is different from Urls which are defined in application, client will get [404 http error] response.
	
	syntax:
		use Routex\Route;

		// for get requests

		Route::get('my-custom-url/{key}/$_GET-key-in-curly-braces/{id}', 'controller@method');

		or

		Route::get('my-custom-url/{key}/$_GET-key-in-curly-braces/{id}', function(){
				return view('ViewName(without extension and use [.] for directory separation)');
		});

		or

		Route::get('my-custom-url/{key}/$_GET-key-in-curly-braces/{id}', function(){
				// valid codes..
				return "hello world";
		});

		// for post request

		Route::post('my-custom-url', 'controller@method');

		or

		Route::post('my-custom-url', function(){
				return view('ViewName(without extension and use [.] for directory separation)');
		});

		or 

		Route::post('my-custom-url', function(){
				// valid codes..
				return "hello world";
		});

## create Route instance
create new instance and pass resource directory path and view file extension([.html] or [.php] : By default it's [.php]) as argument for constructor.

	syntax:
		Routex\Route(string [resource directory],string [file .extension]);

## Use .htaccess file
use .htaccess file to redirecting each request to Routex library, where it can resolve requested url and send response data to client back. 

for Example:
	let we want to create application where [index.php] file is under public folder and we want all request should go to [index.php] file. in that case we can create .htaccess file in root and public folder with codes below.

	syntax:
		# .htaccess file in root directory in which public folder exist
		# to block indexing of directory
		Options All -Indexes 

		# to block access for config or security files
		<FilesMatch "(\.(bak|config|dist|fla|inc|ini|log|psd|sh|sql|swp)|~)$">
	    ## Apache 2.2
	  	Order allow,deny
	   	Deny from all
	   	Satisfy All
		</FilesMatch>

		# to redirect all request in public folder
		RewriteEngine On
		RewriteCond %{REQUEST_URI} !^/public/ 
		RewriteRule (.*) public/$1 [L]

		# .htaccess file in public directory where index.php file exist
		# to redirect all request to index file
		# here you can redirect to any other files if you want
		<IfModule mod_rewrite.c>
	    RewriteEngine On
	    # Removes index.php from ExpressionEngine URLs  
	    RewriteCond %{REQUEST_FILENAME} !-f
	    RewriteCond %{REQUEST_FILENAME} !-d
	    RewriteRule ^(.*)$ index.php/$1 [L,QSA]
		</IfModule>

For more information please visit [.htaccess](https://httpd.apache.org/docs/2.4/howto/htaccess.html)

## any queries
for any further queries, please email us on: (texnder.components@gmail.com)

## License

The aditex is open-sourced php library licensed under the [MIT license](http://opensource.org/licenses/MIT).
