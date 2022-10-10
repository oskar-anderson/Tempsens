<?php


namespace App\dto;

use App\db\dal\DalCache;
use App\model\Cache;
use App\model\SensorReading;
use App\util\Config;
use DateTimeImmutable;
use Exception;

require_once (__DIR__."/../../vendor/autoload.php");

class CacheJsonDTO
{
   public static function GetFilename(): string { return __DIR__ . "/../Cache.json"; }

   /* @var SensorReading[] $sensorReadings */
   public array $sensorReadings;

   /* @param SensorReading[] $sensorReadings */
   function __construct(array $sensorReadings)
   {
      $this->sensorReadings = $sensorReadings;
   }

   public static function CreateEmpty() {
      if ((new Config())->GetUseDbCache()) {
         (new DalCache())->Update(
            (new Cache(false, true, true))->
               setType(DalCache::getLastSensorReadingType())->
               setContent([])
         );
         return;
      }
      $file = fopen(CacheJsonDTO::GetFilename(), 'w') or die('Cannot create file!');
      fwrite($file, '');
      fclose($file);
   }

   public function Save() {
      if ((new Config())->GetUseDbCache()) {
         (new DalCache())->Update(
            (new Cache(false, true, true))->
            setType(DalCache::getLastSensorReadingType())->
            setContent($this->sensorReadings)
         );
         return;
      }
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
       if ((new Config())->GetUseDbCache()) {
          return false;
       }
       return file_exists(CacheJsonDTO::GetFilename());
   }

   /**
    * Read the cache file. Use DoesFileExist() before reading file to prevent Exception.
    *
    *  @return CacheJsonDTO
    *  @throws Exception
    */
   public static function Read(): CacheJsonDTO {
      if ((new Config())->GetUseDbCache()) {
         $lastSensorReadingsOrFalse = (new DalCache())->GetByKeyFirstOrFalse(DalCache::getLastSensorReadingType());
         if ($lastSensorReadingsOrFalse === false) {
            return new CacheJsonDTO([]);
         }
         return new CacheJsonDTO($lastSensorReadingsOrFalse);
      }
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
            DateTimeImmutable::createFromFormat("YmdHi", $sensorReadingJson->dateRecorded),
            $sensorReadingJson->dateAdded === null ? null : DateTimeImmutable::createFromFormat("YmdHi", $sensorReadingJson->dateAdded),
         );
         $sensorReadings[$sensorReading->sensorId] = $sensorReading;

      }
      $result = new CacheJsonDTO($sensorReadings);
      return $result;
   }
}