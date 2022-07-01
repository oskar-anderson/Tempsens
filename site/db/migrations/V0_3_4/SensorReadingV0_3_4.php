<?php

namespace App\db\migrations\V0_3_4;

use App\db\migrations\V1_0_0\SensorReadingV1_0_0;
use App\util\Base64;

class SensorReadingV0_3_4 {

   function __construct(
      public string $id,
      public string $passkey,
      public string $device,
      public float $temp,
      public float $relHum,
      public float $compQuant,
      public float $pressure,
      public string $alarms,
      public string  $compType,
      public string $tempU,
      public string $pressureU,
      public int $timer,
      public ?string $dactdate
   ) {}


   /**
    *  @return SensorReadingV1_0_0
    */
    public function GetUp(string $sensorId, bool $isPortable): SensorReadingV1_0_0
    {
      // $sensorId = SensorReadingV0_3_4::GetSensorIdBySerial($sensors, $this->passkey);
      $sensorReading = new SensorReadingV1_0_0(
         id: Base64::GenerateId(),
         sensorId: $sensorId,
         temp: floatval($this->temp),
         relHum: floatval($this->relHum),
         dateRecorded: $this->dactdate,
         dateAdded: $isPortable ? $this->dactdate : null);

      return $sensorReading;
   }
}
