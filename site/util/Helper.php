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
      return Helper::GetOutputBufferContent(function() {
         phpinfo();
      });
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



   public static function Render($fileName, $model): string
   {
      return Helper::GetOutputBufferContent(function() use ($fileName, $model) {
         require($fileName);
      });
   }


   public static function GetOutputBufferContent(callable $contentCallback): string
   {
      ob_start();
      $contentCallback();
      $content = ob_get_contents();
      ob_clean();
      return $content;
   }
}
