<?php


require 'vendor/autoload.php';
use \App\util\Config;

$line = $_GET['line'];
echo "dir path: " . dirname(__DIR__, 2)."<br>";
echo "dir: " . __DIR__ . "<br>";
echo "__FILE__: " . __FILE__."<br>";
echo "test 4<br>";
if ($line == 1) {
   echo "GetConnectUrl: " . (new Config())->GetConnectUrl() . '<br>';
} elseif ($line == 2) {
   echo "GetUsername: " . (new Config())->GetUsername() . '<br>';
} elseif ($line == 3) {
   echo "getenv: " . getenv("test") . '<br>';
} elseif ($line == 4) {
   echo "(new Config())->GetUsername(): " . Config::EchoTest() . '<br>';
}



// this kinda works, but messes relative paths up
// include_once "site/viewController/index.php";
