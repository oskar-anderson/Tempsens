<?php

namespace App\db\dal;

require_once(__DIR__."/../../../vendor/autoload.php");

use App\model\Sensor;
use App\db\DbHelper;
use JetBrains\PhpStorm\Pure;
use PDO;


class DalSensors extends AbstractDalBase
{
   /**
    *  @return string
    */
   public function GetTableName(): string { return "sensors"; }

   /**
    *  @return string
    */
    public function SqlCreateTableStmt(): string
    {
        $result = "CREATE TABLE " . $this->GetDatabaseNameDotTableName() .
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
                           " FROM " . $this->GetDatabaseNameDotTableName() . ";";
      $pdo = DbHelper::GetPDO();
      $res = $pdo->query($qry);
      $result = array();
      while ($value = $res->fetch()) {
         array_push($result, $this->Map($value));
      }
      return $result;
   }

   /**
    *  @param Sensor $object
    */
   public function Update($object): void {
      $pdo = DbHelper::GetPDO();
      $qry = "UPDATE " . $this->GetDatabaseNameDotTableName() . " SET " .
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

      $qry = "DELETE FROM " . $this->GetDatabaseNameDotTableName() . " WHERE " . Sensor::IdColumnName . " = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$id]);
   }

   /**
    *  @param Sensor[] $objects
    *  @param PDO $pdo
    */
   protected function Insert($objects, PDO $pdo): void {
      $qry = "INSERT INTO " . $this->GetDatabaseNameDotTableName() . " ( " .
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
