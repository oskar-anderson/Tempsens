<?php

namespace App\model;

class SensorReading
{
   public string $id;
   public string $sensorId;
   public float $temp;
   public float $relHum;
   public string $dateRecorded;
   public ?string $dateAdded;

   function __construct(string $id, string $sensorId, float $temp, float $relHum, string $dateRecorded, ?string $dateAdded)
   {
      $this->id = $id;
      $this->sensorId = $sensorId;
      $this->temp = $temp;
      $this->relHum = $relHum;
      $this->dateRecorded = $dateRecorded;
      $this->dateAdded = $dateAdded;
   }
}