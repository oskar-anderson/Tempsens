<?php


namespace App\dto;

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensorReadingTmp;
use App\db\dal\DalSensors;
use App\model\SensorReading;

require_once (__DIR__."/../../vendor/autoload.php");

class CacheJsonDTO
{
   public static function GetFilename(): string { return __DIR__ . "/../Cache.json"; }

   /* @var SensorReading|null[] $sensorReadings */
   public array $sensorReadings;

   /* @param SensorReading|null[] $sensorReadings */
   function __construct(array $sensorReadings)
   {
      $this->sensorReadings = $sensorReadings;
   }

   public static function Recreate() {
      $file = fopen(CacheJsonDTO::GetFilename(), 'w') or die('Cannot create file!');
      fwrite($file, '');
      fclose($file);
   }

   public function Save() {
      $file = fopen(CacheJsonDTO::GetFilename(), 'w') or die('Cannot create file!');
      $txt = json_encode($this, JSON_PRETTY_PRINT);
      fwrite($file, $txt);
      fclose($file);
   }

   public static function Read(): CacheJsonDTO {
      if (! file_exists(CacheJsonDTO::GetFilename())) {
         $sensors = (new DalSensors())->GetAll();
         $lastReadings = [];
         foreach ($sensors as $sensor) {
            $lastReadings[$sensor->id] = (new DalSensorReading())->GetLastReading($sensor);
         }
         (new CacheJsonDTO($lastReadings))->Save();
      }
      $file = file_get_contents(CacheJsonDTO::GetFilename());
      if ($file === false) {
         die('Cannot read file!');
      }
      $json = json_decode($file);
      $sensorReadings = [];
      foreach ($json->sensorReadings as $sensorReadingJson) {
         if ($sensorReadingJson === null) {
            continue;
         }
         $sensorReading = new SensorReading(
            $sensorReadingJson->id,
            $sensorReadingJson->sensorId,
            $sensorReadingJson->temp,
            $sensorReadingJson->relHum,
            $sensorReadingJson->dateRecorded,
            $sensorReadingJson->dateAdded,
         );
         $sensorReadings[$sensorReading->sensorId] = $sensorReading;

      }
      $result = new CacheJsonDTO($sensorReadings);
      return $result;
   }
}