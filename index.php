<?php


require 'vendor/autoload.php';


// echo $_SERVER["REQUEST_URI"];
// Not sure which is better. header allow us to have 1 URL entrypoint, but makes the url longer and uglier
header('Location: '. "site/viewController/index.php");
// include_once "site/viewController/index.php";
