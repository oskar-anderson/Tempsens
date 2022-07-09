<?php


require 'vendor/autoload.php';
use \App\util\Config as Config;

echo "test<br>";
echo (new Config())->GetConnectUrl();
// this kinda works, but messes relative paths up
// include_once "site/viewController/index.php";
