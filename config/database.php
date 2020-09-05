<?php

/**
 |-----------------------------------------------------
 | database configuration
 |-----------------------------------------------------
 | 
 | return database credentials to create database instance
 | using Aditex\src\Container::class, this would make available 
 | connection\connection::class everywhere in your application
 | so that, you dont need to create any other pdo instance.
 | just typehint connection\connection::class in your controller 
 | class method and this will automatically injected in your controller 
 | method. the connection\connection api under developement so,
 | we have given 'conn' property in connection\connection::class which 
 | returns pdo instance for database queries.
 */

return [

	"driver" => "mysql",

	"host" => "localhost",

	"user" => "root",

	"password" => "",

	"schema" => "dbname",

];