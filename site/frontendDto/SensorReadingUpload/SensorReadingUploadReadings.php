<?php

namespace App\frontendDto\SensorReadingUpload;

use DateTimeImmutable;

class SensorReadingUploadReadings
{
   function __construct(
      public DateTimeImmutable $date,
      public float $temp,
      public float $relHum,
   ) {}
}