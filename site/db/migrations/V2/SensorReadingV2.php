<?php

namespace App\db\migrations\V2;

use App\domain\SensorReading;
use DateTimeImmutable;

class SensorReadingV2 {

    // PHP 8: Constructor property promotion - pretty nice
    function __construct(
        public string $id,
        public string $sensorId,
        public float $temp,
        public float $relHum,
        public DateTimeImmutable $dateRecorded,
        public ?DateTimeImmutable $dateAdded)
    { }

   public function MapToModel(): SensorReading
   {
      return new SensorReading(
         $this->id,
         $this->sensorId,
         $this->temp,
         $this->relHum,
         $this->dateRecorded,
         $this->dateAdded
      );
   }
}