<?php

namespace App\db\dal;

require_once(__DIR__."/../../../vendor/autoload.php");

use App\domain\Sensor;
use App\db\DbHelper;
use App\domain\SensorReading;
use App\dtoWeb\SensorAndLastReading;
use DateTimeImmutable;
use JetBrains\PhpStorm\Pure;
use PDO;


class DalSensors extends AbstractDalBase
{
   /**
    *  @return string
    */
    public function SqlCreateTableStmt(): string
    {
        $result = "CREATE TABLE " . Sensor::TableName .
            " ( " .
            Sensor::IdColumnName . " VARCHAR(64) NOT NULL PRIMARY KEY, " .
            Sensor::NameColumnName . " VARCHAR(64) NOT NULL, " .
            Sensor::SerialColumnName . " VARCHAR(64) NOT NULL, " .
            Sensor::ModelColumnName . " VARCHAR(64) NOT NULL, " .
            Sensor::IpColumnName . " VARCHAR(64) NOT NULL, " .
            Sensor::LocationColumnName . " VARCHAR(64) NOT NULL, " .
            Sensor::IsPortableColumnName . " INTEGER NOT NULL, " .
            Sensor::MinTempColumnName . " DECIMAL(18,1) NOT NULL, " .
            Sensor::MaxTempColumnName . " DECIMAL(18,1) NOT NULL, " .
            Sensor::MinRelHumColumnName . " DECIMAL(18,1) NOT NULL, " .
            Sensor::MaxRelHumColumnName . " DECIMAL(18,1) NOT NULL, " .
            Sensor::ReadingIntervalMinutesColumnName . " INTEGER NOT NULL " .
            " ) DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;";
        return $result;
    }

   /**
    *  @return Sensor[]
    */
   public function GetAll(): array {
      $qry = "SELECT " . Sensor::IdColumnName . ", ".
                           Sensor::NameColumnName . ", " .
                           Sensor::SerialColumnName . ", " .
                           Sensor::ModelColumnName . ", " .
                           Sensor::IpColumnName . ", " .
                           Sensor::LocationColumnName . ", " .
                           Sensor::IsPortableColumnName . ", " .
                           Sensor::MinTempColumnName . ", " .
                           Sensor::MaxTempColumnName . ", " .
                           Sensor::MinRelHumColumnName . ", " .
                           Sensor::MaxRelHumColumnName . ", " .
                           Sensor::ReadingIntervalMinutesColumnName .
                           " FROM " . Sensor::TableName . ";";
      $pdo = DbHelper::GetPDO();
      $res = $pdo->query($qry);
      $result = array();
      while ($value = $res->fetch()) {
         array_push($result, $this->Map($value));
      }
      return $result;
   }

