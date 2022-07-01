<?php

namespace App\db\migrations\V1_0_0;

use App\model\Sensor;

class SensorV1_0_0 {

    // PHP 8: Constructor property promotion - pretty nice
    function __construct(
        public string $id,
        public string $name,
        public string $serial,
        public string $model,
        public string $ip,
        public string $location,
        public bool $isPortable,
        public float $minTemp,
        public float $maxTemp,
        public float $minRelHum,
        public float $maxRelHum,
        public int $readingIntervalMinutes
        )
    { }

   public function MapToModel(): Sensor
   {
      return new Sensor(
         $this->id,
         $this->name,
         $this->serial,
         $this->model,
         $this->ip,
         $this->location,
         $this->isPortable,
         $this->minTemp,
         $this->maxTemp,
         $this->minRelHum,
         $this->maxRelHum,
         $this->readingIntervalMinutes
       );
   }
}