<?php

namespace App\db\dal;

require_once(__DIR__ . "/../../../vendor/autoload.php");

use App\db\DbHelper;
use App\model\SensorReadingTmp;
use JetBrains\PhpStorm\Pure;
use PDO;

class DalSensorReadingTmp extends AbstractDalBase
{
   /**
    *  @return string
    */
   public function GetTableName(): string { return "SensorReadingTmp"; }

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
         "TmpDupId VARCHAR(64), " .
         "TmpDupSensorId VARCHAR(64), " .
         "TmpDupDateRecorded VARCHAR(64) " .
         " ) DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;";
      return $result;
   }

   /* TODO Why not including SensorId OR DateAdded significantly increases performance???
    * Selecting all except SensorId takes 1x time and selecting all except DateAdded takes 1x time,
    * but selecting all takes 5x time.
    */
   public function GetAllBetweenTest3(string $from, string $to): array
   {
      $pdo = DbHelper::GetPDO();
      $result = [];
      $qry = "SELECT Id, SensorId, " .
         "Temp, RelHum, " .
         "DateRecorded, DateAdded, TmpDupId, TmpDupSensorId, TmpDupDateRecorded " .
         " FROM " . $this->GetDatabaseNameDotTableName() .
         " WHERE DateRecorded >= ? " .
         " AND DateRecorded <= ? " .
         " ORDER BY DateRecorded ASC";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$from, $to]);

      return $result;
   }

   public function GetAllBetweenTest4(string $from, string $to): array
   {
      $pdo = DbHelper::GetPDO();
      $result = [];
      $qry = "SELECT Id, Temp, RelHum, DateRecorded, DateAdded" .
         " " .
         " " .
         " FROM " . $this->GetDatabaseNameDotTableName() .
         " WHERE DateRecorded >= ? " .
         " AND DateRecorded <= ? " .
         " ORDER BY DateRecorded ASC";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$from, $to]);

      return $result;
   }

   /**
    *  @param SensorReadingTmp[] $objects
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
         "DateAdded, TmpDupId, TmpDupSensorId, TmpDupDateRecorded) " .
         " VALUES " . $this->getPlaceHolders(9, sizeof($objects)) . ";";
      $stmt = $pdo->prepare($qry);
      $params = [];
      foreach ($objects as $object) {
         array_push($params, $object->id, $object->sensorId, $object->temp,
            $object->relHum, $object->dateRecorded, $object->dateAdded,
            $object->tmpDupId, $object->tmpDupSensorId, $object->tmpDupDateRecorded);
      }
      $stmt->execute($params);
   }

   /**
    * @param array $value
    * @return SensorReadingTmp
    */
   #[Pure]
   public function Map(array $value): SensorReadingTmp
   {
      return new SensorReadingTmp(
         $value['Id'],
         $value['SensorId'],
         floatval($value['Temp']),
         floatval($value['RelHum']),
         $value['DateRecorded'],
         $value['DateAdded'], $value['TmpDupId'], $value['TmpDupSensorId'], $value['TmpDupDateRecorded']
      );
   }

   public function Delete(string $id): void
   {
      // TODO: Implement Delete() method.
   }

   public function Update($object): void
   {
      // TODO: Implement Update() method.
   }
}
