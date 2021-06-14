<?php

namespace App\db\migrations\v1;

class OldSensorReading {

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
      public string $dactdate,
   ) {}
}
