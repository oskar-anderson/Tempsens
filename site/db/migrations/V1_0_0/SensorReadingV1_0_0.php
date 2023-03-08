<?php

namespace App\db\migrations\V1_0_0;

use App\domain\SensorReading;
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
      $dateRecordedImmutable = DateTimeImmutable::createFromFormat("YmdHis", $this->dateRecorded);
      $dateAddedImmutableOrNull = $this->dateAdded === null ? null : DateTimeImmutable::createFromFormat("YmdHis", $this->dateAdded);
      return new SensorReading(
         $this->id,
         $this->sensorId,
         $this->temp,
         $this->relHum,
         $dateRecordedImmutable,
         $dateAddedImmutableOrNull
      );
   }
}