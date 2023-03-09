<?php

namespace App\dtoWeb;

class SensorAndLastReading
{
   function __construct(
      public Sensor $sensor,
      public ?SensorReading $sensorReading
   ) {}
}