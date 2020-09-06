<?php

require_once __DIR__ . "/../vendor/autoload.php";

/*
 |-------------------------------------------------------------------
 | Initialise database connection
 |-------------------------------------------------------------------
 |
 | lets, First we establish a connection with database
 | to execute those application tasks which dependent on database connection.
 | here, we are calling connection class using Aditex container. so that,
 | we could use connection instance everywhere it required. once pdo instance
 | created using Aditex\src\Container::class,
 | you can get pdo instance from anywhere,
 | $pdoInstance =   Aditex\src\Container::getInstance(
 |						connection\connection::class
 |					)->conn;
 */

Aditex\src\Container::call(connection\connection::class,[
	"connection\connection.config_path" => __DIR__."/../config/database.php"
]);

/*
 |-----------------------------------------------------------------
 | Session Management
 |-----------------------------------------------------------------
 |
 | start session using SessionManager class. texnder provides secure 
 | and fast way to authenticate urls or other user authentications
 | using its component called sessnex. which is developed to handle 
 | sessions using file or database.
 | it takes configuration file path as argument which returns php array.
 */

$SessionManager = new SessionManager(__DIR__."/../config/session.php");


/*
 |------------------------------------------------------------------
 | Return Client Respones 
 |------------------------------------------------------------------
 |
 | Routex api is used to decode urls and send specific response 
 | to the client browser. Routex is well capable to genrate response
 | for the calling request. because Routex api genrates and send back
 | a valid response, all tasks must perform before this. In other words,
 | it should be last execution line.
 */


$route = new Routex\Route(__DIR__."/../resources/views");