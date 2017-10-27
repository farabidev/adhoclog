<?php

session_start();


if((isset($_SESSION['adhoc_user']) && $_SESSION['adhoc_user']) || (isset($_COOKIE['adhoc_user']) && $_COOKIE['adhoc_user']))
{
	if(!$_SESSION['adhoc_user']) $_SESSION['adhoc_user'] = $_COOKIE['adhoc_user'];
	include './view/home.php';
}
else 
	include './view/login.php';
	

