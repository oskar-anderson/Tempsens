<?php


namespace App\util;

use DateTimeImmutable;
use DateTimeZone;

require_once(__DIR__."/../../vendor/autoload.php");


class Helper
{
   public static function GetDateNowAsDateTime(): DateTimeImmutable
   {
      $datetime = new DateTimeImmutable();
      $timezone = new DateTimeZone('Europe/Tallinn');
      $datetime->setTimezone($timezone);
      return $datetime;
   }

   public static function GetPhpInfo(): string {
      ob_start();
      phpinfo();
      $phpinfoContent = ob_get_contents();
      ob_clean();
      return $phpinfoContent;
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
   public static function Render($fileName, $model): string
   {
      ob_start();
      require($fileName);
      $content = ob_get_contents();
      ob_clean();
      return $content;
   }
}
