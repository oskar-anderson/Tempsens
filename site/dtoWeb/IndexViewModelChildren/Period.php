<?php

namespace App\dtoWeb\IndexViewModelChildren;

use DateTimeImmutable;

class Period
{
  public string $name;
  public int $value;
  public bool $isSelected;

  function __construct(string $name, int $value, bool $isSelected)
  {
    $this->name = $name;
    $this->value = $value;
    $this->isSelected = $isSelected;
  }

   static function GetPeriods() : array {
      $periods = [
         new Period('1 day', 1, false),
         new Period('2 weeks', 14, false),
         new Period('1 month', 30, false),
         new Period('3 months', 91, false),
         new Period('6 months', 183, false),
         new Period('1 year', 365, false),
         new Period('10 years', 3650, false)
      ];
      return $periods;
   }

   /**
    * @param DateTimeImmutable $dateTo
    * @param DateTimeImmutable $dateFrom
    * @return Period[]
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
      $periods[$index]->isSelected = true;
      return $periods;
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