   /**
    *  @return SensorAndLastReading[]
    */
   public function GetAllWithLastReading(): array {
      $qry = "SELECT s." . Sensor::IdColumnName . ", ".
         "s." . Sensor::NameColumnName . ", " .
         "s." . Sensor::SerialColumnName . ", " .
         "s." . Sensor::ModelColumnName . ", " .
         "s." . Sensor::IpColumnName . ", " .
         "s." . Sensor::LocationColumnName . ", " .
         "s." . Sensor::IsPortableColumnName . ", " .
         "s." . Sensor::MinTempColumnName . ", " .
         "s." . Sensor::MaxTempColumnName . ", " .
         "s." . Sensor::MinRelHumColumnName . ", " .
         "s." . Sensor::MaxRelHumColumnName . ", " .
         "s." . Sensor::ReadingIntervalMinutesColumnName . ", " .
         "t0." . SensorReading::IdColumnName . " AS sensor_reading_id, " .
         "t0." . SensorReading::SensorIdColumnName . ", " .
         "t0." . SensorReading::RelHumColumnName . ", " .
         "t0." . SensorReading::TempColumnName . ", " .
         "t0." . SensorReading::DateAddedColumnName . ", " .
         "t0." . SensorReading::DateRecordedColumnName . " " .
         "FROM " . Sensor::TableName . " AS s " .
         "LEFT JOIN ( " .
            "SELECT " .
               "srlast." . SensorReading::IdColumnName . ", " .
               "srlast." . SensorReading::SensorIdColumnName . ", " .
               "srlast." . SensorReading::TempColumnName . ", " .
               "srlast." . SensorReading::RelHumColumnName . ", " .
               "srlast." . SensorReading::DateAddedColumnName . ", " .
               "srlast." . SensorReading::DateRecordedColumnName . " " .
            "FROM ( " .
               "SELECT " .
                  "sr1." . SensorReading::IdColumnName . ", " .
                  "sr1." . SensorReading::SensorIdColumnName . ", " .
                  "sr1." . SensorReading::TempColumnName . ", " .
                  "sr1." . SensorReading::RelHumColumnName . ", " .
                  "sr1." . SensorReading::DateAddedColumnName . ", " .
                  "sr1." . SensorReading::DateRecordedColumnName . ", " .
                  "ROW_NUMBER() OVER(PARTITION BY sr1." . SensorReading::SensorIdColumnName . " ORDER BY sr1." . SensorReading::DateRecordedColumnName . " DESC) AS rn " .
               "FROM " . SensorReading::TableName . " AS sr1 " .
            ") AS srlast " .
            "WHERE srlast.rn <= 1 " .
         ") AS t0 ON s." . Sensor::IdColumnName . " = t0." . SensorReading::SensorIdColumnName . " " .
         "ORDER BY s." . Sensor::NameColumnName . ", s." . Sensor::IdColumnName .";";
      $pdo = DbHelper::GetPDO();
      $res = $pdo->query($qry);
      /** @var SensorAndLastReading[] $result */
      $result = array();
      while ($value = $res->fetch()) {
         $sensorId = $value[Sensor::IdColumnName];
         $sensorName = $value[Sensor::NameColumnName];
         $sensorSerial = $value[Sensor::SerialColumnName];
         $sensorModel = $value[Sensor::ModelColumnName];
         $sensorIp = $value[Sensor::IpColumnName];
         $sensorLocation = $value[Sensor::LocationColumnName];
         $sensorIsPortable = boolval($value[Sensor::IsPortableColumnName]);
         $sensorMinTemp = floatval($value[Sensor::MinTempColumnName]);
         $sensorMaxTemp = floatval($value[Sensor::MaxTempColumnName]);
         $sensorMinRelHum = floatval($value[Sensor::MinRelHumColumnName]);
         $sensorMaxRelHum = floatval($value[Sensor::MaxRelHumColumnName]);
         $sensorReadingIntervalMinutes = intval($value[Sensor::ReadingIntervalMinutesColumnName]);

         $sensor = new \App\dtoWeb\Sensor(
            id: $sensorId, name: $sensorName, serial: $sensorSerial,
            model: $sensorModel, ip: $sensorIp, location: $sensorLocation,
            isPortable: $sensorIsPortable, minTemp: $sensorMinTemp, maxTemp: $sensorMaxTemp,
            minRelHum: $sensorMinRelHum, maxRelHum: $sensorMaxRelHum, readingIntervalMinutes: $sensorReadingIntervalMinutes
         );

         $readingId = $value["sensor_reading_id"];

         $sensorReading = null;
         if ($readingId !== null) {
            $readingSensorId = $value[SensorReading::SensorIdColumnName];
            $readingRelHum = floatval($value[SensorReading::RelHumColumnName]);
            $readingTemp = floatval($value[SensorReading::TempColumnName]);
            $readingDateRecorded = DateTimeImmutable::createFromFormat('YmdHis', $value[SensorReading::DateRecordedColumnName]);
            $readingDateAdded = $value[SensorReading::DateAddedColumnName] === null ? null : DateTimeImmutable::createFromFormat('YmdHis', $value[SensorReading::DateAddedColumnName]);

            $sensorReading = new \App\dtoWeb\SensorReading(id: $readingId, sensorId: $readingSensorId, temp: $readingTemp, relHum: $readingRelHum, dateRecorded: $readingDateRecorded, dateAdded: $readingDateAdded);
         }
         $row = new SensorAndLastReading($sensor, $sensorReading);

         array_push($result, $row);
      }
      return $result;
   }

