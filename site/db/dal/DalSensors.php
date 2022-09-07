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
   public function GetTableName(): string { return "Sensors"; }

   /**
    *  @return string
    */
    public function SqlCreateTableStmt(): string
    {
        $result = "create table " . $this->GetDatabaseNameDotTableName() .
            " ( " .
            "Id VARCHAR(64) NOT NULL PRIMARY KEY, " .
            "Name VARCHAR(64) NOT NULL, " .
            "Serial VARCHAR(64) NOT NULL, " .
            "Model VARCHAR(64) NOT NULL, " .
            "Ip VARCHAR(64) NOT NULL, " .
            "Location VARCHAR(64) NOT NULL, " .
            "IsPortable INTEGER NOT NULL, " .
            "MinTemp DECIMAL(18,1) NOT NULL, " .
            "MaxTemp DECIMAL(18,1) NOT NULL, " .
            "MinRelHum DECIMAL(18,1) NOT NULL, " .
            "MaxRelHum DECIMAL(18,1) NOT NULL, " .
            "ReadingIntervalMinutes INTEGER NOT NULL " .
            " ) DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;";
        return $result;
    }

   /**
    *  @return Sensor[]
    */
   public function GetAll(): array {
      $qry = "SELECT Id, " .
                           "Name, " .
                           "Serial, " .
                           "Model, " .
                           "Ip, " .
                           "Location, " .
                           "IsPortable, " .
                           "MinTemp, " .
                           "MaxTemp, " .
                           "MinRelHum, " .
                           "MaxRelHum, " .
                           "ReadingIntervalMinutes " .
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
         "Name = ?, " .
         "Serial = ?, " .
         "Model = ?, " .
         "Ip = ?, " .
         "Location = ?, " .
         "IsPortable = ?, " .
         "MinTemp = ?, " .
         "MaxTemp = ?, " .
         "MinRelHum = ?, " .
         "MaxRelHum = ?, " .
         "ReadingIntervalMinutes = ? " .
         "WHERE Id = ?";
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

      $qry = "DELETE FROM " . $this->GetDatabaseNameDotTableName() . " WHERE Id = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$id]);
   }

   /**
    *  @param Sensor[] $objects
    *  @param PDO $pdo
    */
   protected function Insert($objects, PDO $pdo): void {
      $qry = "INSERT INTO " . $this->GetDatabaseNameDotTableName() . " ( " .
         "Id, " .
         "Name, " .
         "Serial, " .
         "Model, " .
         "Ip, " .
         "Location, " .
         "IsPortable, " .
         "MinTemp, " .
         "MaxTemp, " .
         "MinRelHum, " .
         "MaxRelHum, " .
         "ReadingIntervalMinutes ) " .
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
         $value['Id'],
         $value['Name'],
         $value['Serial'],
         $value['Model'],
         $value['Ip'],
         $value['Location'],
         boolval($value['IsPortable']),
         floatval($value['MinTemp']),
         floatval($value['MaxTemp']),
         floatval($value['MinRelHum']),
         floatval($value['MaxRelHum']),
         intval($value['ReadingIntervalMinutes'])
      );
   }
}
