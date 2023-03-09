<?php

namespace App\dtoWeb;

class Sensor
{
   function __construct(
      public string $id,
      public string $name,
      public string $serial,
      public string $model,
      public string $ip,
      public string $location,
      public bool $isPortable,
      public float $minTemp,
      public float $maxTemp,
      public float $minRelHum,
      public float $maxRelHum,
      public int $readingIntervalMinutes
   ) {}

}