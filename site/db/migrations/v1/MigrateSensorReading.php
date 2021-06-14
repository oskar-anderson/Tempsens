<?php

namespace App\db\migrations\v1;

require_once(__DIR__."/../../../../vendor/autoload.php");

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensorReadingTmp;
use App\model\Sensor;
use App\model\SensorReading;
use App\model\SensorReadingTmp;
use App\util\Base64;
use PDO;

class MigrateSensorReading
{
   /**
    *  @param Sensor[] $sensors
    */
   public static function GetUpSensorReading(PDO $db1, array $sensors): array
   {
      $qry = "SELECT `id`, `passkey`, `device`, `temp`, `relHum`, `compQuant`, `pressure`, `alarms`, 
             `compType`, `tempU`, `pressureU`, `timer`, `dactdate` FROM `webtemp-orig`;";
      $oldSensorReadings = array();
      if (! $res = $db1->query($qry)) {
         die("Sensors query error!\n");
      }

      while ($values = $res->fetch()) {
         array_push($oldSensorReadings, new OldSensorReading(
               $values["id"],
               $values["passkey"],
               $values["device"],
               $values["temp"],
               $values["relHum"],
               $values["compQuant"],
               $values["pressure"],
               $values["alarms"],
               $values["compType"],
               $values["tempU"],
               $values["pressureU"],
               $values["timer"],
               $values["dactdate"])
         );
      }

      $result = [];
      $resultTmp = [];

      foreach ($oldSensorReadings as $itemSensorReading) {
         $sensorId = (new DalSensorReading())->GetSensorBySerial($sensors, $itemSensorReading->passkey);
         $sensorReading = new SensorReading(
            id: Base64::GenerateId(),
            sensorId: $sensorId,
            temp: floatval($itemSensorReading->temp),
            relHum: floatval($itemSensorReading->relHum),
            dateRecorded: $itemSensorReading->dactdate,
            dateAdded: null);
         array_push($result, $sensorReading);
         $sensorReading = new SensorReadingTmp(
            id: Base64::GenerateId(),
            sensorId: $sensorId,
            temp: floatval($itemSensorReading->temp),
            relHum: floatval($itemSensorReading->relHum),
            dateRecorded: $itemSensorReading->dactdate,
            dateAdded: null, tmpDupId: Base64::GenerateId(), tmpDupSensorId: $sensorId, tmpDupDateRecorded: $itemSensorReading->dactdate);
         array_push($resultTmp, $sensorReading);
      }

      return [$result, $resultTmp];
   }
}