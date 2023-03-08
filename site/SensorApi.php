<?php

namespace App;

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensors;
use App\db\DbHelper;
use App\domain\SensorReading;
use App\domain\Sensor;
use App\util\Base64;
use App\util\Helper;

require_once(__DIR__."/../vendor/autoload.php");


class SensorApi
{
   public static function Save(string $serial, float $temp, float $relHum): string
   {
      /** @var Sensor|null $sensor */
      $sensor = collect((new DalSensors())->GetAll())->first(fn(Sensor $x) => $x->serial === $serial);
      if ($sensor === null) {
         die('Sensor with serial:' . $serial . ' does not exist!');
      }
      $id = Base64::GenerateId();
      $reading = new SensorReading(
         id: $id,
         sensorId: $sensor->id,
         temp: $temp,
         relHum: $relHum,
         dateRecorded: Helper::GetDateNowAsDateTime(),
         dateAdded: null
      );
      $db = DbHelper::GetPDO();
      (new DalSensorReading)->InsertByChunk([$reading], $db);
      DalSensorReading::SetLastReadingsCache($reading);
      return $id;
   }
}