   /**
    *  @return Sensor|null
    */
   public function GetFirstOrDefault(string $id): Sensor|null {
      $qry = "SELECT " . Sensor::IdColumnName . ", ".
         Sensor::NameColumnName . ", " .
         Sensor::SerialColumnName . ", " .
         Sensor::ModelColumnName . ", " .
         Sensor::IpColumnName . ", " .
         Sensor::LocationColumnName . ", " .
         Sensor::IsPortableColumnName . ", " .
         Sensor::MinTempColumnName . ", " .
         Sensor::MaxTempColumnName . ", " .
         Sensor::MinRelHumColumnName . ", " .
         Sensor::MaxRelHumColumnName . ", " .
         Sensor::ReadingIntervalMinutesColumnName .
         " FROM " . Sensor::TableName .
         " WHERE " .Sensor::IdColumnName . " = ? " .
         " LIMIT 1;";
      $pdo = DbHelper::GetPDO();
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$id]);
      $value = $stmt->fetch();
      if (!$value) {
         return null;
      }
      return $this->Map($value);
   }

   /**
    *  @param Sensor $object
    */
   public function Update($object): void {
      $pdo = DbHelper::GetPDO();
      $qry = "UPDATE " . Sensor::TableName . " SET " .
         Sensor::NameColumnName . " = ?, " .
         Sensor::SerialColumnName . " = ?, " .
         Sensor::ModelColumnName . " = ?, " .
         Sensor::IpColumnName . " = ?, " .
         Sensor::LocationColumnName . " = ?, " .
         Sensor::IsPortableColumnName . " = ?, " .
         Sensor::MinTempColumnName . " = ?, " .
         Sensor::MaxTempColumnName . " = ?, " .
         Sensor::MinRelHumColumnName . " = ?, " .
         Sensor::MaxRelHumColumnName . " = ?, " .
         Sensor::ReadingIntervalMinutesColumnName . " = ? " .
         "WHERE " . Sensor::IdColumnName . " = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$object->name, $object->serial,
         $object->model, $object->ip, $object->location, intval($object->isPortable),
         $object->minTemp, $object->maxTemp, $object->minRelHum, $object->maxRelHum,
         $object->readingIntervalMinutes, $object->id ]);
   }

   /**
    *  @param string $id
    */
   public function Delete(string $id): void {
      (new DalSensorReading())->DeleteWhereSensorId($id);
      $pdo = DbHelper::GetPDO();

      $qry = "DELETE FROM " . Sensor::TableName . " WHERE " . Sensor::IdColumnName . " = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$id]);
   }

   /**
    *  @param Sensor[] $objects
    *  @param PDO $pdo
    */
   protected function Insert($objects, PDO $pdo): void {
      $qry = "INSERT INTO " . Sensor::TableName . " ( " .
         Sensor::IdColumnName . ", " .
         Sensor::NameColumnName . ", " .
         Sensor::SerialColumnName . ", " .
         Sensor::ModelColumnName . ", " .
         Sensor::IpColumnName . ", " .
         Sensor::LocationColumnName . ", " .
         Sensor::IsPortableColumnName . ", " .
         Sensor::MinTempColumnName . ", " .
         Sensor::MaxTempColumnName . ", " .
         Sensor::MinRelHumColumnName . ", " .
         Sensor::MaxRelHumColumnName . ", " .
         Sensor::ReadingIntervalMinutesColumnName . " ) " .
         " VALUES " . $this->getPlaceHolders(numberOfQuestionMarks: 12, numberOfRows: sizeof($objects)) . ";";
      $stmt = $pdo->prepare($qry);
      $params = [];
      foreach ($objects as $object) {
         array_push($params, $object->id, $object->name, $object->serial,
            $object->model, $object->ip, $object->location, intval($object->isPortable),
            $object->minTemp, $object->maxTemp, $object->minRelHum, $object->maxRelHum,
            $object->readingIntervalMinutes);
      }
      $stmt->execute($params);
   }

   /**
    *  @param array $value
    *  @return Sensor
    */
   #[Pure]
   public function Map(array $value): Sensor
   {
      return new Sensor(
         $value[Sensor::IdColumnName],
         $value[Sensor::NameColumnName],
         $value[Sensor::SerialColumnName],
         $value[Sensor::ModelColumnName],
         $value[Sensor::IpColumnName],
         $value[Sensor::LocationColumnName],
         boolval($value[Sensor::IsPortableColumnName]),
         floatval($value[Sensor::MinTempColumnName]),
         floatval($value[Sensor::MaxTempColumnName]),
         floatval($value[Sensor::MinRelHumColumnName]),
         floatval($value[Sensor::MaxRelHumColumnName]),
         intval($value[Sensor::ReadingIntervalMinutesColumnName])
      );
   }
}
