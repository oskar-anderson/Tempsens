<?php


namespace App\dto;

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensorReadingTmp;
use App\db\dal\DalSensors;
use App\model\SensorReading;
use Exception;

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

   public static function CreateEmpty() {
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

   /**
    * Create the cache with provided data if it does not exist
    *
    *  @return bool
    */
    public static function DoesFileExist(): bool {
      return file_exists(CacheJsonDTO::GetFilename());
   }

   /**
    * Read the cache file. Use DoesFileExist() before reading file to prevent Exception.
    *
    *  @return CacheJsonDTO
    *  @throws Exception
    */
   public static function Read(): CacheJsonDTO {
      $file = file_get_contents(CacheJsonDTO::GetFilename());
      if ($file === false) {
         throw new Exception("File could not be read! Most likely file does not exist. Try calling CreateWithDataIfNotExist before this function.");
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