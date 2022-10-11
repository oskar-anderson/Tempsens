<?php

namespace App\dto\IndexViewModelChildren;

use DateTimeImmutable;

class Period
{
  public string $name;
  public int $value;

  function __construct(string $name, int $value)
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

   /**
    * @param DateTimeImmutable $dateTo
    * @param DateTimeImmutable $dateFrom
    * @return string[]
    */
   static function GetPeriodOptions(DateTimeImmutable $dateTo, DateTimeImmutable $dateFrom): array {
      $index = Period::IntArrGetClosest(
         $dateTo->diff($dateFrom)->days,
         array_map(
            fn($x) => $x->value,
            Period::GetPeriods()
         )
      );
      $periods = Period::GetPeriods();
      return array_map(
         function($i) use ($periods, $index) {
            $value = $periods[$i]->value;
            $name = $periods[$i]->name;
            $selected = $index === $i ? "selected" : "";
            return "<option value='$value' $selected>-$name</option>";
         },
         array_keys($periods));
   }

   static function IntArrGetClosest(int $needle, array $arr) : int|null {
      $search = null;
      foreach ($arr as $i => $item) {
         if ($search === null || abs($needle - $search) > abs($item - $needle)) {
            $search = $i;
         }
      }
      return $search;
   }
}

