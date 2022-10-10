<?php

namespace App\db\dal;

require_once(__DIR__ . "/../../../vendor/autoload.php");

use App\db\DbHelper;
use App\dto\CacheJsonDTO;
use App\dto\IndexViewModelChildren\SensorReadingDTO;
use App\model\Sensor;
use App\model\SensorReading;
use App\util\Console;
use DateTimeImmutable;
use JetBrains\PhpStorm\Pure;
use PDO;

class DalSensorReading extends AbstractDalBase
{
   /**
    *  @return string
    */
   public function GetTableName(): string { return "SensorReading"; }

   /**
    *  @return string
    */
   public function SqlCreateTableStmt(): string
   {
      $result = "create table " . $this->GetDatabaseNameDotTableName() .
         " ( " .
         "Id VARCHAR(64) NOT NULL PRIMARY KEY, " .
         "SensorId VARCHAR(64) NOT NULL, " .
         "Temp DECIMAL(18,1) NOT NULL, " .
         "RelHum DECIMAL(18,1) NOT NULL, " .
         "DateRecorded VARCHAR(64) NOT NULL, " .
         "DateAdded VARCHAR(64), " .
         "CONSTRAINT " . DalSensorReading::GetTableName() ."FKSensor foreign key (SensorId) references " . (new DalSensors)->GetTableName() . "(Id)" .
         " ) DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;";
      return $result;
   }

   /**
    *  @param Sensor[] $sensors
    */
   public static function ResetCache(array $sensors): void
   {
      CacheJsonDTO::CreateEmpty();
      DalSensorReading::GetLastReadingsFromCacheOrDatabase($sensors);
   }

   /**
    *  @param SensorReading $sensorReading
    */
   public static function SetLastReadingsCache(SensorReading $sensorReading): void
   {
      if (! CacheJsonDTO::DoesFileExist()) {
         $sensors = (new DalSensors())->GetAll();
         (new CacheJsonDTO((new DalSensorReading())->GetLastSensorReadings($sensors)))->Save();
      }
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
      if (! CacheJsonDTO::DoesFileExist()) {
         $lastReadings = ((new DalSensorReading())->GetLastSensorReadings($sensors));
         (new CacheJsonDTO($lastReadings))->Save();
      }
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
         $lastReading = (new DalSensorReading())->GetLastSensorReading($sensor->id);
         $lastReadings[$sensor->id] = $lastReading;
         $isDirty = true;
      }
      // update cache if any data came from the DB
      if ($isDirty) {
         (new CacheJsonDTO($lastReadings))->Save();
      }

      return $lastReadings;
   }

   /**
    * Get assoc array of sensor id and sensor's last SensorReading? (SensorReading|null)[]
    * @param Sensor[] $sensors
    * @return array<SensorReading|null>
    */
   public function GetLastSensorReadings(array $sensors): array {
      $lastReadings = [];
      foreach ($sensors as $sensor) {
         $lastReadings[$sensor->id] = (new DalSensorReading())->GetLastSensorReading($sensor->id);
      }
      return $lastReadings;
   }

   /**
    *  @param string $sensorId
    *  @return SensorReading|null
    */
   public function GetLastSensorReading(string $sensorId): SensorReading|null
   {
      $pdo = DbHelper::GetPDO();
      $qry = "SELECT Id, " .
         " " .
         "Temp, " .
         "RelHum, " .
         "DateRecorded, " .
         "DateAdded " .
         " FROM " . $this->GetDatabaseNameDotTableName() .
         " WHERE SensorId = ? " .
         " ORDER BY DateRecorded DESC LIMIT 1;";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$sensorId]);
      $value = $stmt->fetch();
      if (!$value) {
         return null;
      }
      $value["SensorId"] = $sensorId;
      return $this->Map($value);
   }

   /**
    * @param string $from
    * @param string $to
    * @return SensorReadingDTO[]
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
         " FROM " . $this->GetDatabaseNameDotTableName() .
         " WHERE DateRecorded >= ? " .
         " AND DateRecorded <= ? " .
         " ORDER BY DateRecorded ASC";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$from, $to]);
      while ($value = $stmt->fetch()) {
         $key = $value['SensorId'];
         if (! array_key_exists($key, $result)) {
            $result[$key] = [];
         }
         array_push($result[$key], $this->MapToDTO($value));
      }

      return $result;
   }

   /**
    *  @param string $sensorId
    *  @return SensorReadingDTO[]
    */
   public function GetAllWhereSensorId(string $sensorId): array
   {
      $pdo = DbHelper::GetPDO();
      $result = [];
      $qry = "SELECT Id, " .
         " " .
         "Temp, " .
         "RelHum, " .
         "DateRecorded " .
         " " .
         " FROM " . $this->GetDatabaseNameDotTableName() .
         " WHERE SensorId >= ? " .
         " ORDER BY DateRecorded ASC";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$sensorId]);
      while ($value = $stmt->fetch()) {
         $value['DateAdded'] = null;
         $value['SensorId'] = $sensorId;
         array_push($result, $this->MapToDTO($value));
      }

      return $result;
   }

   /**
    *  @param SensorReading[] $objects
    *  @param PDO $pdo
    */
   protected function Insert($objects, PDO $pdo): void
   {
      $qry = "INSERT INTO " . $this->GetDatabaseNameDotTableName() . " ( " .
         "Id, " .
         "SensorId, " .
         "Temp, " .
         "RelHum, " .
         "DateRecorded, " .
         "DateAdded) " .
         " VALUES " . $this->getPlaceHolders(numberOfQuestionMarks: 6, numberOfRows: sizeof($objects)) . ";";
      $stmt = $pdo->prepare($qry);
      $params = [];
      foreach ($objects as $object) {
         array_push($params, $object->id, $object->sensorId, $object->temp,
            $object->relHum, $object->dateRecorded, $object->dateAdded);
      }
      $stmt->execute($params);
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
         DateTimeImmutable::createFromFormat('YmdHi', $value["DateRecorded"]),
         $value["DateAdded"] === null ? null : DateTimeImmutable::createFromFormat('YmdHi', $value["DateAdded"])
      );
   }

   public function MapToDTO(array $value): SensorReadingDTO {

      return (new SensorReadingDTO(true, true, true))->
         setDate(DateTimeImmutable::createFromFormat('YmdHi', $value['DateRecorded']))->
         setTemp(floatval($value['Temp']))->
         setRelHum($value['RelHum']);
   }

   /**
    * @param string $id Parent sensor id
    */
   public function DeleteWhereSensorId(string $id): void {
      $pdo = DbHelper::GetPDO();
      $qry = "DELETE FROM " . $this->GetDatabaseNameDotTableName() . " WHERE SensorId = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$id]);
   }


   /**
    * @param string $id SensorReading id
    */
   public function Delete(string $id): void
   {
      $pdo = DbHelper::GetPDO();
      $qry = "DELETE FROM " . $this->GetDatabaseNameDotTableName() . " WHERE Id = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$id]);
   }

   /**
    * @param SensorReading $object
    */
   public function Update($object): void
   {
      $pdo = DbHelper::GetPDO();
      $qry = "UPDATE " . $this->GetDatabaseNameDotTableName() . " SET " .
         "SensorId = ?, " .
         "Temp = ?, " .
         "RelHum = ?, " .
         "DateRecorded = ?, " .
         "DateAdded = ? " .
         "WHERE Id = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$object->sensorId, $object->temp,
         $object->relHum, $object->dateRecorded, $object->dateAdded, $object->id]);
   }
}
