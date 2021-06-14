<?php

namespace App\dto\IndexViewModelChildren;

class Period
{
  public $name;
  public $value;

  function __construct($name, $value)
  {
    $this->name = $name;
    $this->value = $value;
  }

   static function GetPeriods() : array {
      $periods = [
         new Period('1 day', 1),
         new Period('2 weeks', 14),
         new Period('1 month', 30),
         new Period('3 months', 91),
         new Period('6 months', 183),
         new Period('1 year', 365),
         new Period('10 years', 3650)
      ];
      return $periods;
   }
}

