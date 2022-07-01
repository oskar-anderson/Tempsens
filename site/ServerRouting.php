<?php

$requestedPage = $_SERVER["REQUEST_URI"];
$router = str_replace('/myApps/Tempsens/site/', '', $requestedPage);
var_dump($requestedPage);
echo "<br>";
var_dump($router);
echo "<br>";
$allowDebug = true;
if ($router == "page1"){
   echo "page1";
   exit();
}
include_once "viewController/index.php";
exit();