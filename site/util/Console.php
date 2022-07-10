<?php


namespace App\util;

require_once(__DIR__."/../../vendor/autoload.php");


class Console
{
    /**
     * Wrap the echo function for better syntax and automatic newline support
     * @param string $value Message that will be echod
     * @param bool $isImportant Adds a dragon emoji to differentiate important- and debug-echos
    *  @return void
    */
    public static function WriteLine($value = "", $isImportant = false): void
    {
        echo ($isImportant ? "🐉 " : "") . $value . "\n";
    }

   public static function DebugToConsole(mixed $data, bool $withScriptTag): void
   {
      // Buffering to solve problems frameworks, like header() in this and not a solid return.
      // ob_start();

      $output = 'console.log(' . json_encode($data) . ');';
      $output = $withScriptTag? '<script type=text/javascript>' . $output . '</script>' : $output;
      echo $output;
   }
}
