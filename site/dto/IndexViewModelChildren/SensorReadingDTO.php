<?php

namespace App\dto\IndexViewModelChildren;


class SensorReadingDTO
{
   public string $date;
   public float $temp;
   public float $relHum;

   function __construct(string $date, float $temp, float $relHum)
   {
      $this->date = $date;
      $this->temp = $temp;
      $this->relHum = $relHum;
   }
}