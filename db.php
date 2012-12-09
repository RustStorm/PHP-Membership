<?php

/*
    Database Config
*/

// Hostname of MySQL Server.
define(MYSQL_HOST, "localhost");

// Username for MySQL Account
define(MYSQL_USER, "mysql_user");

// Password for MySQL Account
define(MYSQL_PASS, "mysql_password"); 

// Database the Member System
define(MYSQL_DB, "mysql_database");

/*
    Script Config
*/

// MD5 password salt for extra security 
define(PASSWORD_SALT, '');

// Our fingerprint cookie name
define(COOKIE_NAME, 'phpsecurity');

// Timeout for login & cookie 1800=30m
define(LOGIN_TIMEOUT, '1800');

// Login Attempts
define(LOGIN_ATTEMPTS, '5');

// Create a mysqli object
$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);

?>