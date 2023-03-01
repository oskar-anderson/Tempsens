<?php

namespace App\model;

class Sensor
{
   const IdColumnName = 'id';
   public string $id;

   const NameColumnName = 'name';
   public string $name;

   const SerialColumnName = 'serial';
   public string $serial;

   const ModelColumnName = 'model';
   public string $model;

   const IpColumnName = 'ip';
   public string $ip;

   const LocationColumnName = 'location';
   public string $location;

   const IsPortableColumnName = 'is_portable';
   public bool $isPortable;

   const MinTempColumnName = 'min_temp';
   public float $minTemp;

   const MaxTempColumnName = 'max_temp';
   public float $maxTemp;

   const MinRelHumColumnName = 'min_rel_hum';
   public float $minRelHum;

   const MaxRelHumColumnName = 'max_rel_hum';
   public float $maxRelHum;

   const ReadingIntervalMinutesColumnName = 'reading_interval_minutes';
   public int $readingIntervalMinutes;


   function __construct(string $id, string $name, string $serial, string $model, string $ip, string $location, bool $isPortable,
                        int $minTemp, int $maxTemp, int $minRelHum, int $maxRelHum, int $readingIntervalMinutes)
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

