<?php

namespace App\model;

class Sensor
{
   public string $id;
   public string $name;
   public string $serial;
   public string $model;
   public string $ip;
   public string $location;
   public bool $isPortable;
   public float $minTemp;
   public float $maxTemp;
   public float $minRelHum;
   public float $maxRelHum;
   public int $readingIntervalMinutes;


   function __construct($id, $name, $serial, $model, $ip, $location, $isPortable,
                        $minTemp, $maxTemp, $minRelHum, $maxRelHum, $readingIntervalMinutes)
   {
    $this->id = $id;
    $this->name = $name;
    $this->serial = $serial;
    $this->model = $model;
    $this->ip = $ip;
    $this->location = $location;
    $this->isPortable = $isPortable;
    $this->minTemp = $minTemp;
    $this->maxTemp = $maxTemp;
    $this->minRelHum = $minRelHum;
    $this->maxRelHum = $maxRelHum;
    $this->readingIntervalMinutes = $readingIntervalMinutes;
   }

   /**
    * Type hinting trick
    *  @return array<Sensor>
    */
   public static function NewArray(): array
   {
      return [];
   }
}

