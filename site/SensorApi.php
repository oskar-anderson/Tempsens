<?php

namespace App;

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensors;
use App\db\DbHelper;
use App\model\SensorReading;
use App\model\Sensor;
use App\util\Base64;
use App\util\Helper;

require_once(__DIR__."/../vendor/autoload.php");


class SensorApi
{
   public static function Save(string $serial, float $temp, float $relHum): void
   {
      $sensorId = Sensor::GetSensorBySerial((new DalSensors())->GetAll(), $serial)->id;
      $reading = new SensorReading(
         id:Base64::GenerateId(),
         sensorId: $sensorId,
         temp: $temp,
         relHum: $relHum,
         dateRecorded: Helper::GetDateNowAsDateTime(),
         dateAdded: null
      );
      $db = DbHelper::GetPDO();
      (new DalSensorReading)->InsertByChunk([$reading], $db);
      DalSensorReading::SetLastReadingsCache($reading);
   }
}
