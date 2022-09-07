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

   /**
    * @param Sensor[] $sensors
    * @param string $serial
    * @return Sensor
    */
    public static function GetSensorBySerial(array $sensors, string $serial): Sensor
    {
       $arr = array_values(array_filter($sensors,
          function ($obj) use ($serial) {
             return $obj->serial === $serial;
          }));
       if (sizeof($arr) === 0) die('Sensor with serial:' . $serial . ' does not exist!');
       if (sizeof($arr) > 1) die('Multiple sensors with serial:' . $serial);
       return $arr[0];
    }

   /**
    * Type hinting trick
    *  @return SensorReading[]
    */
   public static function NewArray(): array
   {
      return [];
   }
}