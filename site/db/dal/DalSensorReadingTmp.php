<?php

namespace App\db\dal;

require_once(__DIR__ . "/../../../vendor/autoload.php");

use App\db\DbHelper;
use App\dto\CacheJsonDTO;
use App\model\Sensor;
use App\model\SensorReading;
use App\model\SensorReadingTmp;
use JetBrains\PhpStorm\Pure;
use PDO;

class DalSensorReadingTmp implements IDalBase
{
   /**
    *  @return string
    */
   public function GetName(): string { return "SensorReadingTmp"; }

   /**
    *  @return string
    */
   public function SqlCreateTableStmt(): string
   {
      $result = "create table " . DalSensorReadingTmp::GetName() .
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
         " FROM " . $this->GetName() .
         " WHERE DateRecorded >= ? " .
         " AND DateRecorded <= ? " .
         " ORDER BY DateRecorded ASC";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$from, $to]);

      return $result;
   }

   /**
    *  @param SensorReadingTmp $sensorReading
    *  @param PDO $pdo
    */
   public function Create(SensorReadingTmp $sensorReading, PDO $pdo): void
   {
      $qry = "INSERT INTO " . $this->GetName() . " ( " .
         "Id, " .
         "SensorId, " .
         "Temp, " .
         "RelHum, " .
         "DateRecorded, " .
         "DateAdded, TmpDupId, TmpDupSensorId, TmpDupDateRecorded) " .
         " VALUES (?,?,?,?,?,?,?,?,?);";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([
         $sensorReading->id, $sensorReading->sensorId, $sensorReading->temp,
         $sensorReading->relHum, $sensorReading->dateRecorded, $sensorReading->dateAdded,
         $sensorReading->tmpDupId, $sensorReading->tmpDupSensorId, $sensorReading->tmpDupDateRecorded ]);
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
}
