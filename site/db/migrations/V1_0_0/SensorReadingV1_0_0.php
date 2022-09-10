<?php

namespace App\db\migrations\V1_0_0;

use App\model\SensorReading;
use DateTimeImmutable;

class SensorReadingV1_0_0 {

    // PHP 8: Constructor property promotion - pretty nice
    function __construct(
        public string $id,
        public string $sensorId,
        public float $temp,
        public float $relHum,
        public string $dateRecorded,
        public ?string $dateAdded)
    { }

   public function MapToModel(): SensorReading
   {
      return new SensorReading(
         $this->id,
         $this->sensorId,
         $this->temp,
         $this->relHum,
         DateTimeImmutable::createFromFormat("YmdHi", $this->dateRecorded),
         $this->dateAdded === null ? null : DateTimeImmutable::createFromFormat("YmdHi", $this->dateAdded)
      );
   }
}