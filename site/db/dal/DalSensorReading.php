<?php

namespace App\db\dal;

use App\db\DbHelper;
use App\domain\Sensor;
use App\domain\SensorReading;
use App\util\Console;
use DateTimeImmutable;
use PDO;

class DalSensorReading extends AbstractDalBase
{
   /**
    *  @return string
    */
   public function SqlCreateTableStmt(): string
   {
      $result = "CREATE TABLE " . SensorReading::TableName .
         " ( " .
         SensorReading::IdColumnName . " VARCHAR(64) NOT NULL PRIMARY KEY, " .
         SensorReading::SensorIdColumnName . " VARCHAR(64) NOT NULL, " .
         SensorReading::TempColumnName . " DECIMAL(18,1) NOT NULL, " .
         SensorReading::RelHumColumnName . " DECIMAL(18,1) NOT NULL, " .
         SensorReading::DateRecordedColumnName . " VARCHAR(64) NOT NULL, " .
         SensorReading::DateAddedColumnName . " VARCHAR(64), " .
         "CONSTRAINT " . SensorReading::TableName ."_fk_to_sensor foreign key ( " . SensorReading::SensorIdColumnName . " ) references " . Sensor::TableName . "(" . Sensor::IdColumnName . ") " .
         " ) DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;";
      return $result;
   }

   /**
    *  @param Sensor[] $sensors
    *  @return \App\dtoWeb\SensorReading[]
    */
   public static function GetLastReadingsFromCacheOrDefault(array $sensors): array
   {
      $lastReadings = [];
      foreach ($sensors as $sensor) {
         // Do a DB query to get the sensors last reading
         $lastReading = (new DalSensorReading())->GetLastSensorReading($sensor->id);
         $lastReadings[$sensor->id] = $lastReading;
      }
      return $lastReadings;
   }

   /**
    * Get assoc array of sensor id and sensor's last SensorReading? (SensorReading|null)[]
    * @param Sensor[] $sensors
    * @return array<\App\dtoWeb\SensorReading|null>
    */
   public function GetLastSensorReadings(array $sensors): array {
      // https://stackoverflow.com/questions/2411559/how-do-i-query-sql-for-a-latest-record-date-for-each-user
      // row_number() does not seem to help performance, this n+1 solution seems the best
      $lastReadings = [];
      foreach ($sensors as $sensor) {
         $lastReadings[$sensor->id] = (new DalSensorReading())->GetLastSensorReading($sensor->id);
      }
      return $lastReadings;
   }

