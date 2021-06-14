<?php


namespace App\view\partial;

class HeaderPartial
{
   public static function GetHtml(string $title): string
   {
      $format = <<<EOT
         <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
         <meta http-equiv="Content-Script-Type" content="text/javascript" />
         <meta http-equiv="Content-Style-Type" content="text/css" />
         <meta name="viewport" content="width=device-width, initial-width, initial-scale=1.0">
         <meta name="robots" content="index,nofollow" />
         <meta name="keywords" content="Sensors" />
         <meta name="description" content="Sensors" />
         <title>%s</title>
         
         <link rel="icon" href="%s" type="image/png" />
         
         <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css">
         <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
         <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

         <link rel="stylesheet" type="text/css" media="screen" href="%s" />
         <link rel="stylesheet" type="text/css" media="screen" href="%s" />
         
         <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
         <script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
         <script type="text/javascript" src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
      EOT;

      $result = sprintf($format,
         $title,
         "../static/gfx/favicon3.png",
         "../static/css/reset.css",
         "../static/css/main-layout.css",
      );
      return $result;
   }
}
