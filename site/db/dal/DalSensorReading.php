<?php

namespace App\db\dal;

require_once(__DIR__ . "/../../../vendor/autoload.php");

use App\db\DbHelper;
use App\dto\CacheJsonDTO;
use App\model\Sensor;
use App\model\SensorReading;
use JetBrains\PhpStorm\Pure;
use PDO;

class DalSensorReading
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
         " ); CREATE INDEX ".DalSensorReading::GetName()."idxDateRecorded ON ". DalSensorReading::GetName() ."(DateRecorded)";
      return $result;
   }

   /**
    *  @param Sensor[] $sensors
    */
   public static function ResetCache(array $sensors): void
   {
      CacheJsonDTO::Recreate();
      $lastReadings = [];
      foreach ($sensors as $sensor) {
         $lastReading = (new DalSensorReading())->GetLastReading($sensor);
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
      (new CacheJsonDTO($cache->sensorReadings))->Save();
   }

   /**
    *  @param Sensor[] $sensors
    *  @return SensorReading[]
    */
   public static function GetLastReadingsCacheIfNotExistUpdate(array $sensors): array
   {
      $cache = CacheJsonDTO::Read();
      $isDirty = false;
      $lastReadings = [];
      foreach ($sensors as $sensor) {
         if (array_key_exists($sensor->id, $cache->sensorReadings)) {
            $lastReadings[$sensor->id] = $cache->sensorReadings[$sensor->id];
            continue;
         }
         $lastReading = (new DalSensorReading())->GetLastReading($sensor);
         $lastReadings[$sensor->id] = $lastReading;
         $isDirty = true;
      }
      if ($isDirty) {
         (new CacheJsonDTO($lastReadings))->Save();
      }

      return $lastReadings;
   }

   /**
    *  @param Sensor $sensor
    *  @return SensorReading|null
    */
   public function GetLastReading(Sensor $sensor): SensorReading|null
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
      $stmt->execute([$sensor->id]);
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
    * @param Sensor[] $sensors
    * @param string $serial
    * @return string
    */
   public function GetSensorBySerial(array $sensors, string $serial): string
   {
      $arr = array_values(array_filter($sensors,
         function ($obj) use ($serial) {
            return $obj->serial == $serial;
         }));
      if (sizeof($arr) === 0) die('Sensor with serial:' . $serial . ' does not exist!');
      if (sizeof($arr) > 1) die('Multiple sensors with serial:' . $serial);
      return $arr[0]->id;
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