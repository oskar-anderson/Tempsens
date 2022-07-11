<?php

namespace App\db\dal;

require_once(__DIR__."/../../../vendor/autoload.php");

use App\model\Sensor;
use App\db\DbHelper;
use JetBrains\PhpStorm\Pure;
use PDO;


class DalSensors implements IDalBase
{
   /**
    *  @return string
    */
   public function GetName(): string { return "Sensors"; }

   /**
    *  @return string
    */
    public function SqlCreateTableStmt(): string
    {
        $result = "create table " . $this->GetName() .
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
                           " FROM " . $this->GetName() . ";";
      $pdo = DbHelper::GetPDO();
      $res = $pdo->query($qry);
      $result = array();
      while ($value = $res->fetch()) {
         array_push($result, $this->Map($value));
      }
      return $result;
   }

   /**
    *  @param Sensor $sensor
    */
   public function Update(Sensor $sensor) {
      $pdo = DbHelper::GetPDO();
      $qry = "UPDATE " . $this->GetName() . " SET " .
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
      $stmt->execute([$sensor->name, $sensor->serial,
         $sensor->model, $sensor->ip, $sensor->location, $sensor->isPortable,
         $sensor->minTemp, $sensor->maxTemp, $sensor->minRelHum, $sensor->maxRelHum,
         $sensor->readingIntervalMinutes, $sensor->id ]);
   }

   /**
    *  @param string $id
    */
   public function Delete(string $id) {
      $pdo = DbHelper::GetPDO();
      $qry = "DELETE FROM " . (new DalSensorReading())->GetName() . " WHERE SensorId = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$id]);

      $qry = "DELETE FROM " . $this->GetName() . " WHERE Id = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$id]);
   }

   /**
    *  @param Sensor $sensor
    *  @param PDO $pdo
    */
   public function Create(Sensor $sensor, PDO $pdo): void {
      $qry = "INSERT INTO " . $this->GetName() . " ( " .
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
         " VALUES (?,?,?,?,?,?,?,?,?,?,?,?);";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$sensor->id, $sensor->name, $sensor->serial,
         $sensor->model, $sensor->ip, $sensor->location, $sensor->isPortable,
         $sensor->minTemp, $sensor->maxTemp, $sensor->minRelHum, $sensor->maxRelHum,
         $sensor->readingIntervalMinutes ]);
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
