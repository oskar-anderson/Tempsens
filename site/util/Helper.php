<?php


namespace App\util;

require_once(__DIR__."/../../vendor/autoload.php");


class Helper
{
   public static function GetDateNow(): string
   {
      date_default_timezone_set('Europe/Tallinn');
      $now = date("YmdHi");
      return $now;
   }
}