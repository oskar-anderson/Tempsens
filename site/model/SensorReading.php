<?php

namespace App\model;

use DateTimeImmutable;

class SensorReading
{
   public string $id;
   public string $sensorId;
   public float $temp;
   public float $relHum;
   public string $dateRecorded;
   public ?string $dateAdded;

   function __construct(string $id, string $sensorId, float $temp, float $relHum, DateTimeImmutable $dateRecorded, ?DateTimeImmutable $dateAdded)
   {
      $this->id = $id;
      $this->sensorId = $sensorId;
      $this->temp = $temp;
      $this->relHum = $relHum;
      $this->dateRecorded = $dateRecorded->format('YmdHi');
      $this->dateAdded = is_null($dateAdded) ? null : $dateAdded->format('YmdHi');
   }


   /**
    * Type hinting trick
    *  @return SensorReading[]
    */
   public static function NewArray(): array
   {
      return [];
   }

   public function getDateRecordedAsDateTime(): DateTimeImmutable {
      return DateTimeImmutable::createFromFormat('YmdHi', $this->dateRecorded);
   }

   public function getDateAddedAsDateTime(): DateTimeImmutable|null {
      return is_null($this->dateAdded) ? null : DateTimeImmutable::createFromFormat('YmdHi', $this->dateRecorded);
   }
}