<?php


require 'vendor/autoload.php';
use \App\util\Config;

echo "test<br>";
echo "GetConnectUrl: " . (new Config())->GetConnectUrl() . '<br>';
echo "GetUsername: " . (new Config())->GetUsername() . '<br>';
echo "getenv: " . getenv("test") . '<br>';
// this kinda works, but messes relative paths up
// include_once "site/viewController/index.php";
