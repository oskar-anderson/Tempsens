<?php


namespace App\dtoApi\Sensor;

class SensorWithAuth_v1
{
   function __construct(
      public Sensor_v1 $sensor,
      public string $auth,
   ) {}
}