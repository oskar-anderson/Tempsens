<?php

namespace App\util;

require_once (__DIR__."/../../vendor/autoload.php");


class Base64
{
   public static function GenerateId(): string
   {
      $guid = base64_encode(random_bytes(40));  // can be lower, not sure about the math, but actual length is about 30% longer after base64_encode
      $guid = str_replace(['+', '/', '='], ['-', '_', ''], $guid);  // make url friendly
      return substr($guid, 0, 22);
   }

}