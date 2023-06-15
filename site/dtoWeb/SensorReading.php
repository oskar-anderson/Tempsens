<?php

namespace App\dtoWeb;

use DateTimeInterface;
use JsonSerializable;

class SensorReading implements JsonSerializable
{
   function __construct(

      public string $id,
      public string $sensorId,
      public float $temp,
      public float $relHum,
      public \DateTimeImmutable $dateRecorded,
      public ?\DateTimeImmutable $dateAdded
   ) {}


   public function jsonSerialize(): array
   {
      return [
         "date" => $this->dateRecorded->format(DateTimeInterface::ATOM),
         "temp" => $this->temp,
         "relHum" => $this->relHum
      ];
   }
}