<?php

namespace App\db\dal;

require_once(__DIR__ . "/../../../vendor/autoload.php");

use App\db\DbHelper;
use App\dto\CacheJsonDTO;
use App\model\Sensor;
use App\model\SensorReading;
use JetBrains\PhpStorm\Pure;
use PDO;

class DalSensorReading implements IDalBase
{
   /**
    *  @return string
    */
   public function GetName(): string { return "SensorReading"; }

   /**
    *  @return string
    */
   public function SqlCreateTableStmt(): string
   {
      $result = "create table " . DalSensorReading::GetName() .
         " ( " .
         "Id VARCHAR(64) NOT NULL PRIMARY KEY, " .
         "SensorId VARCHAR(64) NOT NULL, " .
         "Temp DECIMAL(18,1) NOT NULL, " .
         "RelHum DECIMAL(18,1) NOT NULL, " .
         "DateRecorded VARCHAR(64) NOT NULL, " .
         "DateAdded VARCHAR(64), " .
         "CONSTRAINT " . DalSensorReading::GetName() ."FKSensor foreign key (SensorId) references " . (new DalSensors)->GetName() . "(Id)" .
         " );";
      return $result;
   }

   /**
    *  @param Sensor[] $sensors
    */
   public static function ResetCache(array $sensors): void
   {
      CacheJsonDTO::CreateEmpty();
      $lastReadings = [];
      foreach ($sensors as $sensor) {
         $lastReading = (new DalSensorReading())->GetLastReading($sensor->id);
         $lastReadings[$sensor->id] = $lastReading;
      }
      (new CacheJsonDTO($lastReadings))->Save();
   }

   /**
    *  @param SensorReading $sensorReading
    */
   public static function SetLastReadingsCache(SensorReading $sensorReading): void
   {
      $cache = CacheJsonDTO::Read();
      $cache->sensorReadings[$sensorReading->sensorId] = $sensorReading;
      $cache->Save();
   }

   /**
    *  @param Sensor[] $sensors
    *  @return SensorReading[]
    */
   public static function GetLastReadingsFromCacheOrDatabase(array $sensors): array
   {
      (new CacheJsonDTO(DalSensorReading::GetLastSensorReadings()))->Save();
      $cache = CacheJsonDTO::Read();
      $isDirty = false;
      $lastReadings = [];
      foreach ($sensors as $sensor) {
         // check if cache has the last sensorReading of the sensor
         if (array_key_exists($sensor->id, $cache->sensorReadings)) {
            $lastReadings[$sensor->id] = $cache->sensorReadings[$sensor->id];
            continue;
         }
         // Do a DB query to get the sensors last reading
         $lastReading = (new DalSensorReading())->GetLastReading($sensor->id);
         $lastReadings[$sensor->id] = $lastReading;
         $isDirty = true;
      }
      // update cache if any data came from the DB
      if ($isDirty) {
         (new CacheJsonDTO($lastReadings))->Save();
      }

      return $lastReadings;
   }

   public function GetLastSensorReadings(): array {
      $sensors = (new DalSensors())->GetAll();
      $lastReadings = [];
      foreach ($sensors as $sensor) {
         $lastReadings[$sensor->id] = (new DalSensorReading())->GetLastReading($sensor->id);
      }
      return $lastReadings;
   }

   /**
    *  @param string $sensorId
    *  @return SensorReading|null
    */
   public function GetLastReading(string $sensorId): SensorReading|null
   {
      $pdo = DbHelper::GetPDO();
      $qry = "SELECT Id, " .
         "SensorId, " .
         "Temp, " .
         "RelHum, " .
         "DateRecorded, " .
         "DateAdded " .
         " FROM " . $this->GetName() .
         " WHERE SensorId = ? " .
         " ORDER BY DateRecorded DESC LIMIT 1;";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$sensorId]);
      $value = $stmt->fetch();
      if (!$value) {
         return null;
      }
      return $this->Map($value);
   }

   /**
    *  @param string $from
    *  @param string $to
    *  @return SensorReading[]
    */
   public function GetAllBetween(string $from, string $to): array
   {
      $pdo = DbHelper::GetPDO();
      $result = [];
      $qry = "SELECT Id, " .
         "SensorId, " .
         "Temp, " .
         "RelHum, " .
         "DateRecorded " .
         " " .
         " FROM " . $this->GetName() .
         " WHERE DateRecorded >= ? " .
         " AND DateRecorded <= ? " .
         " ORDER BY DateRecorded ASC";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$from, $to]);
      while ($value = $stmt->fetch()) {
         $value['DateAdded'] = null;
         array_push($result, $this->Map($value));
      }

      return $result;
   }

   /**
    *  @param string $sensorId
    *  @return SensorReading[]
    */
   public function GetAllWhereSensorId(string $sensorId): array
   {
      $pdo = DbHelper::GetPDO();
      $result = [];
      $qry = "SELECT Id, " .
         "SensorId, " .
         "Temp, " .
         "RelHum, " .
         "DateRecorded " .
         " " .
         " FROM " . $this->GetName() .
         " WHERE SensorId >= ? " .
         " ORDER BY DateRecorded ASC";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$sensorId]);
      while ($value = $stmt->fetch()) {
         $value['DateAdded'] = null;
         array_push($result, $this->Map($value));
      }

      return $result;
   }

   /**
    *  @param SensorReading $sensorReading
    *  @param PDO $pdo
    */
   public function Create(SensorReading $sensorReading, PDO $pdo): void
   {
      $qry = "INSERT INTO " . $this->GetName() . " ( " .
         "Id, " .
         "SensorId, " .
         "Temp, " .
         "RelHum, " .
         "DateRecorded, " .
         "DateAdded) " .
         " VALUES (?,?,?,?,?,?);";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([
         $sensorReading->id, $sensorReading->sensorId, $sensorReading->temp,
         $sensorReading->relHum, $sensorReading->dateRecorded, $sensorReading->dateAdded]);
   }

   /**
    * @param array $value
    * @return SensorReading
    */
   #[Pure]
   public function Map(array $value): SensorReading
   {
      return new SensorReading(
         $value['Id'],
         $value['SensorId'],
         floatval($value['Temp']),
         floatval($value['RelHum']),
         $value['DateRecorded'],
         $value['DateAdded'],
      );
   }
}