   /**
    *  @param string $sensorId
    *  @return \App\dtoWeb\SensorReading|null
    */
   public function GetLastSensorReading(string $sensorId): \App\dtoWeb\SensorReading|null
   {
      $pdo = DbHelper::GetPDO();
      $qry = "SELECT " . SensorReading::IdColumnName . ", " .
         SensorReading::TempColumnName . ", " .
         SensorReading::RelHumColumnName . ", " .
         SensorReading::DateRecordedColumnName . ", " .
         SensorReading::DateAddedColumnName .
         " FROM " . SensorReading::TableName .
         " WHERE " . SensorReading::SensorIdColumnName . " = ? " .
         " ORDER BY " . SensorReading::DateRecordedColumnName . " DESC LIMIT 1;";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$sensorId]);
      $value = $stmt->fetch();
      if (!$value) {
         return null;
      }
      $value[SensorReading::SensorIdColumnName] = $sensorId;
      return $this->Map($value);
   }

   /**
    * @param string $from
    * @param string $to
    * @return \App\dtoWeb\SensorReading[]
    */
   public function GetAllBetween(string $from, string $to): array
   {
      $pdo = DbHelper::GetPDO();
      $result = [];
      $qry = "SELECT " .
         SensorReading::IdColumnName . ", " .
         SensorReading::SensorIdColumnName . ", " .
         SensorReading::TempColumnName . ", " .
         SensorReading::RelHumColumnName . ", " .
         SensorReading::DateRecordedColumnName . ", " .
         SensorReading::DateAddedColumnName .
         " FROM " . SensorReading::TableName .
         " WHERE " . SensorReading::DateRecordedColumnName . " >= ? " .
         " AND " . SensorReading::DateRecordedColumnName . " <= ? " .
         " ORDER BY " . SensorReading::DateRecordedColumnName . " ASC";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$from, $to]);
      while ($value = $stmt->fetch()) {
         $key = $value[SensorReading::SensorIdColumnName];
         if (! array_key_exists($key, $result)) {
            $result[$key] = [];
         }
         array_push($result[$key], $this->Map($value));
      }

      return $result;
   }

   /**
    *  @param string $sensorId
    *  @return \App\dtoWeb\SensorReading[]
    */
   public function GetAllWhereSensorId(string $sensorId): array
   {
      $pdo = DbHelper::GetPDO();
      $result = [];
      $qry = "SELECT " .
         SensorReading::IdColumnName . ", " .
         SensorReading::SensorIdColumnName . ", " .
         SensorReading::TempColumnName . ", " .
         SensorReading::RelHumColumnName . ", " .
         SensorReading::DateRecordedColumnName . ", " .
         SensorReading::DateAddedColumnName .
         " FROM " . SensorReading::TableName .
         " WHERE " . SensorReading::SensorIdColumnName . " >= ? " .
         " ORDER BY " . SensorReading::DateRecordedColumnName . " ASC;";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$sensorId]);
      while ($value = $stmt->fetch()) {
         $value[SensorReading::SensorIdColumnName] = $sensorId;
         array_push($result, $this->Map($value));
      }

      return $result;
   }

   /**
    *  @param SensorReading[] $objects
    *  @param PDO $pdo
    */
   protected function Insert($objects, PDO $pdo): void
   {
      $qry = "INSERT INTO " . SensorReading::TableName . " ( " .
         SensorReading::IdColumnName . ", " .
         SensorReading::SensorIdColumnName . ", " .
         SensorReading::TempColumnName . ", " .
         SensorReading::RelHumColumnName . ", " .
         SensorReading::DateRecordedColumnName . ", " .
         SensorReading::DateAddedColumnName . " ) " .
         " VALUES " . $this->getPlaceHolders(numberOfQuestionMarks: 6, numberOfRows: sizeof($objects)) . ";";
      $stmt = $pdo->prepare($qry);
      $params = [];
      foreach ($objects as $object) {
         array_push($params, $object->id, $object->sensorId, $object->temp,
            $object->relHum, $object->dateRecorded, $object->dateAdded);
      }
      $stmt->execute($params);
   }

   public function Map(array $value): \App\dtoWeb\SensorReading {

      return new \App\dtoWeb\SensorReading(
         id: $value[SensorReading::IdColumnName],
         sensorId: $value[SensorReading::SensorIdColumnName],
         temp: floatval($value[SensorReading::TempColumnName]),
         relHum: floatval($value[SensorReading::RelHumColumnName]),
         dateRecorded: DateTimeImmutable::createFromFormat('YmdHis', $value[SensorReading::DateRecordedColumnName]),
         dateAdded: $value[SensorReading::DateAddedColumnName] === null ? null : DateTimeImmutable::createFromFormat('YmdHis', $value[SensorReading::DateAddedColumnName])
      );
   }

   /**
    * @param string $id Parent sensor id
    */
   public function DeleteWhereSensorId(string $id): void {
      $pdo = DbHelper::GetPDO();
      $qry = "DELETE FROM " . SensorReading::TableName . " WHERE " . SensorReading::SensorIdColumnName . " = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$id]);
   }


   /**
    * @param string $id SensorReading id
    */
   public function Delete(string $id): void
   {
      $pdo = DbHelper::GetPDO();
      $qry = "DELETE FROM " . SensorReading::TableName . " WHERE " . SensorReading::IdColumnName . " = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$id]);
   }

   /**
    * @param SensorReading $object
    */
   public function Update($object): void
   {
      $pdo = DbHelper::GetPDO();
      $qry = "UPDATE " . SensorReading::TableName . " SET " .
         SensorReading::SensorIdColumnName . " = ?, " .
         SensorReading::TempColumnName . " = ?, " .
         SensorReading::RelHumColumnName . " = ?, " .
         SensorReading::DateRecordedColumnName . " = ?, " .
         SensorReading::DateAddedColumnName . " = ? " .
         "WHERE " . SensorReading::IdColumnName . " = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$object->sensorId, $object->temp,
         $object->relHum, $object->dateRecorded, $object->dateAdded, $object->id]);
   }
}
