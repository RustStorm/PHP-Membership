<?php
// Get our config and class
require_once('db.php');

//
//  Login
//
if(isset($_POST['submit']) && $_POST['submit'] == "Login")
{    
    // Send to class
    $auth->CheckUser($_POST['username'],$_POST['password']);
}

//
//  Logout
//
if(isset($_GET['logout']))
{
    if($_GET['logout'] == 'true')
    {
        // Send logout to class
        $auth->DestorySession();
    }
}

//
//  Page Display
//
if(!$auth->logged_in)
{
    include 'login.php';
} else {
    echo "Welcome to the Restricted Area";
}
?>
