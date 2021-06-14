<?php

namespace App\model;

class SensorReadingTmp
{
   public string $id;
   public string $sensorId;
   public float $temp;
   public float $relHum;
   public string $dateRecorded;
   public ?string $dateAdded;
   public string $tmpDupId;
   public ?string $tmpDupSensorId;
   public string $tmpDupDateRecorded;


   function __construct(string $id, string $sensorId, float $temp, float $relHum, string $dateRecorded, ?string $dateAdded,
   $tmpDupId, $tmpDupSensorId, $tmpDupDateRecorded)
   {
      $this->id = $id;
      $this->sensorId = $sensorId;
      $this->temp = $temp;
      $this->relHum = $relHum;
      $this->dateRecorded = $dateRecorded;
      $this->dateAdded = $dateAdded;
      $this->tmpDupId = $tmpDupId;
      $this->tmpDupSensorId = $tmpDupSensorId;
      $this->tmpDupDateRecorded = $tmpDupDateRecorded;
   }
}