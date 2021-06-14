<?php


namespace App\util;

require_once(__DIR__."/../../vendor/autoload.php");


class Config
{
   public static function GetConfig(): array
   {
      return require (__DIR__."/../config/config.php");
   }
}