<?php


namespace App\dto\IndexViewModelChildren;


class LastSensorReading
{
   function __construct(
      public string $dateRecorded,
      public string $temp,
      public string $relHum,
      public string $color,
   ) {}

}