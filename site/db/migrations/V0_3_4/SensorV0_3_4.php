<?php

namespace App\db\migrations\V0_3_4;

use App\db\migrations\V1_0_0\SensorV1_0_0;
use App\util\Base64;

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
    public function GetUp($model, $minTemp, $maxTemp, $minRelHum, $maxRelHum, $readingIntervalMinutes) {
         $newSensor = new SensorV1_0_0(
            id: Base64::GenerateId(),
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
}