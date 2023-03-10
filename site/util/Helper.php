<?php


namespace App\util;

use DateTimeImmutable;
use DateTimeZone;

require_once(__DIR__."/../../vendor/autoload.php");


class Helper
{
   public static function GetUtcNow(): DateTimeImmutable
   {
      return new DateTimeImmutable('now', new DateTimeZone('UTC'));
   }

   public static function GetPhpInfo(): string {
      return Helper::GetOutputBufferContent(function() {
         phpinfo();
      });
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
