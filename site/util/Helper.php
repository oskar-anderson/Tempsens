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

   /**
    * Provide universal echo for putting PHP variables inside HTML and JS.
    * Replaces single quotes with \u0027.
    * Call like this in JS or HTML'_<_?php echo SafeEchoReturn($yourValue) ?>'
    * @param mixed $value Value that will be JSON serialized
    * @return string String that needs to be wrapped in single quotes.
    */
   public static function EchoJson(mixed $value): string {
      $unsafeJson = json_encode($value, JSON_HEX_APOS);
      if (gettype($value) === "string") {
         $unsafeJson = substr($unsafeJson, 1, strlen($unsafeJson) - 2);
      }
      return $unsafeJson;
      //$unsafeJson = str_replace("\\", "\\\\", $unsafeJson); // replace
      // return str_replace("\\\\\"", "\\\\\\\"", $unsafeJson);
   }


   /** @noinspection PhpUnusedParameterInspection */
   public static function Render($fileName, $model) {
      require($fileName);
   }
}
