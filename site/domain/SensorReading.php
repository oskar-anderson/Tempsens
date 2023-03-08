<?php

namespace App\domain;

use DateTimeImmutable;

class SensorReading
{
   const TableName = "sensor_reading";
   const IdColumnName = 'id';
   public string $id;

   const SensorIdColumnName = 'sensor_id';
   public string $sensorId;

   const TempColumnName = 'temp';
   public float $temp;

   const RelHumColumnName = 'rel_hum';
   public float $relHum;

   const DateRecordedColumnName = 'date_recorded';
   public string $dateRecorded;

   const DateAddedColumnName = 'date_added';
   public ?string $dateAdded;

   function __construct(string $id, string $sensorId, float $temp, float $relHum, DateTimeImmutable $dateRecorded, ?DateTimeImmutable $dateAdded)
   {
      $this->id = $id;
      $this->sensorId = $sensorId;
      $this->temp = $temp;
      $this->relHum = $relHum;
      $this->dateRecorded = $dateRecorded->format('YmdHis');
      $this->dateAdded = is_null($dateAdded) ? null : $dateAdded->format('YmdHis');
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
      return DateTimeImmutable::createFromFormat('YmdHis', $this->dateRecorded);
   }

   public function getDateAddedAsDateTime(): DateTimeImmutable|null {
      return is_null($this->dateAdded) ? null : DateTimeImmutable::createFromFormat('YmdHis', $this->dateRecorded);
   }
}