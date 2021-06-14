<?php


namespace App\view\partial;

class FooterPartial
{
   public static function GetHtml(): string
   {
      $format = <<<EOT
         <footer>
            Tempsens v1.0.0, 23.05.2021
         </footer>
      EOT;

      $result = $format;
      return $result;
   }
}
