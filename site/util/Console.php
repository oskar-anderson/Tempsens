<?php


namespace App\util;

require_once(__DIR__."/../../vendor/autoload.php");


class Console
{
   public static string $Break = "<br>";
   public static string $Linefeed = "\n";
   public static string $BreakLF = "<br>\n";

   public function __construct(
      private string $newline,
      private bool $withDate
   ) {
   }


    /**
     * Wrap the echo function for better syntax and automatic newline support
     * @param string $value Message that will be echoed
    *  @return void
    */
    public function WriteLine(string $value = ""): void
    {
        echo ($this->withDate && $value !== "" ? ((Helper::GetDateNowAsDateTime())->format("H:i:s:u") . " ") : "") .
           $value . $this->newline;
    }
}
