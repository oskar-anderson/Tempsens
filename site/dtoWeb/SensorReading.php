<?php

namespace App\dtoWeb;

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
         "date" => $this->dateRecorded->format("YmdHis"),
         "temp" => $this->temp,
         "relHum" => $this->relHum
      ];
   }
}