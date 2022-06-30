<?php

namespace App;

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensorReadingTmp;
use App\db\dal\DalSensors;
use App\db\DbHelper;
use App\dto\IndexViewModelChildren\SensorReadingDTO;
use App\model\SensorReading;
use App\util\Base64;
use App\util\Helper;

require_once(__DIR__."/../vendor/autoload.php");


class SensorApi
{
   public static function Save($serial, $temp, $relHum)
   {
      $sensorId = SensorReading::GetSensorBySerial((new DalSensors())->GetAll(), $serial)->id;
      $reading = new SensorReading(
         id:Base64::GenerateId(),
         sensorId: $sensorId,
         temp: floatval($temp),
         relHum: floatval($relHum),
         dateRecorded: Helper::GetDateNow(),
         dateAdded: null
      );
      $db = DbHelper::GetPDO();
      (new DalSensorReading)->Create($reading, $db);
      DalSensorReading::SetLastReadingsCache($reading);
   }
}