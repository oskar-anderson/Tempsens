<?php
/** @noinspection DuplicatedCode */

namespace App\db\migrations\V0_3_4;

use App\db\migrations\V1_0_0\SensorV1_0_0;
use Exception;
use Ramsey\Uuid\Uuid;

error_reporting(E_STRICT);

class SensorV0_3_4 {

    function __construct(
        public int $id,
        public ?string $name,
        public ?string $serial,
        public ?string $ip,
        public ?string $desc,
        public ?bool $portable,
        public ?bool $active,
     ) {}

   /**
    *  @return SensorV1_0_0
    */
    public function GetUp(string $model, float $minTemp, float $maxTemp, float $minRelHum, float $maxRelHum, int $readingIntervalMinutes): SensorV1_0_0
    {
         $newSensor = new SensorV1_0_0(
            id: Uuid::uuid4()->toString(),
            name: $this->name ?? "",
            serial: $this->serial ?? "", // this is supposed to be unique, but that is not forced. This is problematic as it is part of programm logic used in sensorReading to link to the sensor.
            model: $model,
            ip: $this->ip ?? "",
            location: $this->desc ?? "",
            isPortable: $this->portable ?? "",
            minTemp: $minTemp,
            maxTemp: $maxTemp,
            minRelHum: $minRelHum,
            maxRelHum: $maxRelHum,
            readingIntervalMinutes: $readingIntervalMinutes
         );
        return $newSensor;
     }


   /**
    * @param SensorV0_3_4[] $sensors
    * @param string $serial
    * @return SensorV0_3_4
    * @throws Exception
    */
   public static function GetSensorBySerial(array $sensors, string $serial): SensorV0_3_4
   {
      $arr = array_values(array_filter($sensors,
         function ($obj) use ($serial) {
            return $obj->serial === $serial;
         }));
      if (sizeof($arr) === 0) throw new Exception('Sensor with serial:' . $serial . ' does not exist!');
      if (sizeof($arr) > 1) throw new Exception('Multiple sensors with serial:' . $serial);
      return $arr[0];
   }
}