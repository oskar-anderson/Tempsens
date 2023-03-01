<?php


namespace App\frontendDto\Sensor;

class SensorWithAuth_v1
{
   function __construct(
      public Sensor_v1 $sensor,
      public string $auth,
   ) {}
}